<?php

namespace App\Http\Controllers;

use App\Models\AssignedModule;
use App\Models\ModuleCompletionApproval;
use App\Models\TrainingModule;
use App\Notifications\WorkflowNotification;
use App\Services\CertificateService;
use App\Services\ProgressService;
use Illuminate\Http\Request;

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
     * Learner requests module completion approval after finishing every task.
     */
    public function request(Request $request, TrainingModule $module)
    {
        $user = $request->user();

        $assignment = AssignedModule::where('training_module_id', $module->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $assignment) {
            return redirect()->back()->with('error', 'You are not enrolled in this module.');
        }

        // Gate on the task-based calculation, not a lesson/quiz split.
        $summary = app(ProgressService::class)->summary($user->id, $module->id);

        if ($summary['total_tasks'] === 0) {
            return redirect()->back()->with('error', 'This module has no lessons or quizzes yet.');
        }

        if ($summary['completed_tasks'] < $summary['total_tasks']) {
            return redirect()->back()->with(
                'error',
                "You still have {$summary['remaining_tasks']} task(s) to finish: "
                . "{$summary['completed_lessons']}/{$summary['total_lessons']} lessons approved, "
                . "{$summary['completed_quizzes']}/{$summary['total_quizzes']} quizzes passed."
            );
        }

        $approval = ModuleCompletionApproval::firstOrNew([
            'training_module_id' => $module->id,
            'user_id' => $user->id,
        ]);

        if ($approval->exists && $approval->status === 'approved') {
            return redirect()->back()->with('status', 'This module has already been approved and completed.');
        }

        if ($approval->exists && $approval->status === 'pending') {
            return redirect()->back()->with('status', 'You already have a pending completion request for this module.');
        }

        // New request, or a fresh attempt after a rejection.
        $approval->status = 'pending';
        $approval->approved_by = null;
        $approval->approved_at = null;
        $approval->notes = null;
        $approval->save();

        $assignment = AssignedModule::where('training_module_id', $module->id)
            ->where('user_id', $user->id)
            ->with('assigner')
            ->first();

        $reviewer = $assignment?->assigner ?? $module->creator;

        if ($reviewer) {
            $reviewer->notify(new \App\Notifications\ModuleCompletionApprovalRequestNotification($module, $user));
        }

        return redirect()->back()->with('status', 'Module completion request submitted. Waiting for instructor approval.');
    }

    /**
     * Show pending module completion approvals.
     */
    public function index(Request $request)
    {
        $query = ModuleCompletionApproval::with(['module', 'learner'])
            ->where('status', 'pending')
            ->latest();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('learner', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('module', function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%");
                });
            });
        }

        $approvals = $query->paginate(20)->appends(['search' => $search ?? null]);

        return view('module_approvals.index', compact('approvals'));
    }

    /**
     * Approve a completion, mark the assignment completed and issue the certificate.
     */
    public function approve(Request $request, ModuleCompletionApproval $approval)
    {
        if ($approval->status !== 'pending') {
            return redirect()->back()->with('status', 'This approval request has already been processed.');
        }

        $data = $request->validate([
            'valid_until' => ['nullable', 'date', 'after:today'],
        ]);

        $approval->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $module = $approval->module;
        $learner = $approval->learner;

        AssignedModule::where('training_module_id', $module->id)
            ->where('user_id', $learner->id)
            ->update(['status' => 'completed']);

        // Recalculate from tasks, then treat approval as the final state.
        $progress = app(ProgressService::class)->recalculate($learner->id, $module->id);

        if ((float) $progress->percentage < 100) {
            $progress->percentage = 100;
            $progress->save();
        }

        app(CertificateService::class)->issue(
            $learner,
            $module,
            $request->user()->id,
            filled($data['valid_until'] ?? null) ? \Illuminate\Support\Carbon::parse($data['valid_until']) : null,
        );

        $learner->notify(new WorkflowNotification(
            type: 'module_approved',
            message: "Your completion of '{$module->title}' was approved. Your certificate is ready.",
            url: route('certificates.index'),
        ));

        return redirect()->back()->with('status', 'Module completion approved and certificate issued.');
    }

    /**
     * Reject a completion request. A reason is required so the learner knows
     * what to fix before requesting again.
     */
    public function reject(Request $request, ModuleCompletionApproval $approval)
    {
        if ($approval->status !== 'pending') {
            return redirect()->back()->with('status', 'This approval request has already been processed.');
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'reason.required' => 'Please give the learner a reason for the rejection.',
        ]);

        $approval->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'notes' => $data['reason'],
        ]);

        $approval->learner->notify(new WorkflowNotification(
            type: 'module_rejected',
            message: "Your completion of '{$approval->module->title}' was not approved: {$data['reason']}",
            url: route('modules.show', $approval->module),
        ));

        return redirect()->back()->with('status', 'Module completion request rejected. The learner has been notified.');
    }
}
