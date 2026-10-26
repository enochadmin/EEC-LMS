@php
    $fmtTime = function (int $seconds): string {
        if ($seconds <= 0) {
            return '—';
        }
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        if ($h > 0) {
            return $h . 'h ' . str_pad((string) $m, 2, '0', STR_PAD_LEFT) . 'm';
        }
        return max($m, 1) . 'm';
    };

    $hasEnrollments = $enrollmentsByCourse->isNotEmpty();
    $hasTime = $timeByCourse->isNotEmpty();
    $hasDepartments = $departmentMix->isNotEmpty();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Reports') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $isAdmin ? __('All modules') : __('Your modules') }}
                    · {{ \Carbon\Carbon::parse($from)->format('d M Y') }} – {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
                </p>
            </div>
            <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:underline">{{ __('Back to dashboard') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Filters ------------------------------------------------------ --}}
            <form method="GET" action="{{ route('reports.index') }}"
                class="bg-white shadow-sm sm:rounded-lg p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide">{{ __('From') }}</label>
                        <input type="date" name="from" value="{{ $from }}" max="{{ $to }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide">{{ __('To') }}</label>
                        <input type="date" name="to" value="{{ $to }}" min="{{ $from }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide">{{ __('Course') }}</label>
                        <select name="module_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('All courses') }}</option>
                            @foreach ($modules as $module)
                                <option value="{{ $module->id }}" @selected($selectedModule === $module->id)>{{ $module->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide">{{ __('Department') }}</label>
                        <select name="department_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('All departments') }}</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" @selected($selectedDepartment === $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                            {{ __('Apply') }}
                        </button>
                        <a href="{{ route('reports.index') }}"
                            class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200">
                            {{ __('Reset') }}
                        </a>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    @foreach ([['7', 'Last 7 days'], ['29', 'Last 30 days'], ['89', 'Last 90 days'], ['365', 'Last 12 months']] as [$days, $label])
                        <a href="{{ route('reports.index', array_merge(request()->except(['from', 'to']), ['from' => now()->subDays((int) $days)->toDateString(), 'to' => now()->toDateString()])) }}"
                            class="px-3 py-1 rounded-full border border-gray-200 text-gray-600 hover:bg-gray-50">{{ __($label) }}</a>
                    @endforeach
                </div>
            </form>

            {{-- KPI cards ---------------------------------------------------- --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('Enrolled learners') }}</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($kpis['enrolled_learners']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ number_format($kpis['new_enrollments']) }} {{ __('new enrolment(s) in range') }}
                    </p>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('Active learners') }}</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($kpis['active_learners']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ __('Learners who opened course material') }}</p>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('Time on courses') }}</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $fmtTime($kpis['total_seconds']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ $kpis['active_learners'] > 0 ? $fmtTime((int) round($kpis['total_seconds'] / $kpis['active_learners'])) : '—' }}
                        {{ __('avg per active learner') }}
                    </p>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('Completion') }}</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $kpis['completion_rate'] }}%</p>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ number_format($kpis['completed_enrollments']) }} {{ __('of') }} {{ number_format($kpis['total_enrollments']) }} {{ __('enrolments completed') }}
                    </p>
                </div>
            </div>

            @if (! $timeTrackingEnabled)
                <div class="bg-blue-50 border border-blue-200 text-blue-800 text-sm rounded-lg p-4">
                    {{ __('Time-on-course data has no entries yet. It is recorded automatically while learners read lessons — from now on, not retrospectively.') }}
                </div>
            @endif

            {{-- Trending ------------------------------------------------------ --}}
            @if ($trendingModule)
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('Trending course') }}</p>
                            <h3 class="mt-1 text-2xl font-bold text-gray-900">{{ $trendingModule['title'] }}</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ $trendingModule['current'] }} {{ __('enrolments this period vs') }}
                                {{ $trendingModule['previous'] }} {{ __('previous period') }}
                                @if ($trendingModule['is_new'])
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">{{ __('New') }}</span>
                                @else
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $trendingModule['growth'] >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                        {{ $trendingModule['growth'] > 0 ? '+' : '' }}{{ $trendingModule['growth'] }}%
                                    </span>
                                @endif
                            </p>
                        </div>

                        <div class="hidden sm:flex gap-6">
                            @foreach ($trending->skip(1) as $runner)
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">{{ $runner['title'] }}</p>
                                    <p class="text-lg font-semibold text-gray-900">{{ $runner['current'] }}</p>
                                    <p class="text-xs {{ $runner['growth'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ $runner['growth'] > 0 ? '+' : '' }}{{ $runner['growth'] }}%
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Trend + completion mix --------------------------------------- --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-5 lg:col-span-2">
                    <h3 class="text-sm font-semibold text-gray-700">{{ __('Enrolments over time') }}</h3>
                    <p class="text-xs text-gray-500 mt-1">{{ ucfirst($trendGranularity) }} buckets</p>
                    <div class="mt-4 h-64">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <h3 class="text-sm font-semibold text-gray-700">{{ __('Progress mix') }}</h3>
                    <p class="text-xs text-gray-500 mt-1">{{ __('All enrolments in scope') }}</p>
                    <div class="mt-4 h-64">
                        <canvas id="completionChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- Enrolments by course + time by course ------------------------- --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <h3 class="text-sm font-semibold text-gray-700">{{ __('Enrolments by course') }}</h3>
                    <p class="text-xs text-gray-500 mt-1">{{ __('New enrolments in the selected range') }}</p>
                    <div class="mt-4 h-72">
                        @if ($hasEnrollments)
                            <canvas id="enrolmentChart"></canvas>
                        @else
                            <div class="h-full flex items-center justify-center text-sm text-gray-400">
                                {{ __('No enrolments in this range') }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <h3 class="text-sm font-semibold text-gray-700">{{ __('Time spent by course') }}</h3>
                    <p class="text-xs text-gray-500 mt-1">{{ __('Total learning time in the selected range') }}</p>
                    <div class="mt-4 h-72">
                        @if ($hasTime)
                            <canvas id="timeChart"></canvas>
                        @else
                            <div class="h-full flex items-center justify-center text-sm text-gray-400">
                                {{ __('No time recorded yet') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Department mix + quiz pass rate ------------------------------- --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <h3 class="text-sm font-semibold text-gray-700">{{ __('Learners by department') }}</h3>
                    <div class="mt-4 h-64">
                        @if ($hasDepartments)
                            <canvas id="departmentChart"></canvas>
                        @else
                            <div class="h-full flex items-center justify-center text-sm text-gray-400">
                                {{ __('No department data') }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700">{{ __('Quiz pass rate') }}</h3>
                            <p class="text-xs text-gray-500 mt-1">{{ __('Attempts in the selected range') }}</p>
                        </div>
                        <a href="{{ route('reports.export', array_merge(request()->query(), ['type' => 'quizzes'])) }}"
                           class="text-xs font-medium text-indigo-600 hover:text-indigo-800 whitespace-nowrap">{{ __('Export CSV') }}</a>
                    </div>
                    <div class="mt-4 h-64">
                        @if ($quizPerformance->isNotEmpty())
                            <canvas id="quizChart"></canvas>
                        @else
                            <div class="h-full flex items-center justify-center text-sm text-gray-400">
                                {{ __('No quiz attempts in this range') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Course performance table -------------------------------------- --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700">{{ __('Course performance') }}</h3>
                        <p class="text-xs text-gray-500 mt-1">{{ __('Enrolment, completion and time per course') }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-gray-400">{{ $courses->count() }} {{ __('course(s)') }}</span>
                        <a href="{{ route('reports.export', array_merge(request()->query(), ['type' => 'courses'])) }}"
                           class="text-xs font-medium text-indigo-600 hover:text-indigo-800">{{ __('Export CSV') }}</a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold">{{ __('Course') }}</th>
                                <th class="px-5 py-3 text-right font-semibold">{{ __('Enrolled') }}</th>
                                <th class="px-5 py-3 text-right font-semibold">{{ __('Completed') }}</th>
                                <th class="px-5 py-3 text-right font-semibold">{{ __('Rate') }}</th>
                                <th class="px-5 py-3 text-right font-semibold">{{ __('Avg progress') }}</th>
                                <th class="px-5 py-3 text-right font-semibold">{{ __('Time') }}</th>
                                <th class="px-5 py-3 text-right font-semibold">{{ __('Avg / learner') }}</th>
                                <th class="px-5 py-3 text-right font-semibold">{{ __('Last activity') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($courses as $course)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('modules.show', $course['module_id']) }}" class="text-indigo-600 hover:underline font-medium">
                                            {{ $course['title'] }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3 text-right text-gray-900">{{ number_format($course['learners']) }}</td>
                                    <td class="px-5 py-3 text-right text-gray-900">{{ number_format($course['completed']) }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                                            {{ $course['completion_rate'] >= 70 ? 'bg-emerald-100 text-emerald-800' : ($course['completion_rate'] >= 30 ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700') }}">
                                            {{ $course['completion_rate'] }}%
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-right text-gray-700">{{ $course['avg_progress'] }}%</td>
                                    <td class="px-5 py-3 text-right text-gray-700">{{ $fmtTime($course['seconds']) }}</td>
                                    <td class="px-5 py-3 text-right text-gray-700">{{ $fmtTime($course['avg_seconds_per_learner']) }}</td>
                                    <td class="px-5 py-3 text-right text-gray-500">
                                        {{ $course['last_seen'] ? \Carbon\Carbon::parse($course['last_seen'])->diffForHumans() : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-8 text-center text-gray-400">{{ __('No courses in scope') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Learner time table -------------------------------------------- --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700">{{ __('Who spends time where') }}</h3>
                        <p class="text-xs text-gray-500 mt-1">{{ __('Top 25 learner + course pairs by time in the selected range') }}</p>
                    </div>
                    <a href="{{ route('reports.export', array_merge(request()->query(), ['type' => 'learners'])) }}"
                       class="text-xs font-medium text-indigo-600 hover:text-indigo-800 whitespace-nowrap">{{ __('Export CSV') }}</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold">{{ __('Learner') }}</th>
                                <th class="px-5 py-3 text-left font-semibold">{{ __('Department') }}</th>
                                <th class="px-5 py-3 text-left font-semibold">{{ __('Course') }}</th>
                                <th class="px-5 py-3 text-right font-semibold">{{ __('Time spent') }}</th>
                                <th class="px-5 py-3 text-right font-semibold">{{ __('Lessons done') }}</th>
                                <th class="px-5 py-3 text-right font-semibold">{{ __('Last seen') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($learnerTimeRows as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-3 text-gray-900 font-medium">{{ $row['user'] }}</td>
                                    <td class="px-5 py-3 text-gray-600">{{ $row['department'] }}</td>
                                    <td class="px-5 py-3 text-gray-600">{{ $row['module'] }}</td>
                                    <td class="px-5 py-3 text-right text-gray-900">{{ $fmtTime($row['seconds']) }}</td>
                                    <td class="px-5 py-3 text-right text-gray-700">{{ $row['lessons_completed'] }}</td>
                                    <td class="px-5 py-3 text-right text-gray-500">
                                        {{ $row['last_seen'] ? \Carbon\Carbon::parse($row['last_seen'])->diffForHumans() : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-gray-400">
                                        {{ __('No learning time recorded in this range yet') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Quiz table ----------------------------------------------------- --}}
            @if ($quizPerformance->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-700">{{ __('Quiz performance') }}</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                                <tr>
                                    <th class="px-5 py-3 text-left font-semibold">{{ __('Quiz') }}</th>
                                    <th class="px-5 py-3 text-right font-semibold">{{ __('Attempts') }}</th>
                                    <th class="px-5 py-3 text-right font-semibold">{{ __('Takers') }}</th>
                                    <th class="px-5 py-3 text-right font-semibold">{{ __('Passed') }}</th>
                                    <th class="px-5 py-3 text-right font-semibold">{{ __('Pass rate') }}</th>
                                    <th class="px-5 py-3 text-right font-semibold">{{ __('Avg score') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($quizPerformance as $quiz)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-5 py-3 text-gray-900 font-medium">{{ $quiz['title'] }}</td>
                                        <td class="px-5 py-3 text-right text-gray-700">{{ $quiz['attempts'] }}</td>
                                        <td class="px-5 py-3 text-right text-gray-700">{{ $quiz['takers'] }}</td>
                                        <td class="px-5 py-3 text-right text-gray-700">{{ $quiz['passed'] }}</td>
                                        <td class="px-5 py-3 text-right">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                                                {{ $quiz['pass_rate'] >= 70 ? 'bg-emerald-100 text-emerald-800' : ($quiz['pass_rate'] >= 40 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                                {{ $quiz['pass_rate'] }}%
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-right text-gray-700">{{ $quiz['avg_score'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function () {
            if (typeof Chart === 'undefined') return;

            const indigo = '#6366f1';
            const emerald = '#10b981';
            const amber = '#f59e0b';
            const rose = '#f43f5e';
            const sky = '#0ea5e9';
            const palette = [indigo, emerald, amber, rose, sky, '#8b5cf6', '#14b8a6', '#f97316', '#64748b', '#e11d48'];

            Chart.defaults.font.family = "'Figtree', ui-sans-serif, system-ui, sans-serif";
            Chart.defaults.font.size = 12;
            Chart.defaults.color = '#6b7280';

            const trend = @json($trendSeries);
            const trendEl = document.getElementById('trendChart');
            if (trendEl) {
                new Chart(trendEl.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: trend.labels,
                        datasets: [{
                            label: @json(__('Enrolments')),
                            data: trend.values,
                            borderColor: indigo,
                            backgroundColor: 'rgba(99, 102, 241, 0.12)',
                            fill: true,
                            tension: 0.35,
                            pointRadius: 2,
                            pointHoverRadius: 4,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f3f4f6' } },
                            x: { grid: { display: false } },
                        },
                    },
                });
            }

            const mix = @json($completionMix);
            const mixEl = document.getElementById('completionChart');
            if (mixEl) {
                new Chart(mixEl.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: [@json(__('Completed')), @json(__('In progress')), @json(__('Not started'))],
                        datasets: [{
                            data: [mix.completed, mix.in_progress, mix.not_started],
                            backgroundColor: [emerald, amber, '#e5e7eb'],
                            borderWidth: 0,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '62%',
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 14 } } },
                    },
                });
            }

            const byCourse = @json($enrollmentsByCourse);
            const enrolmentEl = document.getElementById('enrolmentChart');
            if (enrolmentEl && byCourse.length) {
                new Chart(enrolmentEl.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: byCourse.map(r => r.title),
                        datasets: [{
                            label: @json(__('Enrolments')),
                            data: byCourse.map(r => r.enrollments),
                            backgroundColor: indigo,
                            borderRadius: 4,
                            maxBarThickness: 26,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f3f4f6' } },
                            x: { grid: { display: false }, ticks: { autoSkip: false, maxRotation: 40, minRotation: 0 } },
                        },
                    },
                });
            }

            const timeRows = @json($timeByCourse);
            const timeEl = document.getElementById('timeChart');
            if (timeEl && timeRows.length) {
                new Chart(timeEl.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: timeRows.map(r => r.title),
                        datasets: [{
                            label: @json(__('Hours')),
                            data: timeRows.map(r => Math.round((r.seconds / 3600) * 10) / 10),
                            backgroundColor: sky,
                            borderRadius: 4,
                            maxBarThickness: 26,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => `${ctx.parsed.x} h`,
                                },
                            },
                        },
                        scales: {
                            x: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                            y: { grid: { display: false } },
                        },
                    },
                });
            }

            const departments = @json($departmentMix);
            const deptEl = document.getElementById('departmentChart');
            if (deptEl && departments.length) {
                new Chart(deptEl.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: departments.map(d => d.department),
                        datasets: [{
                            data: departments.map(d => d.learners),
                            backgroundColor: palette,
                            borderWidth: 0,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '58%',
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 12 } } },
                    },
                });
            }

            const quizzes = @json($quizPerformance);
            const quizEl = document.getElementById('quizChart');
            if (quizEl && quizzes.length) {
                new Chart(quizEl.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: quizzes.map(q => q.title),
                        datasets: [{
                            label: @json(__('Pass rate %')),
                            data: quizzes.map(q => q.pass_rate),
                            backgroundColor: quizzes.map(q => q.pass_rate >= 70 ? emerald : (q.pass_rate >= 40 ? amber : rose)),
                            borderRadius: 4,
                            maxBarThickness: 26,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, max: 100, grid: { color: '#f3f4f6' } },
                            x: { grid: { display: false }, ticks: { autoSkip: false, maxRotation: 40 } },
                        },
                    },
                });
            }
        })();
    </script>
</x-app-layout>
