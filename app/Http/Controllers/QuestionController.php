<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\UserAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class QuestionController extends Controller
{
    public function __construct()
    {
        // Allow instructors and admins (or users with manage quizzes permission)
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            if (!$user) return abort(403);
            if (!$user->hasRole('instructor') && !$user->hasRole('admin') && !$user->can('manage quizzes')) {
                abort(403);
            }
            return $next($request);
        });
    }

    public function store(Request $request, Quiz $quiz)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'points' => ['required', 'integer', 'min:0'],
            'type' => ['required', 'in:mcq,short'],
            'options' => ['nullable', 'array'],
            'options.*' => ['nullable', 'string', 'max:1000'],
            'correct_option_index' => ['nullable', 'integer'],
        ]);

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'question' => $data['question'],
            'points' => $data['points'],
        ]);

        if ($data['type'] === 'mcq') {
            $options = $data['options'] ?? [];
            $correctIndex = isset($data['correct_option_index']) ? (int) $data['correct_option_index'] : null;

            foreach ($options as $idx => $optText) {
                if (is_null($optText) || trim($optText) === '') continue;
                $isCorrect = $correctIndex !== null && (int) $idx === $correctIndex;
                QuestionOption::create([
                    'question_id' => $question->id,
                    'option_text' => $optText,
                    'is_correct' => $isCorrect,
                ]);
            }
        }

        return redirect()->route('quizzes.edit', $quiz)->with('status', 'Question added.');
    }

    public function destroy(Question $question)
    {
        $quiz = $question->quiz;
        $question->options()->delete();
        $question->delete();

        return redirect()->route('quizzes.edit', $quiz)->with('status', 'Question removed.');
    }

    /**
     * Grade a user's short answer
     */
    public function gradeAnswer(Request $request, UserAnswer $answer)
    {
        $user = $request->user();
        if (!$user->hasRole('instructor') && !$user->hasRole('admin') && !$user->can('manage quizzes')) {
            abort(403);
        }

        $data = $request->validate([
            'points_awarded' => ['required', 'integer', 'min:0'],
        ]);

        // Only update grading columns if they exist in the table
        if (Schema::hasColumn('user_answers', 'points_awarded')) {
            $answer->points_awarded = $data['points_awarded'];
        }
        if (Schema::hasColumn('user_answers', 'is_graded')) {
            $answer->is_graded = true;
        }
        $answer->save();

        return redirect()->back()->with('status', 'Answer graded.');
    }
}
