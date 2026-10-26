<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\RetakeRequest;
use App\Models\TrainingModule;
use App\Models\Quiz;
use App\Models\Lesson;

class RetakeRequestController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'requestable_type' => ['required', 'string', 'in:quiz,lesson,module'],
            'requestable_id' => ['required', 'integer'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $typeMap = [
            'quiz' => Quiz::class,
            'lesson' => Lesson::class,
            'module' => TrainingModule::class,
        ];

        $modelClass = $typeMap[$data['requestable_type']];
        $model = $modelClass::findOrFail($data['requestable_id']);

        $retakeRequest = RetakeRequest::firstOrCreate(
            [
                'user_id' => auth()->id(),
                'requestable_type' => $modelClass,
                'requestable_id' => $model->id,
                'status' => 'pending',
            ],
            [
                'reason' => $data['reason'],
            ]
        );

        // Notify instructor (Assigner or Creator)
        // We need to find the module context.
        $module = null;
        if ($model instanceof TrainingModule) {
            $module = $model;
        } elseif ($model instanceof Quiz || $model instanceof Lesson) {
            $module = $model->module;
        }

        if ($module) {
            $assignment = \App\Models\AssignedModule::where('training_module_id', $module->id)
                ->where('user_id', auth()->id())
                ->with('assigner')
                ->first();

            $notifiable = $assignment?->assigner ?? $module->creator;
            
            if ($notifiable && $retakeRequest->wasRecentlyCreated) {
                $notifiable->notify(new \App\Notifications\RetakeRequestNotification($retakeRequest));
                \Illuminate\Support\Facades\Log::info("Retake requested by user " . auth()->id() . " for " . $data['requestable_type'] . " " . $model->id);
            }
        }

        return redirect()->back()->with('status', 'Retake request submitted successfully. Waiting for approval.');
    }

    /**
     * List requests for instructor
     */
    public function index(Request $request)
    {
        $query = RetakeRequest::with(['user', 'requestable'])
            ->where('status', 'pending')
            ->latest();
        
        if ($search = $request->get('search')) {
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }
        
        $requests = $query->paginate(20)->appends(['search' => $search]);

        // Lesson completions are reviewed on their own page (lesson-approvals),
        // not in this retake queue.
        return view('retake_requests.index', compact('requests'));
    }

    /**
     * Approve or Reject
     */
    public function update(Request $request, RetakeRequest $retakeRequest)
    {
        // Allow managers/instructors (assign modules) and admins (manage users)
        if (! $request->user()->can('manage users') && ! $request->user()->can('assign modules')) {
            abort(403);
        }

        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
        ]);

        $retakeRequest->update([
            'status' => $data['status'],
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        if ($data['status'] === 'approved') {
            // Reset the progress/completion for the item
            $this->resetProgress($retakeRequest);

            // Rebuild progress from whatever is still completed.
            $item = $retakeRequest->requestable;
            $moduleId = $item instanceof \App\Models\TrainingModule
                ? $item->id
                : ($item?->module?->id);

            if ($moduleId) {
                app(\App\Services\ProgressService::class)->recalculate($retakeRequest->user_id, $moduleId);
            }
        }

        // Notify learner about response
        $retakeRequest->user->notify(new \App\Notifications\RetakeResponseNotification($retakeRequest));

        return redirect()->back()->with('status', 'Request ' . $data['status']);
    }

    private function resetProgress(RetakeRequest $req)
    {
        $user = $req->user;
        $item = $req->requestable;

        if ($item instanceof Quiz) {
            // Delete user answers for this quiz
            \App\Models\UserAnswer::where('user_id', $user->id)
                ->whereIn('question_id', $item->questions->pluck('id'))
                ->delete();
        } elseif ($item instanceof Lesson) {
            // Delete lesson completion
            \App\Models\LessonCompletion::where('user_id', $user->id)
                ->where('lesson_id', $item->id)
                ->delete();
            
            // Recalculate module progress... (simplified for now)
        } elseif ($item instanceof TrainingModule) {
            // Reset assignment status
            \App\Models\AssignedModule::where('user_id', $user->id)
                ->where('training_module_id', $item->id)
                ->update(['status' => 'in_progress']);
            
            // Optionally clear all lesson completions?
            // For now, let's just unlock the status.
        }
    }
}
