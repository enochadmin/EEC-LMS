<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserHasRole;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage users')->except(['enroll', 'myProgress', 'myEnrollments']);
    }

    public function index(Request $request)
    {
        $query = User::with('roles')->orderBy('name');
        
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleFilter = $request->get('role')) {
            $query->whereHas('roles', function ($q) use ($roleFilter) {
                $q->where('name', $roleFilter);
            });
        }
        
        $users = $query->paginate(15)->appends(['search' => $search]);

        return view('users.index', compact('users'));
    }

    /**
     * Download a template file for bulk user import.
     *
     * The file lives in public/templates so it can be updated
     * without changing application code. It is a CSV that opens
     * directly in Excel.
     */
    public function downloadTemplate(?string $extension = null)
    {
        // Normalize requested extension if present
        $extension = $extension ? strtolower(ltrim($extension, '.')) : null;

        // Prefer explicit extension if provided
        if ($extension === 'xls' || $extension === 'xlsx') {
            $xlsPath = public_path('templates/users_template.' . $extension);
            if (file_exists($xlsPath)) {
                return response()->download($xlsPath, 'users_template.' . $extension);
            }
        }

        $path = public_path('templates/users_template.csv');

        // Fallback to the .xls version if needed
        if (! file_exists($path)) {
            $xlsPath = public_path('templates/users_template.xls');
            if (file_exists($xlsPath)) {
                return response()->download($xlsPath, 'users_template.xls');
            }
        } else {
            return response()->download($path, 'users_template.csv', [
                'Content-Type' => 'text/csv',
            ]);
        }

        // As a last resort, keep the old streamed CSV so the feature still works
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users_template.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            // Add BOM for Excel compatibility
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['employee_id', 'first_name', 'last_name', 'email', 'role', 'department']);
            fputcsv($handle, ['EMP001', 'John', 'Doe', 'john.doe@example.com', 'learner', 'IT']);
            fputcsv($handle, ['EMP002', 'Jane', 'Smith', 'jane.smith@example.com', 'instructor', 'HR']);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import users from uploaded CSV/XLS file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xls,xlsx']
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        // Try to open as CSV - many Excel clients will save .xls as CSV compatible
        if (($handle = fopen($path, 'r')) === false) {
            return redirect()->back()->with('error', 'Unable to read uploaded file.');
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return redirect()->back()->with('error', 'Uploaded file appears empty or malformed.');
        }

        // normalize headers
        $cols = array_map(function ($c) { return trim(strtolower($c)); }, $header);

        $required = ['employee_id','first_name','last_name','email','role','department'];
        foreach ($required as $col) {
            if (!in_array($col, $cols)) {
                fclose($handle);
                return redirect()->back()->with('error', "Missing required column: {$col}. Please use the template.");
            }
        }

        $created = 0; $updated = 0; $rows = 0; $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows++;
            $data = array_combine($cols, $row);
            if (!$data) { continue; }

            $email = trim($data['email'] ?? '');
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row {$rows}: invalid email.";
                continue;
            }

            $employeeId = trim($data['employee_id'] ?? null);
            $first = trim($data['first_name'] ?? '');
            $last = trim($data['last_name'] ?? '');
            $roleName = trim(strtolower($data['role'] ?? 'learner'));
            $deptName = trim($data['department'] ?? null);

            // find or create department
            $departmentId = null;
            if (!empty($deptName)) {
                $department = Department::firstOrCreate(['name' => $deptName]);
                $departmentId = $department->id;
            }

            // find existing by email
            $user = User::where('email', $email)->first();
            if (!$user) {
                $user = User::create([
                    'employee_id' => $employeeId,
                    'first_name' => $first,
                    'last_name' => $last,
                    'name' => trim($first . ' ' . $last),
                    'email' => $email,
                    'password' => null,
                    'department_id' => $departmentId,
                ]);
                $created++;

                // send password reset link so user can set password (best-effort)
                try {
                    Password::sendResetLink(['email' => $email]);
                } catch (\Throwable $e) {
                    // ignore; mail may not be configured
                }
            } else {
                $user->update([
                    'employee_id' => $employeeId,
                    'first_name' => $first,
                    'last_name' => $last,
                    'name' => trim($first . ' ' . $last),
                    'department_id' => $departmentId,
                ]);
                $updated++;
            }

            // assign role if exists
            try {
                $user->syncRoles([$roleName]);
                // maintain user_has_role table
                $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
                if ($role) {
                    \App\Models\UserHasRole::updateOrCreate(
                        ['user_id' => $user->id],
                        ['role_id' => $role->id]
                    );
                }
            } catch (\Throwable $e) {
                // skip role assignment errors
            }
        }

        fclose($handle);

        $msg = "Import finished. Created: {$created}, Updated: {$updated}, Rows processed: {$rows}.";
        if (count($errors)) {
            $msg .= ' Some rows had errors: ' . implode(' | ', array_slice($errors, 0, 5));
        }

        return redirect()->back()->with('status', $msg);
    }

    public function create()
    {
        $roles = Role::whereIn('name', ['learner', 'instructor'])->get();
        $departments = Department::orderBy('name')->get();
        return view('users.create', compact('roles', 'departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'exists:roles,name'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        // Always generate a unique employee ID automatically (E00001, E00002, ...)
        $employeeId = User::generateEmployeeId();

        $user = User::create([
            'employee_id' => $employeeId,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'name' => trim($data['first_name'] . ' ' . $data['last_name']),
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'department_id' => $data['department_id'] ?? null,
        ]);

        $user->assignRole($data['role']);

        // Sync to user_has_role table
        $role = Role::where('name', $data['role'])->first();
        if ($role) {
            UserHasRole::updateOrCreate(
                ['user_id' => $user->id, 'role_id' => $role->id],
                ['user_id' => $user->id, 'role_id' => $role->id]
            );
        }

        return redirect()
            ->route('users.index')
            ->with('status', 'User created successfully.');
    }

    public function show(User $user)
    {
        $user->load(['roles', 'assignedModules.module', 'progress.module', 'certificates.module']);
        
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $roles = Role::whereIn('name', ['learner', 'instructor'])->get();
        $departments = Department::orderBy('name')->get();
        $user->load('roles');
        
        return view('users.edit', compact('user', 'roles', 'departments'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'exists:roles,name'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        $user->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'name' => trim($data['first_name'] . ' ' . $data['last_name']),
            'email' => $data['email'],
            'department_id' => $data['department_id'] ?? null,
        ]);

        if ($request->filled('password')) {
            $user->update([
                'password' => Hash::make($data['password']),
            ]);
        }

        // Sync roles
        $user->syncRoles([$data['role']]);

        // Sync to user_has_role table
        $role = Role::where('name', $data['role'])->first();
        if ($role) {
            // Remove old role assignments
            UserHasRole::where('user_id', $user->id)->delete();
            // Add new role assignment
            UserHasRole::create([
                'user_id' => $user->id,
                'role_id' => $role->id,
            ]);
        }

        return redirect()
            ->route('users.index')
            ->with('status', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('status', 'User deleted successfully.');
    }

    /**
     * Self-enrollment for learners
     */
    public function enroll(Request $request, $moduleId)
    {
        $user = $request->user();
        
        if (!$user->hasRole('learner')) {
            return redirect()->back()->with('error', 'Only learners can enroll in modules.');
        }

        $module = \App\Models\TrainingModule::findOrFail($moduleId);

        if ($module->status !== 'published') {
            return redirect()->back()->with('error', 'This module is not available for enrollment.');
        }

        // Check if already enrolled
        $existingAssignment = \App\Models\AssignedModule::where('training_module_id', $moduleId)
            ->where('user_id', $user->id)
            ->first();

        if ($existingAssignment) {
            return redirect()->back()->with('error', 'You are already enrolled in this module.');
        }

        // Check if there is already a pending enrollment request
        $existingRequest = \App\Models\EnrollmentRequest::where('training_module_id', $moduleId)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return redirect()->back()->with('error', 'You already have a pending enrollment request for this module.');
        }

        // Create pending enrollment request
        \App\Models\EnrollmentRequest::create([
            'training_module_id' => $moduleId,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        return redirect()
            ->route('modules.show', $module)
            ->with('status', 'Enrollment request submitted. Waiting for instructor approval.');
    }

    /**
     * Learner's progress view
     */
    public function myProgress()
    {
        $user = auth()->user();
        
        $enrollments = $user->assignedModules()
            ->with(['module.category', 'module.lessons'])
            ->latest()
            ->get();

        $progress = $user->progress()
            ->with('module')
            ->get()
            ->keyBy('training_module_id');

        return view('users.my-progress', compact('enrollments', 'progress'));
    }

    /**
     * Learner's enrollments view
     */
    public function myEnrollments()
    {
        $user = auth()->user();
        
        $enrollments = $user->assignedModules()
            ->with(['module.category', 'module.lessons', 'module.quizzes'])
            ->latest()
            ->paginate(10);

        $availableModules = \App\Models\TrainingModule::where('status', 'published')
            ->whereDoesntHave('assignments', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['category', 'lessons', 'quizzes'])
            ->latest()
            ->paginate(10);

        return view('users.my-enrollments', compact('enrollments', 'availableModules'));
    }

    /**
     * Return JSON progress for the authenticated user (for live polling)
     */
    public function progressJson()
    {
        $user = auth()->user();

        $progress = $user->progress()->get()->map(function ($p) {
            return [
                'training_module_id' => $p->training_module_id,
                'completed_lessons' => $p->completed_lessons,
                'total_lessons' => $p->total_lessons,
                'percentage' => $p->percentage,
                'last_accessed_at' => $p->last_accessed_at ? $p->last_accessed_at->toIso8601String() : null,
            ];
        })->keyBy('training_module_id');

        return response()->json($progress);
    }
}
