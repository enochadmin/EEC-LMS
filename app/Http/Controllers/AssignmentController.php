<?php

namespace App\Http\Controllers;

use App\Models\AssignedModule;
use App\Models\TrainingModule;
use App\Models\User;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:assign modules');
    }

    public function index(TrainingModule $module)
    {
        // Get only learners for assignment
        $learners = \App\Models\User::role('learner')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        
        // Get current assignments for this module
        $assignments = \App\Models\AssignedModule::where('training_module_id', $module->id)
            ->with(['user', 'module'])
            ->latest()
            ->get();
        
        return view('assignments.index', [
            'module' => $module,
            'users' => $learners,
            'assignments' => $assignments,
        ]);
    }

    public function assign(Request $request, TrainingModule $module)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        $assignment = AssignedModule::updateOrCreate(
            [
                'training_module_id' => $module->id,
                'user_id' => $data['user_id'],
            ],
            [
                'assigned_by' => $request->user()->id,
                'due_date' => $data['due_date'] ?? null,
                'status' => 'assigned',
            ]
        );

        // Initialise progress from the module's task list.
        app(\App\Services\ProgressService::class)->recalculate($assignment->user_id, $module->id);

        // Tell the learner they were enrolled.
        if ($assignment->wasRecentlyCreated) {
            $assignment->user->notify(new \App\Notifications\WorkflowNotification(
                type: 'enrollment',
                message: "You were enrolled in '{$module->title}'.",
                url: route('modules.show', $module),
            ));
        }

        return redirect()
            ->route('modules.assign.index', $module)
            ->with('status', "Module assigned successfully.");
    }

    /**
     * Show evaluation/results for a module
     */
    public function evaluations(TrainingModule $module)
    {
        $assignments = \App\Models\AssignedModule::where('training_module_id', $module->id)
            ->with(['user', 'module'])
            ->get();

        $results = [];
        foreach ($assignments as $assignment) {
            $progress = \App\Models\ModuleProgress::where('training_module_id', $module->id)
                ->where('user_id', $assignment->user_id)
                ->first();

            $quizResults = \App\Models\UserAnswer::whereHas('question.quiz', function ($query) use ($module) {
                $query->where('training_module_id', $module->id);
            })
            ->where('user_id', $assignment->user_id)
            ->with(['question.quiz', 'selectedOption'])
            ->get()
            ->groupBy('question.quiz_id');

            $results[] = [
                'assignment' => $assignment,
                'progress' => $progress,
                'quiz_results' => $quizResults,
            ];
        }

        return view('assignments.evaluations', [
            'module' => $module,
            'results' => $results,
        ]);
    }

    /**
     * Show detailed evaluation for a specific user
     */
    public function userEvaluation(TrainingModule $module, User $user)
    {
        $assignment = \App\Models\AssignedModule::where('training_module_id', $module->id)
            ->where('user_id', $user->id)
            ->with('user')
            ->firstOrFail();

        $progress = \App\Models\ModuleProgress::where('training_module_id', $module->id)
            ->where('user_id', $user->id)
            ->first();

        $quizzes = $module->quizzes()->with(['questions.options'])->get();
        
        $quizResults = [];
        foreach ($quizzes as $quiz) {
            $userAnswers = \App\Models\UserAnswer::where('user_id', $user->id)
                ->whereHas('question', function ($query) use ($quiz) {
                    $query->where('quiz_id', $quiz->id);
                })
                ->with(['question', 'selectedOption'])
                ->get();

            $correctAnswers = 0;
            $totalQuestions = $quiz->questions()->count();
            
            foreach ($userAnswers as $answer) {
                $correctOption = $answer->question->options()->where('is_correct', true)->first();
                if ($correctOption && $answer->selected_option_id == $correctOption->id) {
                    $correctAnswers++;
                }
            }

            $score = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;
            $passed = $score >= $quiz->passing_score;

            $quizResults[] = [
                'quiz' => $quiz,
                'user_answers' => $userAnswers,
                'correct_answers' => $correctAnswers,
                'total_questions' => $totalQuestions,
                'score' => $score,
                'passed' => $passed,
            ];
        }

        return view('assignments.user-evaluation', [
            'module' => $module,
            'user' => $user,
            'assignment' => $assignment,
            'progress' => $progress,
            'quizResults' => $quizResults,
        ]);
    }

    /**
     * Manually mark an assignment as completed.
     */
    public function complete(Request $request, TrainingModule $module, User $user)
    {
        $assignment = \App\Models\AssignedModule::where('training_module_id', $module->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $assignment->update(['status' => 'completed']);

        // Optional: Send notification to learner that their module is marked complete?
        // For now, just redirect back.

        return redirect()->back()->with('status', 'Module marked as completed.');
    }
}
