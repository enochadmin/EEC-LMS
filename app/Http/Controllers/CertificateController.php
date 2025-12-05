<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;


class CertificateController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view certificates');
    }

    public function index(Request $request)
    {
        $query = Certificate::with(['module', 'user'])
            ->where('user_id', $request->user()->id)
            ->latest();
        
        if ($search = $request->get('search')) {
            $query->whereHas('module', function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }
        
        $certificates = $query->paginate(10)->appends(['search' => $search]);

        return view('certificates.index', compact('certificates'));
    }

    public function adminIndex(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->hasRole('admin');

        $query = Certificate::with(['module', 'user', 'issuer'])->latest();

        // Admin: see all certificates; Instructor/manager: only those they issued
        if (! $isAdmin) {
            $query->where('issued_by', $user->id);
        }

        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                })->orWhereHas('module', function($mq) use ($search) {
                    $mq->where('title', 'like', "%{$search}%");
                });
            });
        }

        $certificates = $query->paginate(20)->appends(['search' => $search]);

        return view('certificates.admin', compact('certificates', 'isAdmin'));
    }

    public function issue(Request $request, \App\Models\User $user, \App\Models\TrainingModule $module)
    {
        // Check if certificate already exists
        $existing = Certificate::where('user_id', $user->id)
            ->where('training_module_id', $module->id)
            ->first();

        if ($existing) {
            return redirect()->back()->with('status', 'Certificate already issued for this user and module.');
        }

        // Create Certificate
        $certificate = Certificate::create([
            'user_id' => $user->id,
            'issued_by' => $request->user()->id,
            'training_module_id' => $module->id,
            'certificate_number' => 'CERT-' . strtoupper(uniqid()),
            'file_path' => 'certificates/' . uniqid() . '.pdf',
            'issued_at' => now(),
        ]);

        // Generate PDF
        $this->generatePdf($certificate);

        // Notify User
        $user->notify(new \App\Notifications\CertificateIssuedNotification($certificate));

        return redirect()->back()->with('status', 'Certificate issued successfully.');
    }

    public function download(Request $request, Certificate $certificate)
    {
        abort_unless(
            $certificate->user_id === $request->user()->id || $request->user()->can('issue certificates'),
            403
        );

        // Generate PDF if it doesn't exist
        if (!Storage::disk('public')->exists($certificate->file_path)) {
            $this->generatePdf($certificate);
        }

        $filename = "Certificate-{$certificate->certificate_number}.pdf";

        return Storage::disk('public')->download($certificate->file_path, $filename);
    }

    public function destroy(Certificate $certificate)
    {
        if (Storage::disk('public')->exists($certificate->file_path)) {
            Storage::disk('public')->delete($certificate->file_path);
        }

        $certificate->delete();

        return redirect()->back()->with('status', 'Certificate revoked successfully.');
    }

    private function generatePdf(Certificate $certificate)
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('certificates.pdf', compact('certificate'));
        $pdf->setPaper('a4', 'landscape');
        
        // Ensure directory exists
        if (!Storage::disk('public')->exists('certificates')) {
            Storage::disk('public')->makeDirectory('certificates');
        }

        Storage::disk('public')->put($certificate->file_path, $pdf->output());
    }
}
