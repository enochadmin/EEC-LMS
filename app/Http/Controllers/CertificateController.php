<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\TrainingModule;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view certificates');
    }

    /**
     * The signed-in user's own certificates.
     */
    public function index(Request $request)
    {
        $query = Certificate::with(['module', 'user'])
            ->where('user_id', $request->user()->id)
            ->latest();

        if ($search = $request->get('search')) {
            $query->whereHas('module', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        $certificates = $query->paginate(10)->appends(['search' => $search]);

        return view('certificates.index', compact('certificates'));
    }

    /**
     * Certificate management: admins see everything, instructors see the
     * certificates they issued or that belong to modules they own.
     */
    public function adminIndex(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->hasRole('admin');

        $query = Certificate::with(['module', 'user', 'issuer'])->latest();

        if (! $isAdmin) {
            $query->where(function ($q) use ($user) {
                $q->where('issued_by', $user->id)
                    ->orWhereHas('module', function ($sub) use ($user) {
                        $sub->where('created_by', $user->id);
                    });
            });
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                })->orWhereHas('module', function ($mq) use ($search) {
                    $mq->where('title', 'like', "%{$search}%");
                });
            });
        }

        $certificates = $query->paginate(20)->appends(['search' => $search]);

        return view('certificates.admin', compact('certificates', 'isAdmin'));
    }

    /**
     * Issue a certificate manually. The expiry date is optional — an empty
     * value means the certificate never expires.
     */
    public function issue(Request $request, User $user, TrainingModule $module)
    {
        $data = $request->validate([
            'valid_until' => ['nullable', 'date', 'after:today'],
        ], [
            'valid_until.after' => 'The expiry date must be in the future.',
        ]);

        $existing = Certificate::where('user_id', $user->id)
            ->where('training_module_id', $module->id)
            ->first();

        if ($existing) {
            return redirect()->back()->with('status', 'Certificate already issued for this user and module.');
        }

        app(CertificateService::class)->issue(
            $user,
            $module,
            $request->user()->id,
            filled($data['valid_until'] ?? null) ? Carbon::parse($data['valid_until']) : null,
        );

        return redirect()->back()->with('status', 'Certificate issued successfully.');
    }

    public function download(Request $request, Certificate $certificate)
    {
        abort_unless(
            $certificate->user_id === $request->user()->id || $request->user()->can('issue certificates'),
            403
        );

        if (! Storage::disk('public')->exists($certificate->file_path)) {
            app(CertificateService::class)->generatePdf($certificate);
        }

        $filename = "Certificate-{$certificate->certificate_number}.pdf";

        return Storage::disk('public')->download($certificate->file_path, $filename);
    }

    /**
     * Show the PDF inline in a new browser tab instead of downloading it.
     */
    public function view(Request $request, Certificate $certificate)
    {
        abort_unless(
            $certificate->user_id === $request->user()->id || $request->user()->can('issue certificates'),
            403
        );

        if (! Storage::disk('public')->exists($certificate->file_path)) {
            app(CertificateService::class)->generatePdf($certificate);
        }

        return response()->file(Storage::disk('public')->path($certificate->file_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Certificate-' . $certificate->certificate_number . '.pdf"',
        ]);
    }

    /**
     * Revoke a certificate: remove the PDF and the record.
     */
    public function destroy(Request $request, Certificate $certificate)
    {
        if (! $request->user()->hasRole('admin') && $certificate->issued_by !== $request->user()->id) {
            abort(403);
        }

        if ($certificate->file_path && Storage::disk('public')->exists($certificate->file_path)) {
            Storage::disk('public')->delete($certificate->file_path);
        }

        $certificate->delete();

        return redirect()->back()->with('status', 'Certificate revoked successfully.');
    }
}
