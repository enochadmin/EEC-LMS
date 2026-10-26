<?php

namespace App\Http\Controllers;

use App\Models\LessonCompletion;
use App\Models\ModuleCompletionApproval;
use App\Notifications\ModuleCompletionApprovalRequestNotification;
use App\Notifications\WorkflowNotification;
use App\Services\ProgressService;
use Illuminate\Http\Request;

class LessonCompletionController extends Controller
{
    public function __construct()
    {
        // Anyone who can review a module can review its lesson submissions.
        $this->middleware('permission:manage users|manage modules|assign modules|issue certificates');
    }

    /**
     * Dedicated review queue for lesson completions.
     *
     * This used to sit inside the retake requests page. The two queues mean
     * different things (a retake hands work back, an approval signs it off),
     * so lesson approvals now have their own page.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search'));

        $status = (string) $request->get('status', 'pending');
        if (! in_array($status, ['pending', 'approved', 'rejected', 'all'], true)) {
            $status = 'pending';
        }

        $query = LessonCompletion::with(['user', 'lesson', 'module', 'reviewer'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                        ->orWhereHas('lesson', fn ($l) => $l->where('title', 'like', "%{$search}%"))
                        ->orWhereHas('module', fn ($m) => $m->where('title', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('created_at');

        $completions = $query->paginate(20)->appends([
            'search' => $search,
            'status' => $status,
        ]);

        $counts = [
            'pending' => LessonCompletion::where('status', 'pending')->count(),
            'approved' => LessonCompletion::where('status', 'approved')->count(),
            'rejected' => LessonCompletion::where('status', 'rejected')->count(),
        ];

        return view('lesson_approvals.index', compact('completions', 'counts', 'status', 'search'));
    }

    /**
     * Approve a lesson completion. Progress is recalculated from tasks, never
     * from a fixed lesson/quiz split.
     */
    public function approve(Request $request, LessonCompletion $completion)
    {
        if ($completion->status === 'approved') {
            return $this->respond($request, 'This lesson is already approved.');
        }

        $completion->update([
            'status' => 'approved',
            'rejection_reason' => null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $progress = app(ProgressService::class)
            ->recalculate($completion->user_id, $completion->training_module_id);

        $completion->user->notify(new WorkflowNotification(
            type: 'lesson_approved',
            message: "Your completion of '{$completion->lesson->title}' was approved.",
            url: route('modules.lessons.show', [$completion->module, $completion->lesson]),
        ));

        // When every task is done, open the module completion review automatically.
        $this->openModuleReviewIfComplete($completion, $progress);

        return $this->respond($request, 'Lesson completion approved.', [
            'module_id' => $completion->training_module_id,
            'percentage' => $progress->percentage,
        ]);
    }

    /**
     * Reject a lesson completion. The row is kept (with the reason) so the
     * learner can see why and resubmit.
     */
    public function reject(Request $request, LessonCompletion $completion)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'reason.required' => 'Please give the learner a reason for the rejection.',
        ]);

        $completion->update([
            'status' => 'rejected',
            'rejection_reason' => $data['reason'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        // Progress drops back if the lesson had already been counted.
        app(ProgressService::class)
            ->recalculate($completion->user_id, $completion->training_module_id);

        $completion->user->notify(new WorkflowNotification(
            type: 'lesson_rejected',
            message: "Your completion of '{$completion->lesson->title}' was sent back: {$data['reason']}",
            url: route('modules.lessons.show', [$completion->module, $completion->lesson]),
        ));

        return $this->respond($request, 'Lesson completion rejected. The learner has been notified.');
    }

    /**
     * Create (or reopen) the module completion review once all tasks are done.
     */
    protected function openModuleReviewIfComplete(LessonCompletion $completion, $progress): void
    {
        if (! $progress || (float) $progress->percentage < 100) {
            return;
        }

        $module = $completion->module;

        $approval = ModuleCompletionApproval::firstOrNew([
            'training_module_id' => $module->id,
            'user_id' => $completion->user_id,
        ]);

        if ($approval->exists && in_array($approval->status, ['pending', 'approved'], true)) {
            return;
        }

        $approval->status = 'pending';
        $approval->approved_by = null;
        $approval->approved_at = null;
        $approval->notes = null;
        $approval->save();

        $assignment = \App\Models\AssignedModule::where('training_module_id', $module->id)
            ->where('user_id', $completion->user_id)
            ->with('assigner')
            ->first();

        $reviewer = $assignment?->assigner ?? $module->creator;

        if ($reviewer) {
            $reviewer->notify(new ModuleCompletionApprovalRequestNotification($module, $completion->user));
        }
    }

    protected function respond(Request $request, string $message, array $extra = [])
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(array_merge(['status' => $message], $extra));
        }

        return redirect()->back()->with('status', $message);
    }
}
