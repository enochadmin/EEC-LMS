<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\TrainingModule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * "Create module from template" — a three-step flow:
 *
 *   1. structure()        instructor names lessons/quizzes (no content yet)
 *   2. content()          downloads a CSV pre-filled with that structure,
 *                         fills in descriptions/links, uploads it back with media
 *   3. store()            one transaction creates module + lessons + quizzes + questions
 *
 * The draft lives in the session between steps so nothing partial is written
 * to the database until the instructor confirms.
 */
class ModuleTemplateController extends Controller
{
    private const DRAFT_KEY = 'module_template_draft';

    public function __construct()
    {
        $this->middleware('permission:manage modules');
    }

    // ---------------------------------------------------------------------
    // Step 1 — structure
    // ---------------------------------------------------------------------

    public function structure(Request $request)
    {
        $categories = Category::orderBy('name')->pluck('name', 'id');
        $departments = Department::orderBy('name')->get();
        $advisorOptions = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['instructor', 'instructor_manager']))->orderBy('name')->get(['id', 'name']);

        return view('modules.create-template-structure', [
            'categories' => $categories,
            'departments' => $departments,
            'advisorOptions' => $advisorOptions,
            'draft' => session(self::DRAFT_KEY),
        ]);
    }

    public function storeStructure(Request $request)
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
            'structure' => ['required', 'string'],
        ]);

        $rows = json_decode($data['structure'], true);

        if (! is_array($rows) || count($rows) === 0) {
            return back()
                ->withInput()
                ->withErrors(['structure' => 'Add at least one lesson or quiz to the structure.']);
        }

        $clean = [];
        foreach (array_values($rows) as $index => $row) {
            $type = ($row['type'] ?? 'lesson') === 'quiz' ? 'quiz' : 'lesson';
            $title = trim((string) ($row['title'] ?? ''));

            if ($title === '') {
                return back()
                    ->withInput()
                    ->withErrors(['structure' => 'Row ' . ($index + 1) . ' has no title.']);
            }

            $clean[] = [
                'type' => $type,
                'title' => mb_substr($title, 0, 255),
                'minutes' => $type === 'lesson' ? max((int) ($row['minutes'] ?? 0), 0) : 0,
                'passing_score' => $type === 'quiz'
                    ? min(max((int) ($row['passing_score'] ?? 70), 1), 100)
                    : null,
            ];
        }

        session([self::DRAFT_KEY => [
            'title' => $data['title'],
            'category_id' => (int) $data['category_id'],
            'description' => $data['description'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'department_id' => $data['department_id'] ?? null,
            'advisors' => array_map('intval', $data['advisors'] ?? []),
            'rows' => $clean,
        ]]);

        return redirect()->route('modules.create.template.content');
    }

    // ---------------------------------------------------------------------
    // Step 2 — content
    // ---------------------------------------------------------------------

    public function content()
    {
        $draft = session(self::DRAFT_KEY);

        if (! $draft) {
            return redirect()
                ->route('modules.create.template')
                ->with('error', 'Start by defining the module structure.');
        }

        return view('modules.create-template-content', [
            'draft' => $draft,
            'categoryName' => Category::find($draft['category_id'])->name ?? '—',
            'lessonCount' => collect($draft['rows'])->where('type', 'lesson')->count(),
            'quizCount' => collect($draft['rows'])->where('type', 'quiz')->count(),
        ]);
    }

    /**
     * CSV is used rather than .xlsx on purpose: it opens natively in Excel and
     * Google Sheets and needs no extra PHP spreadsheet dependency in production.
     */
    public function downloadTemplate()
    {
        $draft = session(self::DRAFT_KEY);

        if (! $draft) {
            return redirect()->route('modules.create.template');
        }

        $lessons = collect($draft['rows'])->where('type', 'lesson')->values();
        $quizzes = collect($draft['rows'])->where('type', 'quiz')->values();
        $filename = 'module-template-' . str()->slug($draft['title']) . '.csv';

        return response()->streamDownload(function () use ($draft, $lessons, $quizzes) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM so Excel does not mangle accented characters.
            fputs($out, "\xEF\xBB\xBF");

            $line = fn (array $row) => fputcsv($out, $row, ',', '"');

            $line(['#MODULE']);
            $line(['title', 'description', 'duration_minutes']);
            $line([$draft['title'], $draft['description'] ?? '', $draft['duration_minutes'] ?? '']);

            $line([]);
            $line(['#LESSONS']);
            $line(['lesson_no', 'title', 'estimated_minutes', 'content', 'youtube_link', 'media_file', 'attachment_file']);
            foreach ($lessons as $i => $lesson) {
                $line([
                    $i + 1,
                    $lesson['title'],
                    $lesson['minutes'] ?: '',
                    '', // content
                    '', // youtube link
                    '', // media file name
                    '', // attachment file name
                ]);
            }

            $line([]);
            $line(['#QUIZZES']);
            $line(['quiz_no', 'title', 'passing_score']);
            foreach ($quizzes as $i => $quiz) {
                $line([$i + 1, $quiz['title'], $quiz['passing_score'] ?? 70]);
            }

            $line([]);
            $line(['#QUESTIONS']);
            $line(['quiz_no', 'question', 'points', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_option']);
            foreach ($quizzes as $i => $quiz) {
                // Three starter rows per quiz; empty question cells are skipped.
                for ($r = 0; $r < 3; $r++) {
                    $line([$i + 1, '', 1, '', '', '', '', '']);
                }
            }

            $line([]);
            $line(['#NOTES']);
            $line(['Fill a row per lesson/quiz. Leave a question row empty to skip it.']);
            $line(['correct_option accepts a, b, c, d (or 1-4). Leave option cells empty for a written-answer question.']);
            $line(['media_file / attachment_file must match the exact filename you upload in the browser.']);

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ---------------------------------------------------------------------
    // Step 3 — create
    // ---------------------------------------------------------------------

    public function store(Request $request)
    {
        $draft = session(self::DRAFT_KEY);

        if (! $draft) {
            return redirect()
                ->route('modules.create.template')
                ->with('error', 'Your draft expired. Start the template again.');
        }

        $request->validate([
            'template_file' => ['nullable', 'file', 'mimes:csv,txt', 'max:5120'],
            'media_files' => ['nullable', 'array', 'max:40'],
            'media_files.*' => ['file', 'max:102400'], // 100 MB each
        ]);

        $sections = [];
        if ($request->hasFile('template_file')) {
            $parsed = $this->parseCsv($request->file('template_file')->getRealPath());

            if ($parsed === null) {
                return back()->with('error', 'The uploaded file is not a valid template. Download a fresh copy and keep the #SECTION rows.');
            }

            $sections = $parsed;
        }

        $uploads = $this->indexUploads($request->file('media_files', []));

        $warnings = [];
        $createdLessonIds = [];

        DB::beginTransaction();

        try {
            $module = TrainingModule::create([
                'title' => $draft['title'],
                'category_id' => $draft['category_id'],
                'department_id' => $draft['department_id'] ?? null,
                'description' => $draft['description'],
                'duration_minutes' => $draft['duration_minutes'],
                'status' => $draft['status'] ?? 'draft',
                'created_by' => $request->user()->id,
            ]);

            // ---- Lessons ---------------------------------------------------
            $lessonRows = $this->indexByPosition($sections['LESSONS'] ?? [], 'lesson_no');
            if (! empty($draft['advisors'])) {
            $module->advisors()->syncWithPivotValues(
                $draft['advisors'],
                ['assigned_by' => $request->user()->id]
            );
        }
        $lessons = collect($draft['rows'])->where('type', 'lesson')->values();

            foreach ($lessons as $index => $row) {
                $source = $lessonRows[$index + 1] ?? [];

                $lesson = $module->lessons()->create([
                    'title' => $row['title'],
                    'content' => $this->nullIfBlank($source['content'] ?? null),
                    'youtube_link' => $this->nullIfBlank($source['youtube_link'] ?? null),
                    'estimated_minutes' => (int) ($source['estimated_minutes'] ?? 0) ?: ($row['minutes'] ?: null),
                    'order' => $index + 1,
                ]);

                $lesson->update($this->resolveFiles($lesson, $source, $uploads, $warnings));
                $createdLessonIds[] = $lesson->id;
            }

            // ---- Quizzes + questions ---------------------------------------
            $quizRows = $this->indexByPosition($sections['QUIZZES'] ?? [], 'quiz_no');
            $quizzes = collect($draft['rows'])->where('type', 'quiz')->values();
            $quizMap = []; // quiz_no => Quiz

            foreach ($quizzes as $index => $row) {
                $source = $quizRows[$index + 1] ?? [];

                $quiz = Quiz::create([
                    'training_module_id' => $module->id,
                    'title' => $row['title'],
                    'passing_score' => (int) ($source['passing_score'] ?? 0) ?: ($row['passing_score'] ?? 70),
                ]);

                $quizMap[$index + 1] = $quiz;
            }

            foreach ($sections['QUESTIONS'] ?? [] as $line => $row) {
                $questionText = trim((string) ($row['question'] ?? ''));

                if ($questionText === '') {
                    continue; // starter row left empty
                }

                $quizNo = (int) ($row['quiz_no'] ?? 0);
                $quiz = $quizMap[$quizNo] ?? null;

                if (! $quiz) {
                    $warnings[] = 'Question skipped — unknown quiz_no ' . $quizNo . '.';
                    continue;
                }

                $question = Question::create([
                    'quiz_id' => $quiz->id,
                    'question' => $questionText,
                    'points' => max((int) ($row['points'] ?? 1), 0),
                ]);

                $options = [];
                foreach (['a', 'b', 'c', 'd'] as $letter) {
                    $text = trim((string) ($row['option_' . $letter] ?? ''));
                    if ($text !== '') {
                        $options[$letter] = $text;
                    }
                }

                if ($options === []) {
                    continue; // written-answer question
                }

                $correct = $this->normalizeCorrect($row['correct_option'] ?? '', array_keys($options));

                foreach ($options as $letter => $text) {
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'option_text' => $text,
                        'is_correct' => $correct === $letter,
                    ]);
                }

                if ($correct === null) {
                    $warnings[] = 'Question "' . str()->limit($questionText, 40) . '" has no valid correct_option.';
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->with('error', 'Could not create the module: ' . $e->getMessage());
        }

        session()->forget(self::DRAFT_KEY);

        $summary = sprintf(
            'Module created from template — %d lesson(s), %d quiz(zes).',
            count($createdLessonIds),
            count($quizMap)
        );

        if ($warnings !== []) {
            $summary .= ' ' . implode(' ', array_slice($warnings, 0, 5));
        }

        return redirect()
            ->route('modules.show', $module)
            ->with('status', $summary);
    }

    /**
     * Attach uploaded media to a lesson by matching the filename written in the
     * CSV against the files actually uploaded. Unmatched names are reported
     * instead of failing the whole import.
     */
    private function resolveFiles($lesson, array $source, array $uploads, array &$warnings): array
    {
        $attributes = [];

        foreach (['media_file' => 'video', 'attachment_file' => 'attachment'] as $column => $kind) {
            $name = trim((string) ($source[$column] ?? ''));

            if ($name === '') {
                continue;
            }

            $file = $uploads[strtolower(basename($name))] ?? null;

            if (! $file) {
                $warnings[] = 'Upload missing for "' . $name . '" (lesson: ' . $lesson->title . ').';
                continue;
            }

            $extension = strtolower($file->getClientOriginalExtension());
            $path = $file->store($kind === 'video' ? 'lessons/videos' : 'lessons/files', 'public');

            if ($kind === 'video') {
                $attributes['video_path'] = $path;
            } elseif ($extension === 'pdf') {
                $attributes['pdf_file_path'] = $path;
            } else {
                $attributes['attachment_path'] = $path;
            }
        }

        return $attributes;
    }

    /**
     * @return array<string, \Illuminate\Http\UploadedFile>
     */
    private function indexUploads(array $files): array
    {
        $map = [];

        foreach ($files as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }
            $map[strtolower($file->getClientOriginalName())] = $file;
        }

        return $map;
    }

    /**
     * Read the sectioned CSV produced by downloadTemplate().
     * Returns null when no recognisable section is present.
     */
    private function parseCsv(string $path): ?array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return null;
        }

        $sections = [];
        $current = null;
        $headers = null;
        $sawSection = false;

        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === []) {
                continue;
            }

            $first = trim((string) ($row[0] ?? ''));
            $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);

            if ($first === '') {
                continue;
            }

            if (str_starts_with($first, '#')) {
                $current = strtoupper(trim(ltrim($first, '#')));
                $headers = null;
                $sawSection = true;
                continue;
            }

            if ($headers === null) {
                $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $row);
                continue;
            }

            if ($current === null) {
                continue;
            }

            $assoc = [];
            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }
                $assoc[$header] = trim((string) ($row[$index] ?? ''));
            }

            $sections[$current][] = $assoc;
        }

        fclose($handle);

        return $sawSection ? $sections : null;
    }

    private function indexByPosition(array $rows, string $key): array
    {
        $indexed = [];

        foreach ($rows as $row) {
            $position = (int) ($row[$key] ?? 0);
            if ($position > 0) {
                $indexed[$position] = $row;
            }
        }

        return $indexed;
    }

    private function normalizeCorrect(?string $value, array $availableLetters): ?string
    {
        $value = strtolower(trim((string) $value));

        if ($value === '') {
            return null;
        }

        $map = ['1' => 'a', '2' => 'b', '3' => 'c', '4' => 'd'];
        $letter = $map[$value] ?? $value;

        if (! in_array($letter, $availableLetters, true)) {
            $letter = substr($letter, 0, 1);
        }

        return in_array($letter, $availableLetters, true) ? $letter : null;
    }

    private function nullIfBlank(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return ($value === '' || $value === null) ? null : $value;
    }
}
