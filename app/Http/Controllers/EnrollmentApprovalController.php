<?php

namespace App\Http\Controllers;

use App\Models\AssignedModule;
use App\Models\EnrollmentRequest;
use App\Models\TrainingModule;
use Illuminate\Http\Request;

class EnrollmentApprovalController extends Controller
{
    public function __construct()
    {
        // Instructors/managers who can assign modules or manage users
        $this->middleware('permission:assign modules|manage users');
    }

    /**
     * List pending enrollment approval requests for the current approver.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->hasRole('admin');

        $query = EnrollmentRequest::with(['user', 'module.creator'])
            ->where('status', 'pending')
            ->latest();

        if (! $isAdmin) {
            // Limit to modules created by this instructor/manager
            $query->whereHas('module', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        }

        if ($search = $request->get('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhereHas('module', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        $requests = $query->paginate(20)->appends(['search' => $search ?? null]);

        return view('enrollment_approvals.index', compact('requests', 'isAdmin'));
    }

    /**
     * Approve a pending enrollment.
     */
    public function approve(Request $request, EnrollmentRequest $enrollmentRequest)
    {
        if ($enrollmentRequest->status !== 'pending') {
            return redirect()->back()->with('status', 'This enrollment request has already been processed.');
        }

        $user = $request->user();
        $module = TrainingModule::findOrFail($enrollmentRequest->training_module_id);

        // Create the enrollment (a unique index makes this idempotent).
        $assignment = AssignedModule::firstOrCreate(
            [
                'training_module_id' => $module->id,
                'user_id' => $enrollmentRequest->user_id,
            ],
            [
                'assigned_by' => $user->id,
                'due_date' => null,
                'status' => 'in_progress',
            ]
        );

        // Initialise progress from the module's task list.
        app(\App\Services\ProgressService::class)->recalculate($assignment->user_id, $module->id);

        $assignment->user->notify(new \App\Notifications\WorkflowNotification(
            type: 'enrollment_approved',
            message: "Your enrollment in '{$module->title}' was approved.",
            url: route('modules.show', $module),
        ));

        $enrollmentRequest->update(['status' => 'approved']);

        return redirect()->back()->with('status', 'Enrollment request approved.');
    }

    /**
     * Reject a pending enrollment.
     */
    public function reject(Request $request, EnrollmentRequest $enrollmentRequest)
    {
        if ($enrollmentRequest->status !== 'pending') {
            return redirect()->back()->with('status', 'This enrollment request has already been processed.');
        }

        $enrollmentRequest->update(['status' => 'rejected']);

        return redirect()->back()->with('status', 'Enrollment request rejected.');
    }
}


