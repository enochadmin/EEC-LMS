<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage modules',
            'manage lessons',
            'manage quizzes',
            'assign modules',
            'manage knowledge',
            'view certificates',
            'issue certificates',
            'manage users',
            'assign advisors',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $roles = [
            'admin' => $permissions,
            'instructor' => [
                'manage modules',
                'manage lessons',
                'manage quizzes',
                'manage knowledge',
                'assign modules',
                'issue certificates',
            ],
            'instructor_manager' => [
                'manage modules',
                'manage lessons',
                'manage quizzes',
                'assign modules',
                'assign advisors',
                'manage knowledge',
                'issue certificates',
            ],
            'learner' => [
                'view certificates',
            ],
        ];

        foreach ($roles as $role => $rolePermissions) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'])
                ->syncPermissions($rolePermissions);
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
            ]
        );
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        $instructor = User::firstOrCreate(
            ['email' => 'instructor@example.com'],
            [
                'name' => 'Instructor User',
                'password' => Hash::make('password'),
            ]
        );
        if (!$instructor->hasRole('instructor')) {
            $instructor->assignRole('instructor');
        }

        $learner = User::firstOrCreate(
            ['email' => 'learner@example.com'],
            [
                'name' => 'Learner User',
                'password' => Hash::make('password'),
            ]
        );
        if (!$learner->hasRole('learner')) {
            $learner->assignRole('learner');
        }
    }
}
