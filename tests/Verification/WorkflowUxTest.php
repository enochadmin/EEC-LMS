<?php

namespace Tests\Verification;

use App\Models\AssignedModule;
use App\Models\ModuleCompletionApproval;
use App\Models\User;
use App\Support\NotificationUrl;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Round of fixes from user testing:
 *   1. notifications clickable for every role (URL normalisation)
 *   2. role & permission CRUD with guard rails
 *   3. course-completion card states on the module page
 */
class WorkflowUxTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('admin')->firstOrFail();
    }

    private function learnerWithModule(): array
    {
        $assignment = AssignedModule::with('module')->firstOrFail();

        return [User::findOrFail($assignment->user_id), $assignment->module];
    }

    // ---- 1. notification links ------------------------------------------------

    public function test_stored_url_is_reduced_to_a_path_so_host_changes_do_not_break_it(): void
    {
        $this->assertSame('/certificates', NotificationUrl::for(['url' => 'http://localhost:80/certificates']));
        $this->assertSame('/retake-requests', NotificationUrl::for(['url' => 'http://127.0.0.1:8000/retake-requests']));
        $this->assertSame('/modules/4', NotificationUrl::for(['url' => '/modules/4']));
        $this->assertSame('/my-progress?tab=2', NotificationUrl::for(['url' => 'http://localhost/my-progress?tab=2']));
    }

    public function test_dangerous_urls_are_still_rejected(): void
    {
        $this->assertNull(NotificationUrl::for(['url' => 'javascript:alert(1)']));
        $this->assertNull(NotificationUrl::for(['url' => 'data:text/html,hi']));
    }

    public function test_completion_request_notification_links_to_the_evaluation_screen(): void
    {
        [$learner, $module] = $this->learnerWithModule();

        $url = NotificationUrl::for([
            'type' => 'module_completion_approval_request',
            'module_id' => $module->id,
            'learner_id' => $learner->id,
        ]);

        $this->assertStringContainsString("/evaluations/{$learner->id}", $url);
    }

    // ---- 2. role management ---------------------------------------------------

    public function test_admin_can_create_a_role_with_permissions(): void
    {
        $this->actingAs($this->admin())
            ->post(route('roles.store'), [
                'name' => 'auditor',
                'permissions' => ['manage knowledge', 'view certificates'],
            ])
            ->assertRedirect(route('roles.index'));

        $role = Role::where('name', 'auditor')->first();

        $this->assertNotNull($role);
        $this->assertEqualsCanonicalizing(
            ['manage knowledge', 'view certificates'],
            $role->permissions->pluck('name')->all()
        );
    }

    public function test_admin_role_cannot_be_deleted_or_renamed(): void
    {
        $adminRole = Role::where('name', 'admin')->firstOrFail();

        $this->actingAs($this->admin())
            ->delete(route('roles.destroy', $adminRole))
            ->assertSessionHas('error');

        $this->assertNotNull(Role::where('name', 'admin')->first());

        $this->actingAs($this->admin())
            ->put(route('roles.update', $adminRole), ['name' => 'superuser'])
            ->assertSessionHas('error');

        $this->assertSame('admin', $adminRole->fresh()->name);
    }

    public function test_role_with_users_cannot_be_deleted(): void
    {
        $instructor = Role::where('name', 'instructor')->firstOrFail();

        $this->actingAs($this->admin())
            ->delete(route('roles.destroy', $instructor))
            ->assertSessionHas('error');

        $this->assertNotNull(Role::where('name', 'instructor')->first());
    }

    public function test_learner_cannot_reach_role_management(): void
    {
        $learner = User::role('learner')->firstOrFail();

        $this->actingAs($learner)->get(route('roles.index'))->assertForbidden();
    }

    // ---- 3. course completion card ---------------------------------------------

    public function test_learner_sees_the_waiting_state_while_a_request_is_pending(): void
    {
        [$learner, $module] = $this->learnerWithModule();

        ModuleCompletionApproval::updateOrCreate(
            ['training_module_id' => $module->id, 'user_id' => $learner->id],
            ['status' => 'pending']
        );

        $this->actingAs($learner)
            ->get(route('modules.show', $module))
            ->assertOk()
            ->assertSee('Waiting for instructor approval');
    }

    public function test_learner_sees_the_completed_state_after_approval(): void
    {
        [$learner, $module] = $this->learnerWithModule();

        ModuleCompletionApproval::updateOrCreate(
            ['training_module_id' => $module->id, 'user_id' => $learner->id],
            ['status' => 'approved', 'approved_at' => now()]
        );

        $this->actingAs($learner)
            ->get(route('modules.show', $module))
            ->assertOk()
            ->assertSee('Course completed');
    }
}
