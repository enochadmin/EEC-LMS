<?php

namespace App\Http\Controllers;

use App\Models\LessonCompletion;
use Illuminate\Http\Request;

class LessonCompletionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage users|manage modules');
    }

    public function approve(LessonCompletion $completion)
    {
        $completion->update(['status' => 'approved']);

        // Recalculate progress
        $module = $completion->module;
        $user = $completion->user;

        $completedCount = LessonCompletion::where('training_module_id', $module->id)
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->count();

        $totalLessons = $module->lessons()->count();

        // Lessons contribute 50% of total module score
        $lessonPortion = 50;
        $lessonPercentage = $totalLessons > 0 ? ($completedCount / $totalLessons) * $lessonPortion : 0;

        $progress = \App\Models\ModuleProgress::firstOrNew([
                'training_module_id' => $module->id,
                'user_id' => $user->id,
        ]);

        $progress->completed_lessons = $completedCount;
        $progress->total_lessons = $totalLessons;
        $progress->lesson_percentage = $lessonPercentage;
        $progress->quiz_percentage = $progress->quiz_percentage ?? 0;
        $progress->percentage = round(($progress->lesson_percentage ?? 0) + ($progress->quiz_percentage ?? 0), 1);
        $progress->last_accessed_at = now();
        $progress->save();

        // Notify learner about approval
        $completion->user->notify(new \App\Notifications\LessonApprovedNotification($completion));

        // Check if all lessons in the module are completed (approved)
        $module = $completion->module;
        $totalLessons = $module->lessons()->count();
        $completedLessons = \App\Models\LessonCompletion::where('training_module_id', $module->id)
            ->where('user_id', $completion->user_id)
            ->where('status', 'approved')
            ->count();

        if ($totalLessons > 0 && $totalLessons === $completedLessons) {
            // Ensure quizzes (if any) are passed before creating a module completion approval request
            $quizIds = $module->quizzes()->pluck('id');
            $hasQuiz = $quizIds->isNotEmpty();
            $hasPassedQuiz = false;

            if ($hasQuiz) {
                $hasPassedQuiz = \App\Models\QuizAttempt::where('user_id', $completion->user_id)
                    ->whereIn('quiz_id', $quizIds)
                    ->where('status', 'passed')
                    ->exists();
            }

            if (! $hasQuiz || $hasPassedQuiz) {
                // Create a pending module completion approval if not already present
                $approval = \App\Models\ModuleCompletionApproval::firstOrCreate(
                    [
                        'training_module_id' => $module->id,
                        'user_id' => $completion->user_id,
                    ],
                    [
                        'status' => 'pending',
                    ]
                );

                if ($approval->wasRecentlyCreated) {
                    // Notify instructor / manager about module completion approval request
                    $assignment = \App\Models\AssignedModule::where('training_module_id', $module->id)
                        ->where('user_id', $completion->user_id)
                        ->with('assigner')
                        ->first();

                    $notifiable = $assignment?->assigner ?? $module->creator;
                    if ($notifiable) {
                        $notifiable->notify(new \App\Notifications\ModuleCompletionApprovalRequestNotification($module, $completion->user));
                    }
                }
            }
        }

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'status' => 'Lesson completion approved.',
                'module_id' => $module->id,
                'percentage' => $progress->percentage ?? null,
            ]);
        }

        return redirect()->back()->with('status', 'Lesson completion approved.');
    }

    public function reject(LessonCompletion $completion)
    {
        // Delete the completion record so user can try again
        $completion->delete();

        return redirect()->back()->with('status', 'Lesson completion rejected.');
    }
}
