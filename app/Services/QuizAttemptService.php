<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Owns the lifecycle of one sitting of a quiz (R8).
 *
 * Rules enforced here, all server-side:
 *   - attempts_allowed (0 = unlimited) is a hard cap on *submitted* attempts
 *   - cooldown_minutes blocks a retake for a cooling-off window after a failure
 *   - time_limit_minutes sets a deadline when the sitting starts; the page
 *     counts down and submits automatically, and submit() still re-checks it
 *
 * An "open" attempt is one that has started but not been submitted. Reloading
 * the page resumes it and keeps the original deadline, so a refresh can never
 * hand out extra time.
 */
class QuizAttemptService
{
    /**
     * Returns a human-readable reason the learner may not sit the quiz, or
     * null when they are allowed to start / resume.
     */
    public function blockReason(Quiz $quiz, User $user): ?string
    {
        // An in-progress sitting always resumes, even if the cap is now reached.
        if ($this->openAttempt($quiz, $user)) {
            return null;
        }

        $submitted = $this->submittedAttempts($quiz, $user);

        if ($quiz->attempts_allowed > 0 && $submitted->count() >= $quiz->attempts_allowed) {
            return "You have used all {$quiz->attempts_allowed} attempt(s) for this quiz.";
        }

        if ($quiz->cooldown_minutes > 0 && $submitted->isNotEmpty()) {
            $last = $submitted->sortByDesc('submitted_at')->first();
            $endedAt = $last->submitted_at ?? $last->updated_at;

            if ($endedAt && $last->status === 'failed') {
                $availableAt = Carbon::parse($endedAt)->addMinutes($quiz->cooldown_minutes);

                if ($availableAt->isFuture()) {
                    return 'You can retake this quiz from '
                        . $availableAt->format('d M Y, H:i')
                        . ' (' . $availableAt->diffForHumans() . ').';
                }
            }
        }

        return null;
    }

    /**
     * Resume the open sitting, or start a new one.
     */
    public function start(Quiz $quiz, User $user): QuizAttempt
    {
        if ($existing = $this->openAttempt($quiz, $user)) {
            return $existing;
        }

        $lastNumber = (int) QuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->max('attempt_number');

        $startedAt = now();

        return QuizAttempt::create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'score' => 0,
            'status' => 'failed',
            'attempt_number' => $lastNumber + 1,
            'started_at' => $startedAt,
            'expires_at' => $quiz->time_limit_minutes
                ? $startedAt->copy()->addMinutes($quiz->time_limit_minutes)
                : null,
        ]);
    }

    public function openAttempt(Quiz $quiz, User $user): ?QuizAttempt
    {
        return QuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->whereNull('submitted_at')
            ->latest('id')
            ->first();
    }

    public function submittedAttempts(Quiz $quiz, User $user)
    {
        return QuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->whereNotNull('submitted_at')
            ->get();
    }

    /**
     * Seconds left on a sitting, or null when there is no time limit.
     */
    public function secondsRemaining(?QuizAttempt $attempt): ?int
    {
        if (! $attempt || ! $attempt->expires_at) {
            return null;
        }

        return max(0, now()->diffInSeconds($attempt->expires_at, false));
    }

    /**
     * Close a sitting and store its result.
     */
    public function finish(QuizAttempt $attempt, float $score, bool $passed): QuizAttempt
    {
        $attempt->update([
            'score' => $score,
            'status' => $passed ? 'passed' : 'failed',
            'submitted_at' => now(),
        ]);

        return $attempt->refresh();
    }

    /**
     * Shuffle questions and/or options for display, per the quiz settings.
     * Order only affects presentation — grading is by question and option id.
     */
    public function applyShuffle(Quiz $quiz): Quiz
    {
        if ($quiz->shuffle_questions) {
            $quiz->setRelation('questions', $quiz->questions->shuffle()->values());
        }

        if ($quiz->shuffle_options) {
            $quiz->questions->each(function ($question) {
                $question->setRelation('options', $question->options->shuffle()->values());
            });
        }

        return $quiz;
    }
}
