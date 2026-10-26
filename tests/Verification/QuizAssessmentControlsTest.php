<?php

namespace Tests\Verification;

use App\Models\AssignedModule;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * R8 — shuffle, per-quiz time limit, attempt cap with cooldown.
 */
class QuizAssessmentControlsTest extends TestCase
{
    use DatabaseTransactions;

    private function learnerFor(Quiz $quiz): User
    {
        $assignment = AssignedModule::where('training_module_id', $quiz->training_module_id)->firstOrFail();

        return User::findOrFail($assignment->user_id);
    }

    private function quizWithEnrolment(): array
    {
        $quiz = Quiz::whereHas('module')->orderBy('id')->firstOrFail();

        return [$quiz, $this->learnerFor($quiz)];
    }

    public function test_time_limit_opens_an_attempt_with_a_deadline(): void
    {
        [$quiz, $learner] = $this->quizWithEnrolment();

        $quiz->update(['time_limit_minutes' => 30, 'attempts_allowed' => 0]);

        $this->actingAs($learner)
            ->get(route('quizzes.take', $quiz))
            ->assertOk()
            ->assertSee('Time remaining');

        $attempt = QuizAttempt::where('user_id', $learner->id)
            ->where('quiz_id', $quiz->id)
            ->whereNull('submitted_at')
            ->first();

        $this->assertNotNull($attempt);
        $this->assertNotNull($attempt->started_at);
        $this->assertNotNull($attempt->expires_at);
        $this->assertTrue($attempt->expires_at->between(now()->addMinutes(29), now()->addMinutes(31)));
    }

    public function test_reloading_resumes_the_same_sitting_without_extra_time(): void
    {
        [$quiz, $learner] = $this->quizWithEnrolment();

        $quiz->update(['time_limit_minutes' => 45]);

        $this->actingAs($learner)->get(route('quizzes.take', $quiz))->assertOk();
        $this->travel(5)->minutes();
        $this->actingAs($learner)->get(route('quizzes.take', $quiz))->assertOk();

        $attempts = QuizAttempt::where('user_id', $learner->id)->where('quiz_id', $quiz->id)->get();

        $this->assertCount(1, $attempts, 'A reload must not open a second attempt.');
        // Deadline is unchanged: the learner cannot refresh for more time.
        $this->assertTrue($attempts->first()->expires_at->lessThan(now()->addMinutes(41)));
    }

    public function test_attempt_cap_blocks_further_sittings(): void
    {
        [$quiz, $learner] = $this->quizWithEnrolment();

        $quiz->update(['attempts_allowed' => 1]);

        QuizAttempt::create([
            'user_id' => $learner->id,
            'quiz_id' => $quiz->id,
            'score' => 40,
            'status' => 'failed',
            'attempt_number' => 1,
            'started_at' => now()->subMinutes(20),
            'submitted_at' => now()->subMinutes(15),
        ]);

        $this->actingAs($learner)
            ->get(route('quizzes.take', $quiz))
            ->assertRedirect(route('modules.show', $quiz->module))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'all 1 attempt'));
    }

    public function test_cooldown_blocks_a_retake_after_a_failure(): void
    {
        [$quiz, $learner] = $this->quizWithEnrolment();

        $quiz->update(['attempts_allowed' => 0, 'cooldown_minutes' => 60]);

        QuizAttempt::create([
            'user_id' => $learner->id,
            'quiz_id' => $quiz->id,
            'score' => 30,
            'status' => 'failed',
            'attempt_number' => 1,
            'started_at' => now()->subMinutes(25),
            'submitted_at' => now()->subMinutes(20),
        ]);

        $this->actingAs($learner)
            ->get(route('quizzes.take', $quiz))
            ->assertRedirect(route('modules.show', $quiz->module))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'retake this quiz from'));
    }

    public function test_cooldown_expires_and_allows_a_retake(): void
    {
        [$quiz, $learner] = $this->quizWithEnrolment();

        $quiz->update(['attempts_allowed' => 0, 'cooldown_minutes' => 30]);

        QuizAttempt::create([
            'user_id' => $learner->id,
            'quiz_id' => $quiz->id,
            'score' => 30,
            'status' => 'failed',
            'attempt_number' => 1,
            'started_at' => now()->subMinutes(90),
            'submitted_at' => now()->subMinutes(60),
        ]);

        $this->actingAs($learner)->get(route('quizzes.take', $quiz))->assertOk();
    }

    public function test_instructor_can_save_assessment_rules(): void
    {
        [$quiz] = $this->quizWithEnrolment();
        $admin = User::role('admin')->firstOrFail();

        $this->actingAs($admin)->put(route('quizzes.update', $quiz), [
            'title' => $quiz->title,
            'passing_score' => 70,
            'time_limit_minutes' => 25,
            'attempts_allowed' => 3,
            'cooldown_minutes' => 120,
            'shuffle_questions' => '1',
            'shuffle_options' => '1',
        ])->assertRedirect(route('quizzes.index'));

        $quiz->refresh();

        $this->assertSame(25, $quiz->time_limit_minutes);
        $this->assertSame(3, $quiz->attempts_allowed);
        $this->assertSame(120, $quiz->cooldown_minutes);
        $this->assertTrue($quiz->shuffle_questions);
        $this->assertTrue($quiz->shuffle_options);
    }
}
