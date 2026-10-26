<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\TrainingModule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TrainingModuleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage modules')->except(['index', 'show']);
    }

    public function index(Request $request)
    {
        $query = TrainingModule::with(['category', 'creator'])
            ->withCount(['lessons', 'quizzes'])
            ->latest();
        
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Learners only see the courses assigned to them here; the rest of the
        // catalogue lives on the Discover page.
        if (auth()->check() && auth()->user()->hasRole('learner')) {
            $query->whereIn('id', auth()->user()->assignedModules()->pluck('training_module_id'));
        }
        $modules = $query->paginate(10)->appends(['search' => $search]);

        $myAssignments = auth()->check()
            ? auth()->user()->assignedModules()->with('module')->latest()->take(5)->get()
            : collect();

        return view('modules.index', [
            'modules' => $modules,
            'assignments' => $myAssignments,
        ]);
    }

    /**
     * Entry point for creating a module: the instructor picks a path first
     * (manual builder vs. template), instead of being dropped straight into
     * the long form.
     */
    public function create()
    {
        return view('modules.create-choice');
    }

    /**
     * The original, fully manual builder — behaviour unchanged.
     */
    public function createManual()
    {
        $categories = Category::orderBy('name')->pluck('name', 'id');
        $departments = Department::orderBy('name')->get();
        $advisorOptions = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['instructor', 'instructor_manager']))->orderBy('name')->get(['id', 'name']);

        return view('modules.create', compact('categories', 'departments', 'advisorOptions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'advisors' => ['nullable', 'array'],
            'advisors.*' => ['integer', 'exists:users,id'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'], // 10MB max
            'video_file' => ['nullable', 'file', 'mimes:mp4,avi,mov,wmv', 'max:51200'], // 50MB max
            'youtube_link' => ['nullable', 'url', 'max:255'],
        ]);

        $data['created_by'] = $request->user()->id;
        $data['status'] = $data['status'] ?? 'draft';

        // Handle PDF file upload
        if ($request->hasFile('pdf_file')) {
            $data['pdf_file_path'] = $request->file('pdf_file')->store('modules/pdfs', 'public');
        }

        // Handle video file upload
        if ($request->hasFile('video_file')) {
            $data['video_file_path'] = $request->file('video_file')->store('modules/videos', 'public');
        }

        // Handle YouTube link
        if ($request->filled('youtube_link')) {
            $data['youtube_link'] = $this->extractYouTubeId($request->youtube_link) 
                ? $request->youtube_link 
                : null;
        }

        $module = TrainingModule::create($data);
        // Advisors are the people a learner can ask for help on this course.
        if ($request->has('advisors')) {
            $module->advisors()->syncWithPivotValues(
                $request->input('advisors', []),
                ['assigned_by' => $request->user()->id]
            );
        }

        return redirect()
            ->route('modules.show', $module)
            ->with('status', 'Training module created successfully.');
    }

    public function show(TrainingModule $module)
    {
        $module->load(['lessons', 'quizzes.questions', 'category', 'department', 'advisors', 'assignments.user']);

        $user = auth()->user();

        // Only learners can be enrolled. Admins and instructors are excluded.
        $assignableUsers = $user->can('assign modules')
            ? User::role('learner')->orderBy('name')->get(['id', 'name'])
            : collect();

        // Learners need the module's own material (intro video, PDF, description)
        // plus the review state of every lesson, so the page is the single place
        // to consume the module instead of a table of contents.
        $isEnrolled = $user->assignedModules()
            ->where('training_module_id', $module->id)
            ->exists();

        $lessonCompletions = \App\Models\LessonCompletion::where('user_id', $user->id)
            ->where('training_module_id', $module->id)
            ->get()
            ->keyBy('lesson_id');

        $progress = $isEnrolled
            ? app(\App\Services\ProgressService::class)->summary($user->id, $module->id)
            : null;

        return view('modules.show', [
            'module' => $module,
            'assignableUsers' => $assignableUsers,
            'isEnrolled' => $isEnrolled,
            'lessonCompletions' => $lessonCompletions,
            'progress' => $progress,
        ]);
    }

    public function edit(TrainingModule $module)
    {
        $categories = Category::orderBy('name')->pluck('name', 'id');
        $departments = Department::orderBy('name')->get();
        $advisorOptions = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['instructor', 'instructor_manager']))->orderBy('name')->get(['id', 'name']);
        $selectedAdvisors = $module->advisors()->pluck('users.id')->all();

        return view('modules.edit', compact('module', 'categories', 'departments', 'advisorOptions', 'selectedAdvisors'));
    }

    public function update(Request $request, TrainingModule $module)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'advisors' => ['nullable', 'array'],
            'advisors.*' => ['integer', 'exists:users,id'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'video_file' => ['nullable', 'file', 'mimes:mp4,avi,mov,wmv', 'max:51200'],
            'youtube_link' => ['nullable', 'url', 'max:255'],
            'remove_pdf' => ['nullable', 'boolean'],
            'remove_video' => ['nullable', 'boolean'],
        ]);

        // Handle PDF file upload
        if ($request->hasFile('pdf_file')) {
            // Delete old PDF if exists
            if ($module->pdf_file_path && Storage::disk('public')->exists($module->pdf_file_path)) {
                Storage::disk('public')->delete($module->pdf_file_path);
            }
            $data['pdf_file_path'] = $request->file('pdf_file')->store('modules/pdfs', 'public');
        } elseif ($request->boolean('remove_pdf')) {
            if ($module->pdf_file_path && Storage::disk('public')->exists($module->pdf_file_path)) {
                Storage::disk('public')->delete($module->pdf_file_path);
            }
            $data['pdf_file_path'] = null;
        }

        // Handle video file upload
        if ($request->hasFile('video_file')) {
            // Delete old video if exists
            if ($module->video_file_path && Storage::disk('public')->exists($module->video_file_path)) {
                Storage::disk('public')->delete($module->video_file_path);
            }
            $data['video_file_path'] = $request->file('video_file')->store('modules/videos', 'public');
        } elseif ($request->boolean('remove_video')) {
            if ($module->video_file_path && Storage::disk('public')->exists($module->video_file_path)) {
                Storage::disk('public')->delete($module->video_file_path);
            }
            $data['video_file_path'] = null;
        }

        // Handle YouTube link
        if ($request->filled('youtube_link')) {
            $data['youtube_link'] = $this->extractYouTubeId($request->youtube_link) 
                ? $request->youtube_link 
                : null;
        } elseif ($request->has('youtube_link') && empty($request->youtube_link)) {
            $data['youtube_link'] = null;
        }

        $module->update($data);
        // Advisors are the people a learner can ask for help on this course.
        if ($request->has('advisors')) {
            $module->advisors()->syncWithPivotValues(
                $request->input('advisors', []),
                ['assigned_by' => $request->user()->id]
            );
        }

        return redirect()
            ->route('modules.show', $module)
            ->with('status', 'Training module updated.');
    }

    public function destroy(TrainingModule $module)
    {
        $module->delete();

        return redirect()
            ->route('modules.index')
            ->with('status', 'Training module removed.');
    }

    public function publish(TrainingModule $module)
    {
        $module->update(['status' => 'published']);

        return redirect()
            ->route('modules.show', $module)
            ->with('status', 'Training module published.');
    }

    /**
     * Extract YouTube video ID from URL
     */
    private function extractYouTubeId($url)
    {
        if (empty($url)) {
            return false;
        }

        // Pattern to match YouTube URLs
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/';
        
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }

        return false;
    }
}
