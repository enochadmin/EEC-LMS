<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
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

        // Check if lesson is completed
        // Check if lesson is completed (approved)
        $completion = \App\Models\LessonCompletion::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        if ($completion) {
            if ($completion->status === 'approved') {
                // Check for pending retake request
                $existingRequest = \App\Models\RetakeRequest::where('user_id', $user->id)
                    ->where('requestable_type', \App\Models\Lesson::class)
                    ->where('requestable_id', $lesson->id)
                    ->where('status', 'pending')
                    ->first();

                return view('retake_requests.locked', [
                    'type' => 'lesson',
                    'item' => $lesson,
                    'module' => $module,
                    'existingRequest' => $existingRequest
                ]);
            } elseif ($completion->status === 'pending') {
                // Show pending approval view
                 return view('retake_requests.locked', [
                    'type' => 'lesson',
                    'item' => $lesson,
                    'module' => $module,
                    'existingRequest' => (object)['status' => 'pending_completion', 'created_at' => $completion->created_at], // Mock request object
                    'isCompletionRequest' => true
                ]);
            }
        }

        return view('lessons.show', compact('module', 'lesson'));
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

        // create lesson completion if not exists
        $created = \App\Models\LessonCompletion::firstOrCreate(
            [
                'user_id' => $user->id,
                'training_module_id' => $module->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'status' => 'pending', // Default to pending for approval
            ]
        );

        if ($created->wasRecentlyCreated) {
             \Illuminate\Support\Facades\Log::info("Lesson completion requested by user {$user->id} for lesson {$lesson->id}. Status: pending");
             
             // Notify instructor about the request
             // We can reuse the ModuleCompletedNotification logic but for Lesson Completion Request?
             // Or just rely on the dashboard.
             // For now, let's log it.
        }

        // Only count APPROVED completions for progress
        $completedCount = \App\Models\LessonCompletion::where('training_module_id', $module->id)
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->count();

        $totalLessons = $module->lessons()->count();

        // Lessons contribute 50% of total module score
        $lessonPortion = 50;
        $lessonPercentage = $totalLessons > 0 ? ($completedCount / $totalLessons) * $lessonPortion : 0;

        // Load or init progress and recompute aggregate
        $progress = \App\Models\ModuleProgress::firstOrNew([
                'training_module_id' => $module->id,
                'user_id' => $user->id,
        ]);

        $progress->completed_lessons = $completedCount;
        $progress->total_lessons = $totalLessons;
        $progress->lesson_percentage = $lessonPercentage;
        $progress->quiz_percentage = $progress->quiz_percentage ?? 0;
        $progress->percentage = round(($progress->lesson_percentage ?? 0) + ($progress->quiz_percentage ?? 0), 1);
        $progress->last_accessed_at = now();
        $progress->save();

        // If fully completed, notify instructor/manager
        if ($percentage >= 100) {
            // NOTE: We do NOT automatically mark assignment as completed. 
            // The instructor must review and manually complete it.
            
            \Illuminate\Support\Facades\Log::info("Module completed by user {$user->id}. Checking for notification target.");

            // Notify the person who assigned the module
            if ($assignment && $assignment->assigner) {
                \Illuminate\Support\Facades\Log::info("Notifying assigner: {$assignment->assigner->id}");
                $assignment->assigner->notify(new \App\Notifications\ModuleCompletedNotification($module, $user));
            } elseif ($module->creator) {
                // Fallback to creator if no specific assigner (e.g. self-enrolled)
                \Illuminate\Support\Facades\Log::info("Notifying creator: {$module->creator->id}");
                $module->creator->notify(new \App\Notifications\ModuleCompletedNotification($module, $user));
            } else {
                \Illuminate\Support\Facades\Log::warning("No one to notify for module completion. Module ID: {$module->id}");
            }
        }

        // If this was an AJAX/json request, return the updated progress payload
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'training_module_id' => $module->id,
                'completed_lessons' => $completedCount,
                'total_lessons' => $totalLessons,
                'percentage' => $progress->percentage ?? null,
                'assignment_status' => $assignment ? $assignment->status : null,
            ]);
        }

        return redirect()->route('modules.show', $module)->with('status', 'Lesson completion requested. Waiting for approval.');
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
            'video_file' => ['nullable', 'file', 'mimes:mp4,avi,mov,wmv', 'max:51200'], // 50MB max for MP4
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
