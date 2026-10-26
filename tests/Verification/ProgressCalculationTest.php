<?php

namespace Tests\Verification;

use App\Models\AssignedModule;
use App\Models\ModuleProgress;
use App\Models\TrainingModule;
use App\Models\User;
use App\Services\ProgressService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Guards the "200% completion" defect.
 *
 * Root cause: module_progress rows existed for people who were never enrolled
 * (staff browsing a module), and completion rates divided those rows by the
 * number of *enrolled* learners.
 */
class ProgressCalculationTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('admin')->firstOrFail();
    }

    /** A module the given user is NOT assigned to. */
    private function moduleUserIsNotEnrolledIn(User $user): TrainingModule
    {
        $module = TrainingModule::whereNotIn(
            'id',
            AssignedModule::where('user_id', $user->id)->pluck('training_module_id')
        )->first();

        $this->assertNotNull($module, 'Expected at least one module without an enrolment for this user.');

        return $module;
    }

    public function test_progress_is_not_persisted_for_a_non_enrolled_viewer(): void
    {
        $admin = $this->admin();
        $module = $this->moduleUserIsNotEnrolledIn($admin);

        // Start from a clean slate for this pair.
        ModuleProgress::where('user_id', $admin->id)
            ->where('training_module_id', $module->id)
            ->delete();

        app(ProgressService::class)->recalculate($admin->id, $module->id);

        $this->assertDatabaseMissing('module_progress', [
            'user_id' => $admin->id,
            'training_module_id' => $module->id,
        ]);
    }

    public function test_recalculate_still_persists_for_an_enrolled_learner(): void
    {
        $assignment = AssignedModule::firstOrFail();

        $progress = app(ProgressService::class)
            ->recalculate($assignment->user_id, $assignment->training_module_id);

        $this->assertTrue($progress->exists);
        $this->assertDatabaseHas('module_progress', [
            'user_id' => $assignment->user_id,
            'training_module_id' => $assignment->training_module_id,
        ]);
    }

    public function test_report_rates_never_exceed_one_hundred_percent(): void
    {
        $admin = $this->admin();

        // Reproduce the original defect: an orphan row at 100% for a user who
        // is not enrolled, on a module that has exactly one real learner.
        $assignment = AssignedModule::select('training_module_id')
            ->groupBy('training_module_id')
            ->havingRaw('COUNT(DISTINCT user_id) = 1')
            ->firstOrFail();

        ModuleProgress::updateOrCreate(
            [
                'user_id' => $admin->id,
                'training_module_id' => $assignment->training_module_id,
            ],
            [
                'completed_lessons' => 1,
                'total_lessons' => 1,
                'completed_quizzes' => 0,
                'total_quizzes' => 0,
                'lesson_percentage' => 100,
                'quiz_percentage' => 0,
                'percentage' => 100,
                'computed_at' => now(),
            ]
        );

        $response = $this->actingAs($admin)->get('/reports');
        $response->assertOk();

        $response->assertViewHas('courses', function ($courses) {
            foreach ($courses as $course) {
                if ($course['completion_rate'] > 100 || $course['avg_progress'] > 100) {
                    return false;
                }
            }

            return true;
        });

        $response->assertViewHas('kpis', fn ($kpis) => $kpis['completion_rate'] <= 100);
    }

    public function test_admin_dashboard_completion_rate_is_capped(): void
    {
        $response = $this->actingAs($this->admin())->get('/dashboard');
        $response->assertOk();

        $response->assertViewHas('adminStats', function ($stats) {
            $modules = $stats['top_modules'] ?? collect();

            foreach ($modules as $module) {
                if ($module['completion_rate'] > 100) {
                    return false;
                }
            }

            return true;
        });
    }

    public function test_stored_percentages_are_never_above_one_hundred(): void
    {
        $this->assertSame(
            0,
            ModuleProgress::where('percentage', '>', 100)
                ->orWhere('lesson_percentage', '>', 100)
                ->orWhere('quiz_percentage', '>', 100)
                ->count(),
            'Stored progress must be capped at 100.'
        );
    }
}
