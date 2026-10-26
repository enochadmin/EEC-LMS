<?php

namespace App\Services;

use App\Models\AssignedModule;
use App\Models\LessonCompletion;
use App\Models\ModuleProgress;
use App\Models\QuizAttempt;
use App\Models\TrainingModule;

/**
 * Single source of truth for module progress.
 *
 * A module is measured in *tasks*: every lesson and every quiz is one task.
 *
 *   - a lesson task is done when the learner's completion row is approved
 *   - a quiz task   is done when the learner has at least one passed attempt
 *
 *   percentage = completed tasks / total tasks * 100
 *
 * Every write path (lesson submit, lesson review, quiz submit, module approval,
 * retake reset, enrollment) delegates here. No controller may compute progress
 * on its own — that is what caused the previous 50/50 split, which capped any
 * quiz-less module at 50% and credited learners who had done nothing.
 */
class ProgressService
{
    public function summary(int $userId, int $moduleId): array
    {
        $module = TrainingModule::find($moduleId);

        if (! $module) {
            return $this->emptySummary();
        }

        $lessonIds = $module->lessons()->pluck('id');
        $quizIds = $module->quizzes()->pluck('id');

        $totalLessons = $lessonIds->count();
        $totalQuizzes = $quizIds->count();

        $completedLessons = $lessonIds->isEmpty()
            ? 0
            : LessonCompletion::where('user_id', $userId)
                ->whereIn('lesson_id', $lessonIds)
                ->where('status', 'approved')
                ->count();

        $completedQuizzes = $quizIds->isEmpty()
            ? 0
            : QuizAttempt::where('user_id', $userId)
                ->whereIn('quiz_id', $quizIds)
                ->where('status', 'passed')
                ->distinct()
                ->count('quiz_id');

        // Defensive: a duplicated completion row must never push a learner
        // past the number of tasks that actually exist.
        $completedLessons = min($completedLessons, $totalLessons);
        $completedQuizzes = min($completedQuizzes, $totalQuizzes);

        $totalTasks = $totalLessons + $totalQuizzes;
        $completedTasks = $completedLessons + $completedQuizzes;

        return [
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completedLessons,
            'total_quizzes' => $totalQuizzes,
            'completed_quizzes' => $completedQuizzes,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'remaining_tasks' => max($totalTasks - $completedTasks, 0),
            'lesson_percentage' => $totalLessons > 0
                ? min(100.0, round($completedLessons / $totalLessons * 100, 2))
                : 0.0,
            'quiz_percentage' => $totalQuizzes > 0
                ? min(100.0, round($completedQuizzes / $totalQuizzes * 100, 2))
                : 0.0,
            'percentage' => $totalTasks > 0
                ? min(100.0, round($completedTasks / $totalTasks * 100, 2))
                : 0.0,
            'is_complete' => $totalTasks > 0 && $completedTasks >= $totalTasks,
        ];
    }

    /**
     * Recompute and persist progress for one learner on one module.
     */
    public function recalculate(int $userId, int $moduleId): ModuleProgress
    {
        $summary = $this->summary($userId, $moduleId);

        $enrolled = AssignedModule::where('user_id', $userId)
            ->where('training_module_id', $moduleId)
            ->exists();

        $progress = ModuleProgress::firstOrNew([
            'training_module_id' => $moduleId,
            'user_id' => $userId,
        ]);

        $progress->completed_lessons = $summary['completed_lessons'];
        $progress->total_lessons = $summary['total_lessons'];
        $progress->completed_quizzes = $summary['completed_quizzes'];
        $progress->total_quizzes = $summary['total_quizzes'];
        $progress->lesson_percentage = $summary['lesson_percentage'];
        $progress->quiz_percentage = $summary['quiz_percentage'];
        $progress->percentage = $summary['percentage'];
        $progress->computed_at = now();
        $progress->last_accessed_at = $progress->last_accessed_at ?? now();

        // Staff previewing a module are not learners: return the computed
        // numbers without writing a row, so reports and rates stay honest.
        if (! $enrolled) {
            return $progress;
        }

        $progress->save();

        return $progress;
    }

    /**
     * Recompute every learner that is attached to a module (enrolled or with a
     * progress row). Used when content is added/removed or a module is backfilled.
     */
    public function recalculateForModule(int $moduleId): int
    {
        $userIds = AssignedModule::where('training_module_id', $moduleId)->pluck('user_id')
            ->merge(
                ModuleProgress::where('training_module_id', $moduleId)->pluck('user_id')
            )
            ->unique()
            ->values();

        foreach ($userIds as $userId) {
            $this->recalculate($userId, $moduleId);
        }

        return $userIds->count();
    }

    /**
     * Recompute every progress row in the system (one-off repair / backfill).
     */
    public function recalculateAll(): int
    {
        // Query builder (not Eloquent) so merge() appends instead of collapsing
        // models that share a null primary key.
        $pairs = \Illuminate\Support\Facades\DB::table('module_progress')
            ->select('user_id', 'training_module_id')
            ->get()
            ->merge(
                \Illuminate\Support\Facades\DB::table('assigned_modules')
                    ->select('user_id', 'training_module_id')
                    ->get()
            )
            ->unique(fn ($row) => $row->user_id . ':' . $row->training_module_id);

        foreach ($pairs as $pair) {
            $this->recalculate((int) $pair->user_id, (int) $pair->training_module_id);
        }

        return $pairs->count();
    }

    protected function emptySummary(): array
    {
        return [
            'total_lessons' => 0,
            'completed_lessons' => 0,
            'total_quizzes' => 0,
            'completed_quizzes' => 0,
            'total_tasks' => 0,
            'completed_tasks' => 0,
            'remaining_tasks' => 0,
            'lesson_percentage' => 0.0,
            'quiz_percentage' => 0.0,
            'percentage' => 0.0,
            'is_complete' => false,
        ];
    }
}
