<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\TrainingModule;
use App\Models\User;
use App\Notifications\CertificateIssuedNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Issues module certificates: sequential numbering, PDF generation, notification.
 *
 * Numbering is EEC-<year>-<sequence>, e.g. EEC-2026-0001. The sequence restarts
 * each calendar year and is derived from the certificates already issued for
 * that year.
 */
class CertificateService
{
    public const PREFIX = 'EEC';

    /**
     * Issue a certificate for a learner/module pair. Returns the existing record
     * when one is already present.
     */
    public function issue(
        User $user,
        TrainingModule $module,
        ?int $issuedBy = null,
        ?Carbon $validUntil = null,
    ): Certificate {
        $existing = Certificate::where('user_id', $user->id)
            ->where('training_module_id', $module->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $certificate = Certificate::create([
            'user_id' => $user->id,
            'issued_by' => $issuedBy,
            'training_module_id' => $module->id,
            'certificate_number' => $this->nextNumber(),
            'file_path' => 'certificates/' . uniqid('cert-', true) . '.pdf',
            'issued_at' => now(),
            'valid_until' => $validUntil,
        ]);

        $this->generatePdf($certificate);

        $user->notify(new CertificateIssuedNotification($certificate));

        return $certificate;
    }

    /**
     * Next number in the current year's sequence, e.g. EEC-2026-0001.
     */
    public function nextNumber(?int $year = null): string
    {
        $year = $year ?? (int) now()->year;
        $prefix = self::PREFIX . '-' . $year . '-';

        $last = Certificate::where('certificate_number', 'like', $prefix . '%')
            ->orderByDesc('certificate_number')
            ->value('certificate_number');

        $next = 1;

        if ($last) {
            $next = ((int) substr($last, strlen($prefix))) + 1;
        }

        do {
            $candidate = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $taken = Certificate::where('certificate_number', $candidate)->exists();
            $next++;
        } while ($taken);

        return $candidate;
    }

    public function generatePdf(Certificate $certificate): void
    {
        $certificate->loadMissing(['user', 'module', 'issuer']);

        // The issuing instructor's own signature wins; otherwise use the
        // default configured by the admin.
        $signatory = \App\Models\Setting::signatory();

        if ($certificate->issuer?->signature_path) {
            $signatory['signature_path'] = $certificate->issuer->signature_path;
        }

        $signatory['name'] = $signatory['name'] ?: $certificate->issuer?->name;

        $pdf = Pdf::loadView('certificates.pdf', [
            'certificate' => $certificate,
            'signatory' => $signatory,
        ]);
        $pdf->setPaper('a4', 'landscape');

        if (! Storage::disk('public')->exists('certificates')) {
            Storage::disk('public')->makeDirectory('certificates');
        }

        Storage::disk('public')->put($certificate->file_path, $pdf->output());
    }
}
