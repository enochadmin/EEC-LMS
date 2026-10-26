<?php

namespace Tests\Verification;

use App\Models\Category;
use App\Models\LearningTimeLog;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\TrainingModule;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Manual verification suite for the Reports tab and the template module builder.
 *
 * Runs against a throwaway copy of the database (see README note in the task)
 * and rolls everything back, so it never touches real data.
 */
class ReportsAndTemplateTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('admin')->firstOrFail();
    }

    public function test_admin_sees_reports_page_with_kpis_and_charts(): void
    {
        $response = $this->actingAs($this->admin())->get('/reports');

        $response->assertOk();
        $response->assertSee('Enrolled learners');
        $response->assertSee('Course performance');
        $response->assertSee('trendChart', false);
        $response->assertSee('enrolmentChart', false);
    }

    public function test_instructor_can_open_reports_scoped(): void
    {
        $instructor = User::role('instructor')->first();

        if (! $instructor) {
            $this->markTestSkipped('No instructor in database.');
        }

        $this->actingAs($instructor)->get('/reports')->assertOk();
    }

    public function test_learner_is_forbidden_from_reports(): void
    {
        $learner = User::role('learner')->firstOrFail();

        $this->actingAs($learner)->get('/reports')->assertForbidden();
    }

    public function test_reports_accepts_filters(): void
    {
        $module = TrainingModule::first();

        $response = $this->actingAs($this->admin())->get('/reports?' . http_build_query([
            'from' => now()->subDays(30)->toDateString(),
            'to' => now()->toDateString(),
            'module_id' => $module?->id,
        ]));

        $response->assertOk();
    }

    public function test_create_screen_offers_two_paths(): void
    {
        $response = $this->actingAs($this->admin())->get('/modules/create');

        $response->assertOk();
        $response->assertSee('Manual builder');
        $response->assertSee('Create from template');
    }

    public function test_manual_builder_still_works(): void
    {
        $response = $this->actingAs($this->admin())->get('/modules/create/manual');

        $response->assertOk();
        $response->assertSee('Save Module');
    }

    public function test_template_flow_creates_lessons_quizzes_and_questions(): void
    {
        $admin = $this->admin();
        $category = Category::firstOrFail();

        $structure = json_encode([
            ['type' => 'lesson', 'title' => 'Verification Lesson One', 'minutes' => 5],
            ['type' => 'lesson', 'title' => 'Verification Lesson Two', 'minutes' => 7],
            ['type' => 'quiz', 'title' => 'Verification Quiz', 'passing_score' => 80],
        ]);

        $this->actingAs($admin)
            ->post('/modules/create/template/structure', [
                'title' => 'Verification Template Module',
                'category_id' => $category->id,
                'description' => 'Created by verification test',
                'duration_minutes' => 30,
                'status' => 'draft',
                'structure' => $structure,
            ])
            ->assertRedirect('/modules/create/template/content');

        $this->actingAs($admin)->get('/modules/create/template/content')
            ->assertOk()
            ->assertSee('Download CSV template');

        $download = $this->actingAs($admin)->get('/modules/create/template/download');
        $download->assertOk();
        $this->assertStringContainsString('#LESSONS', $download->streamedContent());
        $this->assertStringContainsString('Verification Lesson One', $download->streamedContent());

        $csv = implode("\n", [
            '#MODULE',
            'title,description,duration_minutes',
            'Verification Template Module,Created by verification test,30',
            '#LESSONS',
            'lesson_no,title,estimated_minutes,content,youtube_link,media_file,attachment_file',
            '1,Verification Lesson One,5,"First lesson body",,,',
            '2,Verification Lesson Two,7,"Second lesson body",,,',
            '#QUIZZES',
            'quiz_no,title,passing_score',
            '1,Verification Quiz,80',
            '#QUESTIONS',
            'quiz_no,question,points,option_a,option_b,option_c,option_d,correct_option',
            '1,What is 2+2?,1,3,4,5,6,b',
            '1,Explain your answer,2,,,,,',
        ]);

        $path = tempnam(sys_get_temp_dir(), 'tmpl') . '.csv';
        file_put_contents($path, $csv);

        $response = $this->actingAs($admin)->post('/modules/create/template', [
            'template_file' => new UploadedFile($path, 'module-template.csv', 'text/csv', null, true),
        ]);

        $module = TrainingModule::where('title', 'Verification Template Module')->latest('id')->first();

        $this->assertNotNull($module, 'Module was not created: ' . $response->getStatusCode());
        $response->assertRedirect(route('modules.show', $module));

        $lessons = Lesson::where('training_module_id', $module->id)->orderBy('order')->get();
        $this->assertCount(2, $lessons, 'Expected two lessons');
        $this->assertSame('First lesson body', $lessons[0]->content);
        $this->assertSame('Second lesson body', $lessons[1]->content);

        $quiz = Quiz::where('training_module_id', $module->id)->first();
        $this->assertNotNull($quiz);
        $this->assertSame(80, (int) $quiz->passing_score);

        $questions = Question::where('quiz_id', $quiz->id)->get();
        $this->assertCount(2, $questions, 'Expected two questions');

        $mcq = $questions->firstWhere('question', 'What is 2+2?');
        $this->assertNotNull($mcq);
        $this->assertSame(4, QuestionOption::where('question_id', $mcq->id)->count());

        $correct = QuestionOption::where('question_id', $mcq->id)->where('is_correct', true)->first();
        $this->assertNotNull($correct);
        $this->assertSame('4', $correct->option_text);

        $written = $questions->firstWhere('question', 'Explain your answer');
        $this->assertNotNull($written);
        $this->assertSame(0, QuestionOption::where('question_id', $written->id)->count());

        @unlink($path);
    }

    public function test_learning_time_heartbeat_records_and_reports(): void
    {
        $learner = User::role('learner')->firstOrFail();
        $assignment = \App\Models\AssignedModule::where('user_id', $learner->id)->first();

        if (! $assignment) {
            $this->markTestSkipped('Learner has no assignment.');
        }

        $this->actingAs($learner)
            ->postJson('/api/learning-time', [
                'training_module_id' => $assignment->training_module_id,
                'lesson_id' => 0,
                'seconds' => 45,
            ])
            ->assertOk()
            ->assertJson(['tracked' => true]);

        $this->assertDatabaseHas('learning_time_logs', [
            'user_id' => $learner->id,
            'training_module_id' => $assignment->training_module_id,
            'lesson_id' => 0,
            'tracked_on' => now()->toDateString(),
        ]);

        $log = LearningTimeLog::where('user_id', $learner->id)->latest('id')->first();
        $this->assertGreaterThanOrEqual(45, $log->seconds);

        // The report must still render now that time data exists.
        $this->actingAs($this->admin())
            ->get('/reports?from=' . now()->subDays(7)->toDateString() . '&to=' . now()->toDateString())
            ->assertOk()
            ->assertSee('Who spends time where');
    }

    public function test_time_is_ignored_for_non_enrolled_users(): void
    {
        $admin = $this->admin();
        $module = TrainingModule::firstOrFail();

        $this->actingAs($admin)
            ->postJson('/api/learning-time', [
                'training_module_id' => $module->id,
                'lesson_id' => 0,
                'seconds' => 60,
            ])
            ->assertStatus(202)
            ->assertJson(['tracked' => false]);

        $this->assertDatabaseMissing('learning_time_logs', [
            'user_id' => $admin->id,
            'training_module_id' => $module->id,
        ]);
    }
}
