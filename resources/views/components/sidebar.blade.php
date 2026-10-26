@props(['user'])
@php
    $pendingApprovals = 0;
    if ($user && ($user->hasRole('admin') || $user->can('assign modules') || $user->can('issue certificates'))) {
        $pendingApprovals = \App\Models\ModuleCompletionApproval::where('status', 'pending')->count();
    }
@endphp

<aside x-data="{ 
        open: true,
        init() {
          // Sync with global sidebar store
          this.open = this.$store.sidebar.open;
          this.$watch('$store.sidebar.open', value => {
            this.open = value;
          });
        }
      }" 
      class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 text-white transform transition-transform duration-300 ease-in-out lg:z-auto"
      :class="{ '-translate-x-full': !open, 'translate-x-0': open }">
    <div class="flex flex-col h-full">
        <!-- Logo -->
        <div class="flex items-center justify-between h-16 px-6 border-b border-gray-800">
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-2">
                <x-application-logo class="block h-8 w-auto fill-current text-white" />
                <span class="text-xl font-bold">Training MS</span>
            </a>
            <button @click="$store.sidebar.open = false" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-gray-300 rounded-lg hover:bg-gray-800 transition-colors {{ request()->routeIs('dashboard') ? 'bg-gray-800 text-white' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Dashboard
            </a>

            @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('instructor'))
            <!-- Reports -->
            <a href="{{ route('reports.index') }}" class="flex items-center px-4 py-3 text-gray-300 rounded-lg hover:bg-gray-800 transition-colors {{ request()->routeIs('reports.*') ? 'bg-gray-800 text-white' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Reports
            </a>
            @endif
            <!-- Training Modules -->
            <a href="{{ route('modules.index') }}" class="flex items-center px-4 py-3 text-gray-300 rounded-lg hover:bg-gray-800 transition-colors {{ request()->routeIs('modules.*') ? 'bg-gray-800 text-white' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                Modules
            </a>
            @if(auth()->user()->hasRole('learner'))
            <a href="{{ route('discover.index') }}" class="flex items-center px-4 py-3 text-gray-300 rounded-lg hover:bg-gray-800 transition-colors {{ request()->routeIs('discover.*') ? 'bg-gray-800 text-white' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"></path>
                </svg>
                Discover
            </a>
            @endif

            @can('manage lessons')
            <!-- Lessons -->
            <a href="{{ route('lessons.index') }}" class="flex items-center px-4 py-3 text-gray-300 rounded-lg hover:bg-gray-800 transition-colors {{ request()->routeIs('lessons.*') ? 'bg-gray-800 text-white' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                Lessons
            </a>
            @endcan

            @can('manage quizzes')
            <!-- Quizzes -->
            <a href="{{ route('quizzes.index') }}" class="flex items-center px-4 py-3 text-gray-300 rounded-lg hover:bg-gray-800 transition-colors {{ request()->routeIs('quizzes.*') ? 'bg-gray-800 text-white' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                </svg>
                Quizzes
            </a>
            @endcan

            <!-- Knowledge Files -->
            <a href="{{ route('knowledge.index') }}" class="flex items-center px-4 py-3 text-gray-300 rounded-lg hover:bg-gray-800 transition-colors {{ request()->routeIs('knowledge.*') ? 'bg-gray-800 text-white' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Knowledge Files
            </a>

            @can('view certificates')
            <!-- Certificates -->
            <a href="{{ route('certificates.index') }}" class="flex items-center px-4 py-3 text-gray-300 rounded-lg hover:bg-gray-800 transition-colors {{ request()->routeIs('certificates.*') ? 'bg-gray-800 text-white' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                </svg>
                Certificates
            </a>
            @can('assign modules')
            <a href="{{ route('modules.index') }}?assignments=1" class="flex items-center px-4 py-3 text-gray-300 rounded-lg hover:bg-gray-800 transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                Assign Modules
            </a>
            @endcan
            @endcan

            <!-- Management (collapsible): Users / Modules / Categories -->
            @unlessrole('learner')
            <div x-data="{ openMgmt: false }" class="pt-4">
                <p class="px-4 text-xs font-semibold text-gray-400 uppercase">Management</p>
                <button @click="openMgmt = !openMgmt" class="w-full flex items-center justify-between px-4 py-3 text-gray-300 rounded-lg hover:bg-gray-800 transition-colors">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"></path>
                        </svg>
                        Management
                    </span>
                    <svg x-show="!openMgmt" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    <svg x-show="openMgmt" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                </button>

                <div x-show="openMgmt" x-collapse class="mt-2 space-y-1 px-2">
                    @if(auth()->user()->hasRole('admin') || auth()->user()->can('manage users'))
                        <a href="{{ route('users.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 rounded-md hover:bg-gray-800 {{ request()->routeIs('users.*') ? 'bg-gray-800 text-white' : '' }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1z"></path></svg>
                            Manage Users
                        </a>
                        <a href="{{ route('departments.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 rounded-md hover:bg-gray-800 {{ request()->routeIs('departments.*') ? 'bg-gray-800 text-white' : '' }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"></path>
                            </svg>
                            Manage Departments
                        </a>
                    @endif

                    @if(auth()->user()->hasRole('admin') || auth()->user()->can('manage modules'))
                        <a href="{{ route('modules.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 rounded-md hover:bg-gray-800 {{ request()->routeIs('modules.*') ? 'bg-gray-800 text-white' : '' }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13"></path></svg>
                            Manage Modules
                        </a>

                        <a href="{{ route('categories.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 rounded-md hover:bg-gray-800 {{ request()->routeIs('categories.*') ? 'bg-gray-800 text-white' : '' }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                            Manage Categories
                        </a>
                    @endif

                    @if(auth()->user()->hasRole('admin'))
                        <a href="{{ route('admin.branding.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 rounded-md hover:bg-gray-800 {{ request()->routeIs('admin.branding.*') ? 'bg-gray-800 text-white' : '' }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 012.828 2.828L11.828 15.83a2 2 0 01-1.414.586H8v-2.414A2 2 0 018.586 12.6z"></path></svg>
                            Certificate Branding
                        </a>
                        <a href="{{ route('admin.activity.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 rounded-md hover:bg-gray-800 {{ request()->routeIs('admin.activity.*') ? 'bg-gray-800 text-white' : '' }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            Activity Log
                        </a>
                        <a href="{{ route('roles.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 rounded-md hover:bg-gray-800 {{ request()->routeIs('roles.*') ? 'bg-gray-800 text-white' : '' }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            Roles &amp; Permissions
                        </a>
                    @endif
                </div>
            </div>
            @endunlessrole

            @if(auth()->user()->assignedModules()->exists() || \App\Models\EnrollmentRequest::where('user_id', auth()->id())->where('status', 'pending')->exists())

            <!-- Learner Section -->
            <div class="pt-4">
                <p class="px-4 text-xs font-semibold text-gray-400 uppercase">My Learning</p>
            </div>
            <a href="{{ route('my.enrollments') }}" class="flex items-center px-4 py-3 text-gray-300 rounded-lg hover:bg-gray-800 transition-colors {{ request()->routeIs('my.*') ? 'bg-gray-800 text-white' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                My Enrollments
            </a>
            <a href="{{ route('my.progress') }}" class="flex items-center px-4 py-3 text-gray-300 rounded-lg hover:bg-gray-800 transition-colors {{ request()->routeIs('my.progress') ? 'bg-gray-800 text-white' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                My Progress
            </a>
            @endif

            @canany(['assign modules','issue certificates','manage users'])
            <!-- Approvals for instructors/managers -->
            <div class="pt-4">
                <p class="px-4 text-xs font-semibold text-gray-400 uppercase">Approvers</p>
            </div>
            <div x-data="{ openApprovals: {{ $pendingApprovals > 0 ? 'true' : 'false' }} }">
                <button @click="openApprovals = !openApprovals" class="w-full flex items-center justify-between px-4 py-3 text-gray-300 rounded-lg hover:bg-gray-800 transition-colors">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Approvers
                        @if($pendingApprovals > 0)
                            <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold rounded-full bg-red-500 text-white">{{ $pendingApprovals }}</span>
                        @endif
                    </span>
                    <svg x-show="!openApprovals" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                    <svg x-show="openApprovals" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                    </svg>
                </button>
                <div x-show="openApprovals" x-collapse class="mt-2 space-y-1 px-2">
                    <a href="{{ route('enrollment-approvals.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 rounded-md hover:bg-gray-800 {{ request()->routeIs('enrollment-approvals.*') ? 'bg-gray-800 text-white' : '' }}">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h18M8 7h8m-9 4h10m-11 4h12"></path>
                        </svg>
                        Enrollment Approvals
                    </a>
                    <a href="{{ route('module-approvals.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 rounded-md hover:bg-gray-800 {{ request()->routeIs('module-approvals.*') ? 'bg-gray-800 text-white' : '' }}">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Module Approvals
                        @if($pendingApprovals > 0)
                            <span class="ml-auto inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold rounded-full bg-red-500 text-white">{{ $pendingApprovals }}</span>
                        @endif                    </a>
                    <a href="{{ route('lesson-approvals.index') }}" class="flex items-center justify-between px-4 py-2 text-sm text-gray-300 rounded-md hover:bg-gray-800 {{ request()->routeIs('lesson-approvals.*') ? 'bg-gray-800 text-white' : '' }}">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Lesson Approvals
                        </span>
                        @php $pendingLessonApprovals = \App\Models\LessonCompletion::where('status', 'pending')->count(); @endphp
                        @if($pendingLessonApprovals > 0)
                            <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-500 text-gray-900">
                                {{ $pendingLessonApprovals }}
                            </span>
                        @endif
                    </a>
                    <a href="{{ route('retake-requests.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 rounded-md hover:bg-gray-800 {{ request()->routeIs('retake-requests.*') ? 'bg-gray-800 text-white' : '' }}">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9M5 19v-5h-.582m0 0A8.003 8.003 0 0119.418 15"></path>
                        </svg>
                        Retake Requests
                    </a>
                </div>
            </div>
            @endcanany
        </nav>

        <!-- User Info -->
        <div class="p-4 border-t border-gray-800">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center">
                        <span class="text-sm font-medium text-white">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ $user->name }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ $user->email }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        @foreach($user->roles as $role)
                            <span class="inline-block px-2 py-0.5 bg-gray-700 rounded text-xs mr-1">{{ ucfirst($role->name) }}</span>
                        @endforeach
                    </p>
                </div>
            </div>
        </div>
    </div>
</aside>
