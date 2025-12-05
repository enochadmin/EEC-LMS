<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeFile;
use App\Models\Department;
use App\Models\TrainingModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KnowledgeFileController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage knowledge')->except(['index', 'download']);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $query = KnowledgeFile::with(['uploader', 'department', 'module'])->latest();
        
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Optional filters
        $departmentId = $request->integer('department_id');
        $moduleId = $request->integer('module_id');

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($moduleId) {
            $query->where('training_module_id', $moduleId);
        }
        
        $files = $query->paginate(10)->appends([
            'search' => $search,
            'department_id' => $departmentId,
            'module_id' => $moduleId,
        ]);

        $departments = Department::orderBy('name')->get();
        $modules = TrainingModule::orderBy('title')->get();

        return view('knowledge.index', compact('files', 'departments', 'modules', 'departmentId', 'moduleId'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();
        $modules = TrainingModule::orderBy('title')->get();

        return view('knowledge.create', compact('departments', 'modules'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'file' => ['required', 'file', 'max:5120'],
            'department_id' => ['required', 'exists:departments,id'],
            'training_module_id' => ['required', 'exists:training_modules,id'],
        ]);

        $path = $request->file('file')->store('knowledge');

        KnowledgeFile::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'file_path' => $path,
            'uploaded_by' => $request->user()->id,
            'department_id' => $data['department_id'],
            'training_module_id' => $data['training_module_id'],
        ]);

        return redirect()
            ->route('knowledge.index')
            ->with('status', 'Knowledge file uploaded.');
    }

    public function edit(KnowledgeFile $knowledge)
    {
        $departments = Department::orderBy('name')->get();
        $modules = TrainingModule::orderBy('title')->get();

        return view('knowledge.edit', [
            'knowledge' => $knowledge,
            'departments' => $departments,
            'modules' => $modules,
        ]);
    }

    public function update(Request $request, KnowledgeFile $knowledge)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:5120'],
            'department_id' => ['required', 'exists:departments,id'],
            'training_module_id' => ['required', 'exists:training_modules,id'],
        ]);

        if ($request->hasFile('file')) {
            if (Storage::exists($knowledge->file_path)) {
                Storage::delete($knowledge->file_path);
            }

            $knowledge->file_path = $request->file('file')->store('knowledge');
        }

        $knowledge->title = $data['title'];
        $knowledge->description = $data['description'] ?? null;
        $knowledge->department_id = $data['department_id'];
        $knowledge->training_module_id = $data['training_module_id'];
        $knowledge->save();

        return redirect()
            ->route('knowledge.index')
            ->with('status', 'Knowledge file updated.');
    }

    public function destroy(KnowledgeFile $knowledge)
    {
        if (Storage::exists($knowledge->file_path)) {
            Storage::delete($knowledge->file_path);
        }

        $knowledge->delete();

        return redirect()
            ->route('knowledge.index')
            ->with('status', 'Knowledge file deleted.');
    }

    public function download(KnowledgeFile $knowledge)
    {
        if (!Storage::disk('local')->exists($knowledge->file_path)) {
            return redirect()
                ->back()
                ->with('status', 'File not found.');
        }

        return Storage::disk('local')->download($knowledge->file_path, $knowledge->title);
    }
}

