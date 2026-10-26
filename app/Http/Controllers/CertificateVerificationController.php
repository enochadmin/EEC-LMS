<?php

namespace App\Http\Controllers;

use App\Models\Certificate;

/**
 * Public certificate lookup — no login required.
 *
 * Someone holding a printed certificate (or its number) can confirm it is
 * genuine, who it belongs to, and whether it is still valid. This is what
 * makes the PDF evidence in an audit rather than a picture.
 */
class CertificateVerificationController extends Controller
{
    public function show(string $number)
    {
        $normalised = strtoupper(trim($number));

        $certificate = Certificate::with(['user', 'module', 'issuer'])
            ->where('certificate_number', $normalised)
            ->first();

        $status = 'not_found';

        if ($certificate) {
            $status = ($certificate->valid_until && $certificate->valid_until->isPast())
                ? 'expired'
                : 'valid';
        }

        return response()
            ->view('certificates.verify', [
                'certificate' => $certificate,
                'status' => $status,
                'number' => $normalised,
            ])
            // A verification page should not be indexed or cached.
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
