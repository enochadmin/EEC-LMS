<?php

namespace Tests\Verification;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_create_a_user_with_hr_profile_fields(): void
    {
        $admin = User::role('admin')->firstOrFail();
        Role::firstOrCreate(['name' => 'learner']);

        $this->actingAs($admin)->post(route('users.store'), [
            'first_name' => 'Selam',
            'last_name' => 'Bekele',
            'email' => 'selam.bekele@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'learner',
            'job_title' => 'Safety Officer',
            'phone' => '+251911000111',
            'office_location' => 'Addis Ababa HQ',
            'employment_type' => 'Permanent',
            'hire_date' => '2024-03-01',
        ])->assertRedirect();

        $user = User::where('email', 'selam.bekele@example.com')->firstOrFail();

        $this->assertSame('Safety Officer', $user->job_title);
        $this->assertSame('+251911000111', $user->phone);
        $this->assertSame('Addis Ababa HQ', $user->office_location);
        $this->assertSame('Permanent', $user->employment_type);
        $this->assertSame('2024-03-01', $user->hire_date->format('Y-m-d'));
    }

    public function test_profile_fields_can_be_updated(): void
    {
        $admin = User::role('admin')->firstOrFail();
        $learner = User::role('learner')->firstOrFail();

        $this->actingAs($admin)->put(route('users.update', $learner), [
            'first_name' => $learner->first_name ?: 'Test',
            'last_name' => $learner->last_name ?: 'User',
            'email' => $learner->email,
            'role' => 'learner',
            'job_title' => 'Senior Technician',
            'phone' => '+251922333444',
            'employment_type' => 'Contract',
        ])->assertRedirect();

        $learner->refresh();

        $this->assertSame('Senior Technician', $learner->job_title);
        $this->assertSame('+251922333444', $learner->phone);
        $this->assertSame('Contract', $learner->employment_type);
    }
}
