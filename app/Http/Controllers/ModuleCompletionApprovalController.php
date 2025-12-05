<?php

namespace App\Http\Controllers;

use App\Models\ModuleCompletionApproval;
use App\Models\Certificate;
use App\Models\TrainingModule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ModuleCompletionApprovalController extends Controller
{
    public function __construct()
    {
        // Instructors/managers who can assign modules or issue certificates can approve completions
        $this->middleware('permission:assign modules|issue certificates|manage users')->only(['index', 'approve', 'reject']);
        // Learners can submit their own completion request
        $this->middleware('role:learner')->only(['request']);
    }

    /**
     * Learner requests module completion approval after finishing lessons & quizzes.
     */
    public function request(Request $request, TrainingModule $module)
    {
        $user = $request->user();

        // Ensure learner is enrolled/assigned
        $assignment = \App\Models\AssignedModule::where('training_module_id', $module->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $assignment) {
            return redirect()->back()->with('error', 'You are not enrolled in this module.');
        }

        // Ensure all lessons are approved
        $totalLessons = $module->lessons()->count();
        $completedLessons = \App\Models\LessonCompletion::where('training_module_id', $module->id)
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->count();

        if ($totalLessons > 0 && $completedLessons < $totalLessons) {
            return redirect()->back()->with('error', 'Please complete and get approval for all lessons before requesting module completion.');
        }

        // Ensure quizzes (if any) are passed
        $quizIds = $module->quizzes()->pluck('id');
        $hasQuiz = $quizIds->isNotEmpty();
        $hasPassedQuiz = false;

        if ($hasQuiz) {
            $hasPassedQuiz = \App\Models\QuizAttempt::where('user_id', $user->id)
                ->whereIn('quiz_id', $quizIds)
                ->where('status', 'passed')
                ->exists();

            if (! $hasPassedQuiz) {
                return redirect()->back()->with('error', 'You must pass the module quiz before requesting completion approval.');
            }
        }

        // Create or reuse pending approval
        $approval = ModuleCompletionApproval::firstOrCreate(
            [
                'training_module_id' => $module->id,
                'user_id' => $user->id,
            ],
            [
                'status' => 'pending',
            ]
        );

        if (! $approval->wasRecentlyCreated && $approval->status === 'pending') {
            return redirect()->back()->with('status', 'You already have a pending completion request for this module.');
        }

        if ($approval->status === 'approved') {
            return redirect()->back()->with('status', 'This module has already been approved and completed.');
        }

        // Notify instructor/manager
        $assignment = \App\Models\AssignedModule::where('training_module_id', $module->id)
            ->where('user_id', $user->id)
            ->with('assigner')
            ->first();

        $notifiable = $assignment?->assigner ?? $module->creator;
        if ($notifiable) {
            $notifiable->notify(new \App\Notifications\ModuleCompletionApprovalRequestNotification($module, $user));
        }

        return redirect()->back()->with('status', 'Module completion request submitted. Waiting for instructor approval.');
    }

    /**
     * Show pending module completion approvals for instructors/managers.
     */
    public function index(Request $request)
    {
        $query = ModuleCompletionApproval::with(['module', 'learner'])
            ->where('status', 'pending')
            ->latest();

        if ($search = $request->get('search')) {
            $query->whereHas('learner', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhereHas('module', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        $approvals = $query->paginate(20)->appends(['search' => $search ?? null]);

        return view('module_approvals.index', compact('approvals'));
    }

    /**
     * Approve a module completion, mark assignment completed and issue certificate.
     */
    public function approve(Request $request, ModuleCompletionApproval $approval)
    {
        $this->authorize('issue certificates');

        if ($approval->status !== 'pending') {
            return redirect()->back()->with('status', 'This approval request has already been processed.');
        }

        $approval->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $module = $approval->module;
        $user = $approval->learner;

        // Mark assignment as completed if exists
        \App\Models\AssignedModule::where('training_module_id', $module->id)
            ->where('user_id', $user->id)
            ->update(['status' => 'completed']);

        // Ensure module progress is marked as fully completed for this learner
        $totalLessons = $module->lessons()->count();
        \App\Models\ModuleProgress::updateOrCreate(
            [
                'training_module_id' => $module->id,
                'user_id' => $user->id,
            ],
            [
                'completed_lessons' => $totalLessons,
                'total_lessons' => $totalLessons,
                'percentage' => 100,
                'last_accessed_at' => now(),
            ]
        );

        // Issue certificate if not already issued
        $existing = Certificate::where('user_id', $user->id)
            ->where('training_module_id', $module->id)
            ->first();

        if (! $existing) {
            $certificate = Certificate::create([
                'user_id' => $user->id,
                'training_module_id' => $module->id,
                'certificate_number' => 'CERT-' . strtoupper(uniqid()),
                'file_path' => 'certificates/' . uniqid() . '.pdf',
                'issued_at' => now(),
            ]);

            $this->generatePdf($certificate);

            // Notify learner
            $user->notify(new \App\Notifications\CertificateIssuedNotification($certificate));
        }

        return redirect()->back()->with('status', 'Module completion approved and certificate issued.');
    }

    /**
     * Reject a module completion approval request.
     */
    public function reject(Request $request, ModuleCompletionApproval $approval)
    {
        if ($approval->status !== 'pending') {
            return redirect()->back()->with('status', 'This approval request has already been processed.');
        }

        $approval->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('status', 'Module completion request rejected.');
    }

    /**
     * Generate a PDF certificate file (mirrors CertificateController::generatePdf).
     */
    protected function generatePdf(Certificate $certificate): void
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('certificates.pdf', compact('certificate'));
        $pdf->setPaper('a4', 'landscape');

        if (! Storage::disk('public')->exists('certificates')) {
            Storage::disk('public')->makeDirectory('certificates');
        }

        Storage::disk('public')->put($certificate->file_path, $pdf->output());
    }
}


