<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Modules -->
                <a href="{{ route('modules.index') }}" class="block transform transition hover:scale-105">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Modules</dt>
                                        <dd class="text-lg font-semibold text-gray-900">{{ $stats['total_modules'] }}</dd>
                                        <dd class="text-xs text-gray-500">{{ $stats['published_modules'] }} published</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Total Users -->
                @if($showReports)
                <a href="{{ route('users.index') }}" class="block transform transition hover:scale-105">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                        <dd class="text-lg font-semibold text-gray-900">{{ $stats['total_users'] }}</dd>
                                        <dd class="text-xs text-gray-500">{{ $adminStats['users_assigned_to_modules'] ?? 0 }} assigned</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
                @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">My Assignments</dt>
                                    <dd class="text-lg font-semibold text-gray-900">{{ $userStats['my_assignments'] ?? 0 }}</dd>
                                    <dd class="text-xs text-gray-500">{{ $userStats['completed_modules'] ?? 0 }} completed</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Certificates -->
                <a href="{{ route('certificates.index') }}" class="block transform transition hover:scale-105">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Certificates</dt>
                                        @if($showReports)
                                            <dd class="text-lg font-semibold text-gray-900">{{ $stats['total_certificates'] }}</dd>
                                            <dd class="text-xs text-gray-500">{{ $adminStats['certified_users'] ?? 0 }} users certified</dd>
                                        @else
                                            <dd class="text-lg font-semibold text-gray-900">{{ $userStats['my_certificates'] ?? 0 }}</dd>
                                            <dd class="text-xs text-gray-500">My certificates</dd>
                                        @endif
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Quizzes/Questions -->
                <a href="{{ route('quizzes.index') }}" class="block transform transition hover:scale-105">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Quizzes</dt>
                                        @if($showReports)
                                            <dd class="text-lg font-semibold text-gray-900">{{ $stats['total_quizzes'] }}</dd>
                                            <dd class="text-xs text-gray-500">{{ $stats['total_questions'] }} questions</dd>
                                        @else
                                            <dd class="text-lg font-semibold text-gray-900">{{ $userStats['quizzes_taken'] ?? 0 }}</dd>
                                            <dd class="text-xs text-gray-500">Quizzes taken</dd>
                                        @endif
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            @if($showReports)
            <!-- Admin Reporting Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Users Per Module -->
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Users Per Module</h3>
                        <div class="space-y-3">
                            @forelse($adminStats['users_per_module'] ?? [] as $moduleData)
                                <div class="flex items-center justify-between">
                                    @if(!empty($moduleData['module_id']))
                                        <a href="{{ route('modules.show', $moduleData['module_id']) }}" class="text-sm text-indigo-600 hover:underline truncate">
                                            {{ $moduleData['module'] }}
                                        </a>
                                    @else
                                        <span class="text-sm text-gray-700 truncate">{{ $moduleData['module'] }}</span>
                                    @endif
                                    <span class="text-sm font-semibold text-gray-900">{{ $moduleData['users_count'] }} users</span>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">No module assignments yet</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Module Completions -->
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Module Completions</h3>
                        <div class="space-y-3">
                            @forelse($adminStats['module_completions'] ?? [] as $completion)
                                <div class="flex items-center justify-between">
                                    @if(!empty($completion['module_id']))
                                        <a href="{{ route('modules.show', $completion['module_id']) }}" class="text-sm text-indigo-600 hover:underline truncate">
                                            {{ $completion['module'] }}
                                        </a>
                                    @else
                                        <span class="text-sm text-gray-700 truncate">{{ $completion['module'] }}</span>
                                    @endif
                                    <span class="text-sm font-semibold text-green-600">{{ $completion['completions'] }} completed</span>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">No completions yet</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Performing Modules -->
            <div class="bg-white shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Performing Modules</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Module</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assignments</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completions</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completion Rate</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($adminStats['top_modules'] ?? [] as $module)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            @if(!empty($module['module_id']))
                                                <a href="{{ route('modules.show', $module['module_id']) }}" class="text-indigo-600 hover:underline">
                                                    {{ $module['title'] }}
                                                </a>
                                            @else
                                                {{ $module['title'] }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $module['category'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $module['assignments'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $module['completions'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                    <div class="bg-green-600 h-2 rounded-full" style="width: {{ min($module['completion_rate'], 100) }}%"></div>
                                                </div>
                                                <span class="text-sm text-gray-700">{{ $module['completion_rate'] }}%</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ $module['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                {{ ucfirst($module['status']) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No modules found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Module Progress Overview -->
            @if(isset($adminStats['module_progress_overview']))
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h4 class="text-sm font-medium text-gray-500 mb-2">Average Progress</h4>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($adminStats['module_progress_overview']->avg_progress ?? 0, 1) }}%</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h4 class="text-sm font-medium text-gray-500 mb-2">Completed</h4>
                    <p class="text-2xl font-semibold text-green-600">{{ $adminStats['module_progress_overview']->completed ?? 0 }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h4 class="text-sm font-medium text-gray-500 mb-2">In Progress</h4>
                    <p class="text-2xl font-semibold text-yellow-600">{{ $adminStats['module_progress_overview']->in_progress ?? 0 }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h4 class="text-sm font-medium text-gray-500 mb-2">Not Started</h4>
                    <p class="text-2xl font-semibold text-gray-600">{{ $adminStats['module_progress_overview']->not_started ?? 0 }}</p>
                </div>
            </div>
            @endif

            <!-- Users by Role -->
            @if(isset($adminStats['users_by_role']))
            <div class="bg-white shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Users by Role</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach($adminStats['users_by_role'] as $roleData)
                            @php
                                $canManageUsers = auth()->user()->can('manage users');
                                $roleLink = $canManageUsers ? route('users.index', ['role' => $roleData['role']]) : null;
                            @endphp
                            @if($roleLink)
                                <a href="{{ $roleLink }}" class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition">
                                    <p class="text-sm font-medium text-gray-500">{{ ucfirst($roleData['role']) }}</p>
                                    <p class="text-2xl font-semibold text-gray-900 mt-2">{{ $roleData['count'] }}</p>
                                </a>
                            @else
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <p class="text-sm font-medium text-gray-500">{{ ucfirst($roleData['role']) }}</p>
                                    <p class="text-2xl font-semibold text-gray-900 mt-2">{{ $roleData['count'] }}</p>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            @if(($topLearnersMonth ?? collect())->isNotEmpty() || ($topLearnersQuarter ?? collect())->isNotEmpty())
            <!-- Top Learners Report Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                @if(($topLearnersMonth ?? collect())->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Learners of the Month</h3>
                        <div class="space-y-3">
                            @foreach($topLearnersMonth as $index => $entry)
                                <div class="flex items-center justify-between text-sm border-b border-gray-100 pb-2 last:border-0 last:pb-0">
                                    <div>
                                        <p class="font-medium text-gray-900">
                                            #{{ $index + 1 }} {{ $entry['user']->name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ $entry['module_title'] ?? $entry['quiz_title'] }} — {{ $entry['quiz_title'] }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-semibold text-indigo-600">{{ $entry['best_score'] }}%</p>
                                        <p class="text-xs text-gray-400">{{ $entry['attempted_at']->diffForHumans() }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                @if(($topLearnersQuarter ?? collect())->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Learners of the Quarter</h3>
                        <div class="space-y-3">
                            @foreach($topLearnersQuarter as $index => $entry)
                                <div class="flex items-center justify-between text-sm border-b border-gray-100 pb-2 last:border-0 last:pb-0">
                                    <div>
                                        <p class="font-medium text-gray-900">
                                            #{{ $index + 1 }} {{ $entry['user']->name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ $entry['module_title'] ?? $entry['quiz_title'] }} — {{ $entry['quiz_title'] }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-semibold text-indigo-600">{{ $entry['best_score'] }}%</p>
                                        <p class="text-xs text-gray-400">{{ $entry['attempted_at']->diffForHumans() }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>
            @endif

            <!-- Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Recent Assignments -->
                <div x-data="{ openAssignments: true }" class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-semibold text-gray-900">Recent Assignments</h3>
                            <button @click="openAssignments = !openAssignments"
                                    class="text-xs text-gray-500 hover:text-gray-700 focus:outline-none">
                                <span x-show="openAssignments">Hide</span>
                                <span x-show="!openAssignments">Show</span>
                            </button>
                        </div>
                        <div x-show="openAssignments" x-collapse class="space-y-3">
                            @forelse($adminStats['recent_assignments'] ?? [] as $assignment)
                                <div class="flex items-center justify-between text-sm">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $assignment->user->name }}</p>
                                        <p class="text-gray-500">{{ $assignment->module->title }}</p>
                                    </div>
                                    <span class="text-gray-400">{{ $assignment->created_at->diffForHumans() }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">No recent assignments</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Recent Certificates -->
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Certificates</h3>
                        <div class="space-y-3">
                            @forelse($adminStats['recent_certificates'] ?? [] as $certificate)
                                <div class="flex items-center justify-between text-sm">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $certificate->user->name }}</p>
                                        <p class="text-gray-500">{{ $certificate->module->title }}</p>
                                    </div>
                                    <span class="text-gray-400">{{ $certificate->created_at->diffForHumans() }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">No recent certificates</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- User Progress (for learners) -->
            @if(!$showReports && isset($userProgress) && $userProgress->count() > 0)
            <div class="bg-white shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">My Progress</h3>
                    <div class="space-y-4">
                        @foreach($userProgress as $progress)
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-700">{{ $progress->module->title }}</span>
                                    <span class="text-sm text-gray-500">{{ number_format($progress->percentage, 1) }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $progress->percentage }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Recent Modules -->
            <div class="bg-white shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Modules</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @forelse($recentModules as $module)
                            <a href="{{ route('modules.show', $module->id) }}" class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                <h4 class="font-semibold text-gray-900 mb-2">{{ $module->title }}</h4>
                                <p class="text-sm text-gray-500 mb-3">{{ $module->category->name ?? 'Uncategorized' }}</p>
                                <div class="flex items-center space-x-4 text-xs text-gray-500">
                                    <span>{{ $module->lessons_count }} lessons</span>
                                    <span>{{ $module->quizzes_count }} quizzes</span>
                                    @if($showReports)
                                        <span>{{ $module->assignments_count }} assignments</span>
                                    @endif
                                </div>
                            </a>
                        @empty
                            <p class="text-sm text-gray-500 col-span-3">No modules available</p>
                        @endforelse
                    </div>
                </div>
            </div>
            
            <!-- Charts: Quiz Performance & Enrollments & Departments -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quiz Performance</h3>
                    <div class="w-full h-64">
                        <canvas id="coursePieChart" aria-label="Quiz status pie chart" class="w-full h-full"></canvas>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Enrollments</h3>
                    <div class="w-full h-64">
                        <canvas id="enrollmentBarChart" aria-label="Enrollment statistics" class="w-full h-full"></canvas>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Users by Department</h3>
                    <div class="w-full h-64">
                        <canvas id="departmentChart" aria-label="Users per department" class="w-full h-full"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function(){
            const data = {!! json_encode($chartData ?? ['taken' => 0, 'passed' => 0, 'failed' => 0, 'not_started' => 0]) !!};
            const enrollmentData = {!! json_encode($enrollmentData ?? ['modules' => 0, 'categories' => 0, 'lessons' => 0]) !!};
            const departmentData = {!! json_encode($departmentData ?? []) !!};
            
            const labels = ['Total Attempts', 'Passed Attempts', 'Failed Attempts', 'Not Started Quizzes'];
            const values = [data.taken, data.passed, data.failed, data.not_started];

            // Pie Chart - Quiz Performance
            const pieCanvas = document.getElementById('coursePieChart');
            if (pieCanvas) {
                new Chart(pieCanvas.getContext('2d'), {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: values,
                            backgroundColor: ['#6366F1','#10B981','#EF4444','#9CA3AF'],
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                    }
                });
            }

            // Bar Chart - Enrollments
            const enrollmentCanvas = document.getElementById('enrollmentBarChart');
            if (enrollmentCanvas) {
                new Chart(enrollmentCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: ['Modules', 'Categories', 'Lessons'],
                        datasets: [{
                            label: 'Enrolled',
                            data: [enrollmentData.modules, enrollmentData.categories, enrollmentData.lessons],
                            backgroundColor: ['#6366F1','#F59E0B','#10B981'],
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }

            // Bar Chart - Departments
            const departmentCanvas = document.getElementById('departmentChart');
            if (departmentCanvas && departmentData.length > 0) {
                const deptLabels = departmentData.map(d => d.name);
                const deptValues = departmentData.map(d => d.count);
                
                new Chart(departmentCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: deptLabels,
                        datasets: [{
                            label: 'Users',
                            data: deptValues,
                            backgroundColor: '#8B5CF6',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }
        })();
    </script>
