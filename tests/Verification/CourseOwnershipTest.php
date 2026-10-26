<?php

namespace Tests\Verification;

use App\Models\Category;
use App\Models\Department;
use App\Models\TrainingModule;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Department ownership + advisors on both creation paths.
 */
class CourseOwnershipTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('admin')->firstOrFail();
    }

    private function advisor(): User
    {
        return User::whereHas('roles', fn ($q) => $q->whereIn('name', ['instructor', 'instructor_manager']))
            ->firstOrFail();
    }

    public function test_manual_creation_saves_department_and_advisors(): void
    {
        $category = Category::firstOrFail();
        $department = Department::firstOrCreate(['name' => 'Operations']);
        $advisor = $this->advisor();

        $response = $this->actingAs($this->admin())->post(route('modules.store'), [
            'title' => 'Department owned course',
            'category_id' => $category->id,
            'department_id' => $department->id,
            'description' => 'Owned by a department.',
            'status' => 'published',
            'advisors' => [$advisor->id],
        ]);

        $module = TrainingModule::where('title', 'Department owned course')->firstOrFail();

        $response->assertRedirect(route('modules.show', $module));
        $this->assertSame($department->id, $module->department_id);
        $this->assertTrue($module->advisors->pluck('id')->contains($advisor->id));
        $this->assertSame($this->admin()->id, $module->advisors->first()->pivot->assigned_by);
    }

    public function test_template_creation_carries_department_and_advisors_through_the_wizard(): void
    {
        $category = Category::firstOrFail();
        $department = Department::firstOrCreate(['name' => 'Operations']);
        $advisor = $this->advisor();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('modules.create.template.structure'), [
            'title' => 'Template owned course',
            'category_id' => $category->id,
            'department_id' => $department->id,
            'description' => 'Created from a template.',
            'status' => 'published',
            'advisors' => [$advisor->id],
            'structure' => json_encode([
                ['type' => 'lesson', 'title' => 'Intro', 'minutes' => 5],
            ]),
        ])->assertRedirect(route('modules.create.template.content'));

        $this->actingAs($admin)->post(route('modules.create.template'), [])
            ->assertRedirect();

        $module = TrainingModule::where('title', 'Template owned course')->firstOrFail();

        $this->assertSame($department->id, $module->department_id);
        $this->assertTrue($module->advisors->pluck('id')->contains($advisor->id));
        $this->assertSame(1, $module->lessons()->count());
    }
}
