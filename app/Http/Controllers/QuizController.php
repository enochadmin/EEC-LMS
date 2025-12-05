<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Support\Facades\Schema;
use App\Models\TrainingModule;
use App\Models\UserAnswer;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage quizzes')->except(['take', 'submit']);
    }

    public function index(Request $request)
    {
        $query = Quiz::with('module')->latest();
        
        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }
        
        $quizzes = $query->paginate(10)->appends(['search' => $search]);

        return view('quizzes.index', compact('quizzes'));
    }

    public function create()
    {
        $modules = TrainingModule::orderBy('title')->pluck('title', 'id');

        return view('quizzes.create', compact('modules'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'training_module_id' => ['required', 'exists:training_modules,id'],
            'title' => ['required', 'string', 'max:255'],
            'passing_score' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $quiz = Quiz::create($data);

        return redirect()
            ->route('quizzes.edit', $quiz)
            ->with('status', 'Quiz created. Add questions below.');
    }

    public function edit(Quiz $quiz)
    {
        $quiz->load('questions.options', 'module');

        return view('quizzes.edit', compact('quiz'));
    }

    public function update(Request $request, Quiz $quiz)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'passing_score' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $quiz->update($data);

        return redirect()
            ->route('quizzes.index')
            ->with('status', 'Quiz updated.');
    }

    public function destroy(Quiz $quiz)
    {
        $quiz->delete();

        return redirect()
            ->route('quizzes.index')
            ->with('status', 'Quiz removed.');
    }

    public function take(Quiz $quiz)
    {
        $quiz->load('questions.options');
        
        $user = auth()->user();
        
        // Ensure learner is enrolled for this module
        if ($user->hasRole('learner')) {
            $assignment = \App\Models\AssignedModule::where('training_module_id', $quiz->training_module_id)
                ->where('user_id', $user->id)
                ->first();

            if (! $assignment) {
                return redirect()->route('modules.show', $quiz->module)
                    ->with('error', 'You are not enrolled in this module or your enrollment is pending approval.');
            }
        }
        
        // Check if user has already passed this quiz
        // Get all user answers for this quiz
        $userAnswers = \App\Models\UserAnswer::where('user_id', $user->id)
            ->whereIn('question_id', $quiz->questions->pluck('id'))
            ->get();
            
        if ($userAnswers->isNotEmpty()) {
            // Calculate score
            $correctAnswers = 0;
            $totalQuestions = $quiz->questions->count();
            
            foreach ($userAnswers as $answer) {
                $question = $quiz->questions->find($answer->question_id);
                if ($question && $question->options->count() > 0) {
                     $correctOption = $question->options->where('is_correct', true)->first();
                     if ($correctOption && $answer->selected_option_id == $correctOption->id) {
                         $correctAnswers++;
                     }
                }
            }
            
            $score = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;
            
            if ($score >= $quiz->passing_score) {
                // Check for existing retake request
                $existingRequest = \App\Models\RetakeRequest::where('user_id', $user->id)
                    ->where('requestable_type', \App\Models\Quiz::class)
                    ->where('requestable_id', $quiz->id)
                    ->where('status', 'pending')
                    ->first();

                return view('retake_requests.locked', [
                    'type' => 'quiz',
                    'item' => $quiz,
                    'module' => $quiz->module,
                    'existingRequest' => $existingRequest
                ]);
            }
        }

        return view('quizzes.take', compact('quiz'));
    }

    public function submit(Request $request, Quiz $quiz)
    {
        $quiz->load('questions.options');

        if ($quiz->questions->isEmpty()) {
            return redirect()
                ->route('quizzes.take', $quiz)
                ->with('status', 'This quiz does not have any questions yet.');
        }

        $validated = $request->validate([
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', 'integer', 'exists:question_options,id'],
            'text_answers' => ['nullable', 'array'],
            'text_answers.*' => ['nullable', 'string', 'max:2000'],
        ]);

        $answers = $validated['answers'] ?? [];
        $textAnswers = $validated['text_answers'] ?? [];

        // Auto-graded questions (MCQ)
        $autoQuestions = $quiz->questions->filter(function ($q) {
            return $q->options->count() > 0;
        });

        $totalAutoPoints = $autoQuestions->sum('points');
        $earnedPoints = 0;

        foreach ($quiz->questions as $question) {
            // MCQ answer submitted
            if ($question->options->count() > 0) {
                $selectedOptionId = isset($answers[$question->id]) ? (int) $answers[$question->id] : null;

                if ($selectedOptionId) {
                    $update = [
                        'selected_option_id' => $selectedOptionId,
                    ];
                    // Only include fields if columns exist to avoid SQL errors
                    if (Schema::hasColumn('user_answers', 'text_answer')) {
                        $update['text_answer'] = null;
                    }
                    if (Schema::hasColumn('user_answers', 'is_graded')) {
                        $update['is_graded'] = true;
                    }

                    UserAnswer::updateOrCreate(
                        [
                            'user_id' => $request->user()->id,
                            'question_id' => $question->id,
                        ],
                        $update
                    );
                }

                $correctOption = $question->options->firstWhere('is_correct', true);
                if ($correctOption && $selectedOptionId !== null && $selectedOptionId === (int) $correctOption->id) {
                    $earnedPoints += $question->points;
                }
            } else {
                // Short answer (store text for manual grading)
                $text = isset($textAnswers[$question->id]) ? $textAnswers[$question->id] : null;
                if ($text !== null) {
                    $update = [
                        'selected_option_id' => null,
                    ];
                    if (Schema::hasColumn('user_answers', 'text_answer')) {
                        $update['text_answer'] = $text;
                    }
                    if (Schema::hasColumn('user_answers', 'is_graded')) {
                        $update['is_graded'] = false;
                    }

                    UserAnswer::updateOrCreate(
                        [
                            'user_id' => $request->user()->id,
                            'question_id' => $question->id,
                        ],
                        $update
                    );
                }
            }
        }

        $percentageAuto = $totalAutoPoints > 0 ? round(($earnedPoints / $totalAutoPoints) * 100) : null;
        $pendingGradingPoints = $quiz->questions->filter(fn($q)=> $q->options->count() === 0)->sum('points');
        
        $passed = $percentageAuto !== null ? ($percentageAuto >= $quiz->passing_score) : false;

        // Record Attempt and update module-level progress
        if ($percentageAuto !== null) {
            $attemptCount = \App\Models\QuizAttempt::where('user_id', $request->user()->id)
                ->where('quiz_id', $quiz->id)
                ->count();

            $attempt = \App\Models\QuizAttempt::create([
                'user_id' => $request->user()->id,
                'quiz_id' => $quiz->id,
                'score' => $percentageAuto,
                'status' => $passed ? 'passed' : 'failed',
                'attempt_number' => $attemptCount + 1,
            ]);

            // Update module progress: quizzes contribute 50%
            $module = $quiz->module;
            $userId = $request->user()->id;

            $quizPortion = 50;
            $quizPercentage = $passed ? $quizPortion : 0; // Only passed quizzes count toward the quiz portion

            // Load or init progress and recompute aggregate
            $progress = \App\Models\ModuleProgress::firstOrNew([
                'training_module_id' => $module->id,
                'user_id' => $userId,
            ]);

            $progress->quiz_percentage = $quizPercentage;
            $progress->lesson_percentage = $progress->lesson_percentage ?? 0;
            $progress->percentage = round(($progress->lesson_percentage ?? 0) + ($progress->quiz_percentage ?? 0), 1);
            $progress->last_accessed_at = now();
            $progress->save();

            // Notify instructor about quiz attempt
            $assignment = \App\Models\AssignedModule::where('training_module_id', $module->id)
                ->where('user_id', $request->user()->id)
                ->with('assigner')
                ->first();

            $notifiable = $assignment?->assigner ?? $module->creator;
            if ($notifiable) {
                $notifiable->notify(new \App\Notifications\QuizAttemptNotification($attempt));
            }
        }

        return view('quizzes.result', [
            'quiz' => $quiz,
            'percentage' => $percentageAuto,
            'passed' => $passed,
            'pendingGradingPoints' => $pendingGradingPoints,
        ]);
    }

    public function results(Quiz $quiz)
    {
        $quiz->load('questions.options');
        $userAnswers = \App\Models\UserAnswer::where('user_id', auth()->id())
            ->whereIn('question_id', $quiz->questions->pluck('id'))
            ->get()
            ->keyBy('question_id');

        return view('quizzes.results', [
            'quiz' => $quiz,
            'userAnswers' => $userAnswers,
        ]);
    }
}
