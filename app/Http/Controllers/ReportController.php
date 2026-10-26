<?php

namespace App\Http\Controllers;

use App\Models\AssignedModule;
use App\Models\Certificate;
use App\Models\Department;
use App\Models\LearningTimeLog;
use App\Models\ModuleProgress;
use App\Models\TrainingModule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Monitoring reports for admins (all modules) and instructors (their modules).
 *
 * Every figure on the page comes from one of three sources:
 *   - assigned_modules    → who is enrolled, when
 *   - module_progress     → how far each learner has got
 *   - learning_time_logs  → how long each learner actually spent
 *
 * The same filters (date range, course, department) are applied to all of
 * them, so the KPI cards, charts and tables always describe the same slice.
 */
class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();

            abort_unless(
                $user && ($user->hasRole('admin') || $user->hasRole('instructor')),
                403
            );

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->hasRole('admin');

        [$from, $to] = $this->resolveRange($request);
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->endOfDay();

        // ---- Scope: which modules may this user report on? -----------------
        $moduleQuery = TrainingModule::query();

        if (! $isAdmin) {
            $allowedIds = AssignedModule::where('assigned_by', $user->id)
                ->pluck('training_module_id')
                ->merge(TrainingModule::where('created_by', $user->id)->pluck('id'))
                ->unique()
                ->values();

            $moduleQuery->whereIn('id', $allowedIds);
        }

        $modules = $moduleQuery->orderBy('title')->get(['id', 'title']);
        $moduleIds = $modules->pluck('id');

        $selectedModule = $request->filled('module_id') ? $request->integer('module_id') : null;
        if ($selectedModule) {
            $moduleIds = $moduleIds->intersect([$selectedModule])->values();
        }

        $departments = Department::orderBy('name')->get(['id', 'name']);
        $selectedDepartment = $request->filled('department_id') ? $request->integer('department_id') : null;

        $deptUserIds = $selectedDepartment
            ? User::where('department_id', $selectedDepartment)->pluck('id')
            : null;

        // ---- Base scopes ---------------------------------------------------
        $assignmentBase = function () use ($moduleIds, $deptUserIds) {
            $q = AssignedModule::whereIn('training_module_id', $moduleIds);
            if ($deptUserIds !== null) {
                $q->whereIn('user_id', $deptUserIds);
            }
            return $q;
        };

        $progressBase = function () use ($moduleIds, $deptUserIds) {
            $q = ModuleProgress::whereIn('training_module_id', $moduleIds)
                // Only count progress that belongs to an actual enrolment,
                // otherwise staff who browsed a module inflate completion.
                ->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('assigned_modules')
                        ->whereColumn('assigned_modules.user_id', 'module_progress.user_id')
                        ->whereColumn('assigned_modules.training_module_id', 'module_progress.training_module_id');
                });
            if ($deptUserIds !== null) {
                $q->whereIn('user_id', $deptUserIds);
            }
            return $q;
        };

        $timeBase = function () use ($moduleIds, $deptUserIds) {
            $q = LearningTimeLog::whereIn('training_module_id', $moduleIds);
            if ($deptUserIds !== null) {
                $q->whereIn('user_id', $deptUserIds);
            }
            return $q;
        };

        // ---- KPI cards -----------------------------------------------------
        $enrolledLearners = (clone $assignmentBase())->distinct()->count('user_id');
        $newEnrollments = (clone $assignmentBase())
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->count();

        $activeLearners = (clone $timeBase())
            ->whereBetween('tracked_on', [$from, $to])
            ->distinct()
            ->count('user_id');

        $totalSeconds = (int) (clone $timeBase())
            ->whereBetween('tracked_on', [$from, $to])
            ->sum('seconds');

        // Completion rate must be per enrolment (module-user pair), not per
        // distinct learner: one learner enrolled twice is two enrolments.
        $totalEnrollments = (clone $assignmentBase())->count();
        $completedEnrollments = (clone $progressBase())->where('percentage', '>=', 100)->count();
        $avgProgress = round((float) ((clone $progressBase())->avg('percentage') ?? 0), 1);

        $certificatesIssued = Certificate::whereIn('training_module_id', $moduleIds)
            ->when($deptUserIds !== null, fn ($q) => $q->whereIn('user_id', $deptUserIds))
            ->whereBetween('issued_at', [$from, $to])
            ->count();

        $kpis = [
            'enrolled_learners' => $enrolledLearners,
            'new_enrollments' => $newEnrollments,
            'active_learners' => $activeLearners,
            'total_seconds' => $totalSeconds,
            'total_enrollments' => $totalEnrollments,
            'completed_enrollments' => $completedEnrollments,
            'avg_progress' => $avgProgress,
            'certificates' => $certificatesIssued,
            'completion_rate' => $totalEnrollments > 0
                ? min(100.0, round($completedEnrollments / $totalEnrollments * 100, 1))
                : 0.0,
        ];

        // ---- Enrollments per course (period) -------------------------------
        $enrollmentsByCourse = (clone $assignmentBase())
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->select(
                'training_module_id',
                DB::raw('COUNT(*) as enrollments'),
                DB::raw('COUNT(DISTINCT user_id) as learners')
            )
            ->groupBy('training_module_id')
            ->orderByDesc('enrollments')
            ->get()
            ->map(fn ($row) => [
                'module_id' => $row->training_module_id,
                'title' => $modules->firstWhere('id', $row->training_module_id)->title ?? 'Removed module',
                'enrollments' => (int) $row->enrollments,
                'learners' => (int) $row->learners,
            ]);

        // ---- Trending: this period vs the equally long previous period -----
        $periodDays = max(Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1, 1);
        $prevFrom = Carbon::parse($from)->subDays($periodDays);
        $prevTo = Carbon::parse($from)->subDay();

        $currentCounts = (clone $assignmentBase())
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->select('training_module_id', DB::raw('COUNT(*) as total'))
            ->groupBy('training_module_id')
            ->pluck('total', 'training_module_id');

        $previousCounts = (clone $assignmentBase())
            ->whereBetween('created_at', [$prevFrom->copy()->startOfDay(), $prevTo->copy()->endOfDay()])
            ->select('training_module_id', DB::raw('COUNT(*) as total'))
            ->groupBy('training_module_id')
            ->pluck('total', 'training_module_id');

        $trending = $moduleIds->map(function ($id) use ($modules, $currentCounts, $previousCounts) {
            $current = (int) ($currentCounts[$id] ?? 0);
            $previous = (int) ($previousCounts[$id] ?? 0);
            $growth = $previous > 0
                ? round((($current - $previous) / $previous) * 100, 1)
                : ($current > 0 ? 100.0 : 0.0);

            return [
                'module_id' => $id,
                'title' => $modules->firstWhere('id', $id)->title ?? 'Removed module',
                'current' => $current,
                'previous' => $previous,
                'growth' => $growth,
                'is_new' => $previous === 0 && $current > 0,
            ];
        })
            ->sortByDesc(fn ($row) => [$row['growth'], $row['current']])
            ->take(3)
            ->values();

        $trendingModule = $trending->first();

        // ---- Enrollment trend over the selected window ---------------------
        $rawDaily = (clone $assignmentBase())
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $trendGranularity = $periodDays > 62 ? 'month' : 'day';
        $trendSeries = $this->buildTrend($rawDaily, Carbon::parse($from), Carbon::parse($to), $trendGranularity);

        // ---- Time on course -------------------------------------------------
        $timeByCourse = (clone $timeBase())
            ->whereBetween('tracked_on', [$from, $to])
            ->select(
                'training_module_id',
                DB::raw('SUM(seconds) as total_seconds'),
                DB::raw('COUNT(DISTINCT user_id) as learners')
            )
            ->groupBy('training_module_id')
            ->orderByDesc('total_seconds')
            ->get()
            ->map(fn ($row) => [
                'module_id' => $row->training_module_id,
                'title' => $modules->firstWhere('id', $row->training_module_id)->title ?? 'Removed module',
                'seconds' => (int) $row->total_seconds,
                'learners' => (int) $row->learners,
            ]);

        // ---- Completion mix -------------------------------------------------
        $progressRows = (clone $progressBase())->get(['percentage']);
        $completed = $progressRows->where('percentage', '>=', 100)->count();
        $inProgress = $progressRows->filter(fn ($r) => $r->percentage > 0 && $r->percentage < 100)->count();
        $progressRowsCount = $progressRows->count();
        $notStarted = max($kpis['total_enrollments'] - ($completed + $inProgress), 0);

        $completionMix = [
            'completed' => $completed,
            'in_progress' => $inProgress,
            'not_started' => $notStarted,
            'tracked_rows' => $progressRowsCount,
        ];

        // ---- Department mix (learners enrolled) ------------------------------
        $departmentMix = User::query()
            ->select(DB::raw('COALESCE(departments.name, "Unassigned") as department'), DB::raw('COUNT(DISTINCT users.id) as learners'))
            ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
            ->whereIn('users.id', (clone $assignmentBase())->select('user_id'))
            ->when($deptUserIds !== null, fn ($q) => $q->whereIn('users.id', $deptUserIds))
            ->groupBy('department')
            ->orderByDesc('learners')
            ->get();

        // ---- Learner × course time (top rows) --------------------------------
        $learnerTime = (clone $timeBase())
            ->whereBetween('tracked_on', [$from, $to])
            ->select(
                'user_id',
                'training_module_id',
                DB::raw('SUM(seconds) as total_seconds'),
                DB::raw('MAX(tracked_on) as last_seen')
            )
            ->groupBy('user_id', 'training_module_id')
            ->orderByDesc('total_seconds')
            ->limit(25)
            ->get();

        $learnerIds = $learnerTime->pluck('user_id')->unique();
        $learners = User::with('department')->whereIn('id', $learnerIds)->get()->keyBy('id');
        $lessonsCompletedMap = DB::table('lesson_completions')
            ->where('status', 'approved')
            ->whereIn('user_id', $learnerIds)
            ->select('user_id', 'training_module_id', DB::raw('COUNT(*) as total'))
            ->groupBy('user_id', 'training_module_id')
            ->get()
            ->keyBy(fn ($row) => $row->user_id . ':' . $row->training_module_id);

        $learnerTimeRows = $learnerTime->map(function ($row) use ($learners, $modules, $lessonsCompletedMap) {
            $learner = $learners->get($row->user_id);

            return [
                'user' => $learner->name ?? 'Removed user',
                'department' => $learner->department->name ?? '—',
                'module' => $modules->firstWhere('id', $row->training_module_id)->title ?? 'Removed module',
                'seconds' => (int) $row->total_seconds,
                'lessons_completed' => (int) ($lessonsCompletedMap[$row->user_id . ':' . $row->training_module_id]->total ?? 0),
                'last_seen' => $row->last_seen,
            ];
        });

        // ---- Course performance table ----------------------------------------
        $enrolledPerModule = (clone $assignmentBase())
            ->select('training_module_id', DB::raw('COUNT(DISTINCT user_id) as learners'))
            ->groupBy('training_module_id')
            ->pluck('learners', 'training_module_id');

        $completedPerModule = (clone $progressBase())
            ->completed()
            ->select('training_module_id', DB::raw('COUNT(*) as total'))
            ->groupBy('training_module_id')
            ->pluck('total', 'training_module_id');

        $avgProgressPerModule = (clone $progressBase())
            ->select('training_module_id', DB::raw('AVG(percentage) as avg_progress'))
            ->groupBy('training_module_id')
            ->pluck('avg_progress', 'training_module_id');

        $timePerModule = (clone $timeBase())
            ->whereBetween('tracked_on', [$from, $to])
            ->select('training_module_id', DB::raw('SUM(seconds) as total_seconds'))
            ->groupBy('training_module_id')
            ->pluck('total_seconds', 'training_module_id');

        $lastActivityPerModule = (clone $timeBase())
            ->select('training_module_id', DB::raw('MAX(tracked_on) as last_seen'))
            ->groupBy('training_module_id')
            ->pluck('last_seen', 'training_module_id');

        $courses = $modules->map(function ($module) use (
            $enrolledPerModule,
            $completedPerModule,
            $avgProgressPerModule,
            $timePerModule,
            $lastActivityPerModule
        ) {
            $learners = (int) ($enrolledPerModule[$module->id] ?? 0);
            $completed = (int) ($completedPerModule[$module->id] ?? 0);
            $seconds = (int) ($timePerModule[$module->id] ?? 0);

            return [
                'module_id' => $module->id,
                'title' => $module->title,
                'learners' => $learners,
                'completed' => $completed,
                'completion_rate' => $learners > 0 ? min(100.0, round($completed / $learners * 100, 1)) : 0.0,
                'avg_progress' => min(100.0, round((float) ($avgProgressPerModule[$module->id] ?? 0), 1)),
                'seconds' => $seconds,
                'avg_seconds_per_learner' => $learners > 0 ? (int) round($seconds / $learners) : 0,
                'last_seen' => $lastActivityPerModule[$module->id] ?? null,
            ];
        })
            ->sortByDesc('learners')
            ->values();

        // ---- Quiz performance -------------------------------------------------
        $quizPerformance = DB::table('quiz_attempts')
            ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
            ->whereIn('quizzes.training_module_id', $moduleIds)
            ->when($deptUserIds !== null, fn ($q) => $q->whereIn('quiz_attempts.user_id', $deptUserIds))
            ->whereNotNull('quiz_attempts.submitted_at')
            ->whereBetween('quiz_attempts.created_at', [$fromDate, $toDate])
            ->select(
                'quizzes.id',
                'quizzes.title',
                DB::raw('COUNT(*) as attempts'),
                DB::raw('COUNT(DISTINCT quiz_attempts.user_id) as takers'),
                DB::raw('SUM(CASE WHEN quiz_attempts.status = "passed" THEN 1 ELSE 0 END) as passed'),
                DB::raw('AVG(quiz_attempts.score) as avg_score')
            )
            ->groupBy('quizzes.id', 'quizzes.title')
            ->orderByDesc('attempts')
            ->get()
            ->map(fn ($row) => [
                'title' => $row->title ?? 'Untitled quiz',
                'attempts' => (int) $row->attempts,
                'takers' => (int) $row->takers,
                'passed' => (int) $row->passed,
                'pass_rate' => $row->attempts > 0 ? round($row->passed / $row->attempts * 100, 1) : 0.0,
                'avg_score' => round((float) $row->avg_score, 1),
            ]);

        $timeTrackingEnabled = LearningTimeLog::whereIn('training_module_id', $moduleIds)->exists();

        return view('reports.index', [
            'kpis' => $kpis,
            'enrollmentsByCourse' => $enrollmentsByCourse,
            'trending' => $trending,
            'trendingModule' => $trendingModule,
            'trendSeries' => $trendSeries,
            'trendGranularity' => $trendGranularity,
            'timeByCourse' => $timeByCourse,
            'completionMix' => $completionMix,
            'departmentMix' => $departmentMix,
            'learnerTimeRows' => $learnerTimeRows,
            'courses' => $courses,
            'quizPerformance' => $quizPerformance,
            'modules' => $modules,
            'departments' => $departments,
            'selectedModule' => $selectedModule,
            'selectedDepartment' => $selectedDepartment,
            'from' => $from,
            'to' => $to,
            'isAdmin' => $isAdmin,
            'timeTrackingEnabled' => $timeTrackingEnabled,
        ]);
    }

    /**
     * CSV export of a report table.
     *
     * Renders index() and reads its view data, so the file is guaranteed to
     * match what the user is looking at — same filters, same numbers.
     */
    public function export(Request $request)
    {
        $data = $this->index($request)->getData();
        $type = (string) $request->query('type', 'courses');

        $header = [];
        $body = [];

        if ($type === 'learners') {
            $header = ['Learner', 'Department', 'Course', 'Time spent (hours)', 'Lessons completed', 'Last active'];

            foreach ($data['learnerTimeRows'] as $row) {
                $body[] = [
                    $row['user'],
                    $row['department'],
                    $row['module'],
                    round($row['seconds'] / 3600, 2),
                    $row['lessons_completed'],
                    $row['last_seen'],
                ];
            }
        } elseif ($type === 'quizzes') {
            $header = ['Quiz', 'Attempts', 'Learners', 'Passed', 'Pass rate (%)', 'Average score'];

            foreach ($data['quizPerformance'] as $row) {
                $body[] = [
                    $row['title'],
                    $row['attempts'],
                    $row['takers'],
                    $row['passed'],
                    $row['pass_rate'],
                    $row['avg_score'],
                ];
            }
        } else {
            $type = 'courses';
            $header = [
                'Course', 'Enrolled learners', 'Completed', 'Completion rate (%)',
                'Avg progress (%)', 'Time spent (hours)', 'Avg time per learner (minutes)', 'Last activity',
            ];

            foreach ($data['courses'] as $row) {
                $body[] = [
                    $row['title'],
                    $row['learners'],
                    $row['completed'],
                    $row['completion_rate'],
                    $row['avg_progress'],
                    round($row['seconds'] / 3600, 2),
                    (int) round($row['avg_seconds_per_learner'] / 60),
                    $row['last_seen'],
                ];
            }
        }

        $filename = 'lms-report-' . $type . '-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($header, $body) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens the file with the right encoding.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $header);

            foreach ($body as $line) {
                fputcsv($handle, $line);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
    /**
     * Normalise the requested window and default it to the last 30 days.
     * Swaps inverted ranges instead of failing, because the date pickers are
     * two independent inputs.
     */
    private function resolveRange(Request $request): array
    {
        $from = $request->date('from') ?: now()->subDays(29);
        $to = $request->date('to') ?: now();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->toDateString(), $to->toDateString()];
    }

    /**
     * Build a gap-free series so the chart does not imply activity on days
     * that simply have no rows.
     */
    private function buildTrend($rawCounts, Carbon $start, Carbon $end, string $granularity): array
    {
        $labels = [];
        $values = [];

        if ($granularity === 'month') {
            $cursor = $start->copy()->startOfMonth();
            $last = $end->copy()->startOfMonth();

            while ($cursor->lte($last)) {
                $key = $cursor->format('Y-m');
                $labels[] = $cursor->format('M Y');
                $values[] = (int) $rawCounts
                    ->filter(fn ($total, $day) => str_starts_with((string) $day, $key))
                    ->sum();
                $cursor->addMonth();
            }

            return ['labels' => $labels, 'values' => $values];
        }

        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        while ($cursor->lte($last)) {
            $key = $cursor->toDateString();
            $labels[] = $cursor->format('d M');
            $values[] = (int) ($rawCounts[$key] ?? 0);
            $cursor->addDay();
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
