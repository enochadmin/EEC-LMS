<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\EnrollmentRequest;
use App\Models\TrainingModule;
use Illuminate\Http\Request;

/**
 * Course catalogue for learners.
 *
 * Shows every published course the learner is *not* already in, so requesting
 * enrolment never leads to a duplicate. Filters are server-side and survive
 * pagination via appends().
 */
class DiscoverController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->hasRole('learner')) {
            return redirect()->route('modules.index')
                ->with('error', 'Discover is the learner catalogue. Use Modules to manage your courses.');
        }

        $assignedIds = $user->assignedModules()->pluck('training_module_id');

        // A pending request already exists — show it as "requested" rather than
        // letting the learner submit the same request twice.
        $pendingIds = EnrollmentRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->pluck('training_module_id');

        $query = TrainingModule::with(['category', 'creator.department'])
            ->withCount(['lessons', 'quizzes'])
            ->where('status', 'published')
            ->whereNotIn('id', $assignedIds);

        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->get('category')) {
            $query->where('category_id', $categoryId);
        }

        // Modules have no owning department of their own, so the filter uses the
        // department of the instructor who created the course.
        if ($departmentId = $request->get('department')) {
            $query->where(function ($departmentQuery) use ($departmentId) {
                $departmentQuery->where('department_id', $departmentId)
                    ->orWhere(function ($fallback) use ($departmentId) {
                        $fallback->whereNull('department_id')
                            ->whereHas('creator', fn ($creator) => $creator->where('department_id', $departmentId));
                    });
            });
        }

        $modules = $query->latest()->paginate(12)->appends($request->query());

        return view('discover.index', [
            'modules' => $modules,
            'categories' => Category::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'pendingIds' => $pendingIds->all(),
        ]);
    }
}
