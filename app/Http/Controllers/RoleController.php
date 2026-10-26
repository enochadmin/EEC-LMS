<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Role & permission management for administrators.
 *
 * Roles drive both middleware (`role:admin`) and abilities (`permission:manage
 * modules`), so a mistake here can lock people out. The two guard rails:
 *   - the `admin` role cannot be renamed or deleted
 *   - a role that still has users cannot be deleted
 */
class RoleController extends Controller
{
    private const PROTECTED_ROLES = ['admin'];

    public function index()
    {
        $roles = Role::withCount(['users', 'permissions'])->orderBy('name')->get();
        $permissionCount = Permission::count();

        return view('roles.index', compact('roles', 'permissionCount'));
    }

    public function create()
    {
        return view('roles.form', [
            'role' => new Role(),
            'groups' => $this->permissionGroups(),
            'assigned' => old('permissions', []),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9]+(?:[ _-][a-z0-9]+)*$/', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ], [
            'name.regex' => 'Use lowercase letters, numbers, spaces, dashes or underscores.',
        ]);

        $role = Role::create(['name' => Str::slug($data['name'], ' ')]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('roles.index')
            ->with('status', "Role '{$role->name}' created with " . count($data['permissions'] ?? []) . ' permission(s).');
    }

    public function edit(Role $role)
    {
        return view('roles.form', [
            'role' => $role,
            'groups' => $this->permissionGroups(),
            'assigned' => old('permissions', $role->permissions->pluck('name')->all()),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $isProtected = in_array($role->name, self::PROTECTED_ROLES, true);

        $data = $request->validate([
            'name' => [
                'required', 'string', 'min:3', 'max:50',
                'regex:/^[a-z0-9]+(?:[ _-][a-z0-9]+)*$/',
                Rule::unique('roles', 'name')->ignore($role->id),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        if ($isProtected && Str::slug($data['name'], ' ') !== $role->name) {
            return back()->withInput()->with('error', 'The admin role cannot be renamed.');
        }

        if (! $isProtected) {
            $role->name = Str::slug($data['name'], ' ');
            $role->save();
        }

        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('roles.index')
            ->with('status', "Role '{$role->name}' updated.");
    }

    public function destroy(Role $role)
    {
        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return back()->with('error', 'The admin role cannot be deleted.');
        }

        if ($role->users()->exists()) {
            $count = $role->users()->count();

            return back()->with('error', "This role is still assigned to {$count} user(s). Reassign them first.");
        }

        $name = $role->name;
        $role->delete();

        return redirect()->route('roles.index')->with('status', "Role '{$name}' deleted.");
    }

    /**
     * Permissions grouped by the noun they act on: "manage modules",
     * "assign modules" and "manage lessons" become Modules / Lessons.
     */
    private function permissionGroups()
    {
        return Permission::orderBy('name')->get()
            ->groupBy(fn ($permission) => Str::headline(Str::afterLast($permission->name, ' ')));
    }
}
