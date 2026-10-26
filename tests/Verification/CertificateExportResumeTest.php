<?php

namespace Tests\Verification;

use App\Models\Certificate;
use App\Models\ModuleProgress;
use App\Models\TrainingModule;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Batch 1: certificate verification (R13), report exports (R26),
 * and the "continue learning" resume card (R20).
 */
class CertificateExportResumeTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('admin')->firstOrFail();
    }

    private function learnerWithOpenModule(): array
    {
        $progress = ModuleProgress::enrolledOnly()
            ->where('percentage', '<', 100)
            ->firstOrFail();

        return [
            User::findOrFail($progress->user_id),
            TrainingModule::findOrFail($progress->training_module_id),
        ];
    }

    private function makeCertificate(array $overrides = []): Certificate
    {
        [$learner, $module] = $this->learnerWithOpenModule();

        return Certificate::create(array_merge([
            'user_id' => $learner->id,
            'training_module_id' => $module->id,
            'certificate_number' => 'EEC-TEST-' . uniqid(),
            'file_path' => 'certificates/test.pdf',
            'issued_at' => now()->subMonth(),
            'valid_until' => null,
        ], $overrides));
    }

    public function test_certificate_verification_is_public_and_shows_the_holder(): void
    {
        $certificate = $this->makeCertificate();

        // No actingAs(): this page must work for anyone holding a certificate.
        $this->get('/verify/' . $certificate->certificate_number)
            ->assertOk()
            ->assertSee('Certificate verified')
            ->assertSee($certificate->user->name)
            ->assertSee($certificate->module->title)
            ->assertSee($certificate->certificate_number);
    }

    public function test_verification_is_case_insensitive(): void
    {
        $certificate = $this->makeCertificate();

        $this->get('/verify/' . strtolower($certificate->certificate_number))
            ->assertOk()
            ->assertSee('Certificate verified');
    }

    public function test_expired_certificate_is_reported_as_expired(): void
    {
        $certificate = $this->makeCertificate([
            'valid_until' => now()->subDay(),
        ]);

        $this->get('/verify/' . $certificate->certificate_number)
            ->assertOk()
            ->assertSee('expired', false)
            ->assertDontSee('currently valid');
    }

    public function test_unknown_number_is_reported_as_not_found(): void
    {
        $this->get('/verify/EEC-0000-0000')
            ->assertOk()
            ->assertSee('Certificate not found');
    }

    public function test_reports_export_returns_csv_for_each_table(): void
    {
        $admin = $this->admin();

        foreach (['courses', 'learners', 'quizzes'] as $type) {
            $response = $this->actingAs($admin)->get('/reports/export?type=' . $type);

            $response->assertOk();
            $this->assertStringContainsString(
                'text/csv',
                $response->headers->get('content-type') ?? ''
            );

            $content = $response->streamedContent();
            $this->assertStringStartsWith("\xEF\xBB\xBF", $content, 'CSV should start with a UTF-8 BOM.');
            $this->assertNotEmpty(trim($content), 'CSV should contain a header row.');
        }
    }

    public function test_reports_export_respects_the_date_filter(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get('/reports/export?type=courses&from=2020-01-01&to=2020-01-31');
        $response->assertOk();

        $lines = array_filter(explode("\n", trim($response->streamedContent())));
        $this->assertGreaterThanOrEqual(1, count($lines));
    }

    public function test_learner_cannot_export_reports(): void
    {
        [$learner] = $this->learnerWithOpenModule();

        $this->actingAs($learner)
            ->get('/reports/export?type=courses')
            ->assertForbidden();
    }

    public function test_dashboard_shows_resume_card_for_an_incomplete_module(): void
    {
        [$learner, $module] = $this->learnerWithOpenModule();

        $this->actingAs($learner)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Continue learning')
            ->assertSee($module->title);
    }
}
