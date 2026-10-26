<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonCompletion;
use App\Models\TrainingModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage lessons')->except(['show', 'complete', 'index']);
    }

    public function index(Request $request)
    {
        $query = Lesson::with(['module'])->latest();
        
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhereHas('module', function($mq) use ($search) {
                      $mq->where('title', 'like', "%{$search}%");
                  });
            });
        }
        
        $lessons = $query->paginate(15)->appends(['search' => $search]);

        return view('lessons.index', compact('lessons'));
    }

    public function show(TrainingModule $module, Lesson $lesson)
    {
        $user = auth()->user();

        abort_if($lesson->training_module_id !== $module->id, 404);

        // Ensure learner is enrolled for this module
        if ($user->hasRole('learner')) {
            $assignment = \App\Models\AssignedModule::where('training_module_id', $module->id)
                ->where('user_id', $user->id)
                ->first();

            if (! $assignment) {
                return redirect()->route('modules.show', $module)
                    ->with('error', 'You are not enrolled in this module or your enrollment is pending approval.');
            }
        }

        $completion = LessonCompletion::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        // Only a submission awaiting review blocks the page. Approved lessons
        // stay readable so learners can revise material they have finished.
        if ($completion && $completion->status === 'pending') {
            return view('retake_requests.locked', [
                'type' => 'lesson',
                'item' => $lesson,
                'module' => $module,
                'existingRequest' => (object) [
                    'status' => 'pending',
                    'created_at' => $completion->created_at,
                ],
                'isCompletionRequest' => true,
            ]);
        }

        return view('lessons.show', compact('module', 'lesson', 'completion'));
    }

    /**
     * Mark a lesson as completed by the authenticated user.
     */
    public function complete(TrainingModule $module, Lesson $lesson)
    {
        $user = auth()->user();

        // ensure lesson belongs to module
        abort_if($lesson->training_module_id !== $module->id, 404);

        // User must be enrolled/assigned
        $assignment = \App\Models\AssignedModule::where('training_module_id', $module->id)
            ->where('user_id', $user->id)
            ->with('assigner') // Eager load assigner
            ->first();

        if (!$assignment) {
            return redirect()->back()->with('error', 'You are not enrolled in this module.');
        }

        $completion = LessonCompletion::firstOrNew([
            'user_id' => $user->id,
            'training_module_id' => $module->id,
            'lesson_id' => $lesson->id,
        ]);

        if ($completion->exists && $completion->status === 'approved') {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'training_module_id' => $module->id,
                    'status' => 'approved',
                ]);
            }

            return redirect()
                ->route('modules.lessons.show', [$module, $lesson])
                ->with('status', 'This lesson is already approved.');
        }

        $isNewSubmission = ! $completion->exists || $completion->status === 'rejected';

        if ($isNewSubmission) {
            $completion->status = 'pending';
            $completion->rejection_reason = null;
            $completion->reviewed_by = null;
            $completion->reviewed_at = null;
            $completion->save();

            $reviewer = $assignment->assigner ?? $module->creator;

            if ($reviewer) {
                $reviewer->notify(new \App\Notifications\WorkflowNotification(
                    type: 'lesson_submitted',
                    message: "{$user->name} submitted the lesson '{$lesson->title}' for review.",
                    url: route('lesson-approvals.index'),
                ));
            }
        }

        $progress = app(\App\Services\ProgressService::class)->recalculate($user->id, $module->id);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'training_module_id' => $module->id,
                'completed_lessons' => $progress->completed_lessons,
                'total_lessons' => $progress->total_lessons,
                'percentage' => $progress->percentage,
                'status' => $completion->status,
                'assignment_status' => $assignment->status,
            ]);
        }

        return redirect()
            ->route('modules.lessons.show', [$module, $lesson])
            ->with('status', 'Lesson submitted. Waiting for your instructor to review it.');
    }

    public function create(Request $request)
    {
        $modules = TrainingModule::orderBy('title')->pluck('title', 'id');

        return view('lessons.create', [
            'modules' => $modules,
            'selectedModule' => $request->integer('module_id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'training_module_id' => ['required', 'exists:training_modules,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'video_file' => ['nullable', 'file', 'mimes:mp4,avi,mov,wmv', 'max:30720'], // 30MB — must stay under PHP's upload_max_filesize (40M)
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'], // 10MB max
            'youtube_link' => ['nullable', 'url', 'max:255'],
            'youtube_links' => ['nullable', 'array'],
            'youtube_links.*' => ['nullable', 'url', 'max:255'],
            'attachment_path' => ['nullable', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1'],
        ]);

        // Handle video file upload (MP4)
        if ($request->hasFile('video_file')) {
            $data['video_path'] = $request->file('video_file')->store('lessons/videos', 'public');
        }

        // Handle PDF file upload
        if ($request->hasFile('pdf_file')) {
            $data['pdf_file_path'] = $request->file('pdf_file')->store('lessons/pdfs', 'public');
        }

        // Handle YouTube links (single and multiple)
        $links = [];
        if ($request->filled('youtube_link')) {
            $links[] = $request->youtube_link;
        }
        if ($request->filled('youtube_links')) {
            $links = array_merge($links, array_filter($request->youtube_links));
        }
        $links = array_values(array_unique(array_filter($links)));

        $data['youtube_link'] = $links[0] ?? null; // keep first for backward compatibility
        $data['youtube_links'] = !empty($links) ? json_encode($links) : null;

        $lesson = Lesson::create($data);

        // Tasks changed: refresh totals for everyone enrolled.
        app(\App\Services\ProgressService::class)->recalculateForModule($lesson->training_module_id);

        return redirect()
            ->route('modules.show', $lesson->module)
            ->with('status', 'Lesson created.');
    }

    public function edit(Lesson $lesson)
    {
        $modules = TrainingModule::orderBy('title')->pluck('title', 'id');

        return view('lessons.edit', compact('lesson', 'modules'));
    }

    public function update(Request $request, Lesson $lesson)
    {
        $data = $request->validate([
            'training_module_id' => ['required', 'exists:training_modules,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'video_file' => ['nullable', 'file', 'mimes:mp4,avi,mov,wmv', 'max:51200'],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'youtube_link' => ['nullable', 'url', 'max:255'],
            'youtube_links' => ['nullable', 'array'],
            'youtube_links.*' => ['nullable', 'url', 'max:255'],
            'attachment_path' => ['nullable', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1'],
            'remove_video' => ['nullable', 'boolean'],
            'remove_pdf' => ['nullable', 'boolean'],
        ]);

        // Handle video file upload (MP4)
        if ($request->hasFile('video_file')) {
            // Delete old video if exists
            if ($lesson->video_path && Storage::disk('public')->exists($lesson->video_path)) {
                Storage::disk('public')->delete($lesson->video_path);
            }
            $data['video_path'] = $request->file('video_file')->store('lessons/videos', 'public');
        } elseif ($request->boolean('remove_video')) {
            if ($lesson->video_path && Storage::disk('public')->exists($lesson->video_path)) {
                Storage::disk('public')->delete($lesson->video_path);
            }
            $data['video_path'] = null;
        }

        // Handle PDF file upload
        if ($request->hasFile('pdf_file')) {
            // Delete old PDF if exists
            if ($lesson->pdf_file_path && Storage::disk('public')->exists($lesson->pdf_file_path)) {
                Storage::disk('public')->delete($lesson->pdf_file_path);
            }
            $data['pdf_file_path'] = $request->file('pdf_file')->store('lessons/pdfs', 'public');
        } elseif ($request->boolean('remove_pdf')) {
            if ($lesson->pdf_file_path && Storage::disk('public')->exists($lesson->pdf_file_path)) {
                Storage::disk('public')->delete($lesson->pdf_file_path);
            }
            $data['pdf_file_path'] = null;
        }

        // Handle YouTube links (single and multiple)
        $links = [];
        if ($request->filled('youtube_link')) {
            $links[] = $request->youtube_link;
        }
        if ($request->filled('youtube_links')) {
            $links = array_merge($links, array_filter($request->youtube_links));
        }
        $links = array_values(array_unique(array_filter($links)));

        if ($request->has('youtube_link') && empty($request->youtube_link) && empty($request->youtube_links)) {
            $data['youtube_link'] = null;
            $data['youtube_links'] = null;
        } else {
            $data['youtube_link'] = $links[0] ?? null; // keep first for backward compatibility
            $data['youtube_links'] = !empty($links) ? json_encode($links) : null;
        }

        $lesson->update($data);

        return redirect()
            ->route('modules.show', $lesson->module)
            ->with('status', 'Lesson updated.');
    }

    public function destroy(Lesson $lesson)
    {
        $module = $lesson->module;
        
        // Delete associated files
        if ($lesson->video_path && Storage::disk('public')->exists($lesson->video_path)) {
            Storage::disk('public')->delete($lesson->video_path);
        }
        if ($lesson->pdf_file_path && Storage::disk('public')->exists($lesson->pdf_file_path)) {
            Storage::disk('public')->delete($lesson->pdf_file_path);
        }
        
        $lesson->delete();

        // A task was removed: shrink the totals for enrolled learners.
        app(\App\Services\ProgressService::class)->recalculateForModule($module->id);

        return redirect()
            ->route('modules.show', $module)
            ->with('status', 'Lesson removed.');
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
