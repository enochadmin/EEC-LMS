<?php

namespace Tests\Verification;

use App\Models\AssignedModule;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\TrainingModule;
use App\Models\User;
use App\Models\UserAnswer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Learners: assigned-only module list + Discover catalogue with enrolment
 * request, and the short-answer input on the quiz sitting page.
 */
class DiscoverAndShortAnswerTest extends TestCase
{
    use DatabaseTransactions;

    private function learner(): User
    {
        return User::role('learner')->firstOrFail();
    }

    public function test_modules_page_shows_only_assigned_courses_to_a_learner(): void
    {
        $learner = $this->learner();
        $assignedIds = AssignedModule::where('user_id', $learner->id)->pluck('training_module_id');
        $other = TrainingModule::whereNotIn('id', $assignedIds)->first();

        $response = $this->actingAs($learner)->get(route('modules.index'))->assertOk();

        $visibleIds = collect($response->viewData('modules')->items())->pluck('id');

        foreach ($visibleIds as $id) {
            $this->assertTrue($assignedIds->contains($id), "Module {$id} is not assigned to the learner but was listed.");
        }

        if ($other) {
            $this->assertFalse($visibleIds->contains($other->id));
        }
    }

    public function test_discover_lists_published_courses_the_learner_is_not_in(): void
    {
        $learner = $this->learner();
        $assignedIds = AssignedModule::where('user_id', $learner->id)->pluck('training_module_id');

        $response = $this->actingAs($learner)->get(route('discover.index'))->assertOk();

        $listed = collect($response->viewData('modules')->items())->pluck('id');

        foreach ($listed as $id) {
            $this->assertFalse($assignedIds->contains($id), 'An assigned course leaked into Discover.');
        }

        $publishedUnassigned = TrainingModule::where('status', 'published')
            ->whereNotIn('id', $assignedIds)
            ->count();

        $this->assertSame($publishedUnassigned, $listed->count());
    }

    public function test_discover_search_and_category_filters_apply(): void
    {
        $learner = $this->learner();
        $assigned = AssignedModule::where('user_id', $learner->id)->pluck('training_module_id');
        $module = TrainingModule::where('status', 'published')->whereNotIn('id', $assigned)->first();

        if (! $module) {
            $this->markTestSkipped('No published module in the test database.');
        }

        $response = $this->actingAs($learner)
            ->get(route('discover.index', ['q' => $module->title, 'category' => $module->category_id]))
            ->assertOk();

        $listed = collect($response->viewData('modules')->items())->pluck('id');
        $this->assertTrue($listed->contains($module->id));
    }

    public function test_staff_cannot_use_discover(): void
    {
        $admin = User::role('admin')->firstOrFail();

        $this->actingAs($admin)->get(route('discover.index'))
            ->assertRedirect(route('modules.index'));
    }

    public function test_short_answer_question_renders_a_textarea_and_is_saved(): void
    {
        $learner = $this->learner();
        $moduleId = AssignedModule::where('user_id', $learner->id)->value('training_module_id');
        $quiz = Quiz::where('training_module_id', $moduleId)->firstOrFail();

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'question' => 'Explain the evacuation procedure in your own words.',
            'points' => 2,
        ]);

        $this->actingAs($learner)
            ->get(route('quizzes.take', $quiz))
            ->assertOk()
            ->assertSee('text_answers[' . $question->id . ']', false)
            ->assertSee('Your answer');

        $this->actingAs($learner)
            ->post(route('quizzes.submit', $quiz), [
                'text_answers' => [$question->id => 'Leave by the nearest exit and gather at the assembly point.'],
            ]);

        $answer = UserAnswer::where('user_id', $learner->id)
            ->where('question_id', $question->id)
            ->first();

        $this->assertNotNull($answer, 'The written answer was not stored.');
        $this->assertStringContainsString('assembly point', $answer->text_answer);
        $this->assertFalse((bool) $answer->is_graded, 'Written answers must wait for manual grading.');
    }
}
