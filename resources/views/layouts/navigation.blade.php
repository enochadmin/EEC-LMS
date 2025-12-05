<nav class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-40">
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Sidebar toggle button (mobile & desktop) -->
                <button @click="$store.sidebar.open = !$store.sidebar.open" class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
                    <svg x-show="$store.sidebar.open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <!-- Icon: three lines (expanded) -->
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h8" />
                    </svg>
                    <svg x-show="!$store.sidebar.open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <!-- Icon: hamburger (collapsed) -->
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Page Title -->
                <div class="ml-4 lg:ml-0">
                    <h1 class="text-xl font-semibold text-gray-900">
                        @if(isset($header))
                            {{ $header }}
                        @else
                            {{ ucfirst(str_replace('.', ' ', request()->route()->getName())) }}
                        @endif
                    </h1>
                </div>

                <!-- Mobile quick links (Admin / managers only, never learners) -->
                @unlessrole('learner')
                    @can('manage users')
                        <a href="{{ route('users.index') }}" class="md:hidden ml-4 px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-md transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </a>
                        <a href="{{ route('departments.index') }}" class="md:hidden ml-2 px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-md transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"></path>
                            </svg>
                        </a>
                    @endcan
                @endunlessrole
            </div>

            <!-- Right side -->
            <div class="flex items-center space-x-4">
                <!-- management links moved to sidebar -->

                <!-- Dark mode toggle -->
                <button 
                    x-data 
                    @click="$store.theme.toggle()" 
                    class="p-2 rounded-full border border-gray-200 text-gray-500 hover:text-gray-800 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    :class="{ 'bg-gray-900 text-yellow-300 border-gray-700': $store.theme.dark }"
                    aria-label="Toggle dark mode">
                    <!-- Sun (light mode) -->
                    <svg x-show="!$store.theme.dark" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.364-6.364l-1.414 1.414M8.05 17.95l-1.414 1.414M18.364 18.364l-1.414-1.414M8.05 6.05L6.636 4.636M12 8a4 4 0 100 8 4 4 0 000-8z" />
                    </svg>
                    <!-- Moon (dark mode) -->
                    <svg x-show="$store.theme.dark" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
                    </svg>
                </button>

                <!-- Notifications -->
                <div x-data="{ open: false, count: {{ auth()->user()->unreadNotifications()->count() }} }" class="relative">
                    <button @click="open = !open" class="p-2 text-gray-400 hover:text-gray-500 focus:outline-none relative">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span x-show="count > 0" class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full" x-text="count"></span>
                    </button>

                    <!-- Dropdown -->
                    <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg overflow-hidden z-50" style="display: none;">
                        <div class="py-2 max-h-96 overflow-y-auto">
                            <div class="px-4 py-2 border-b flex justify-between items-center">
                                <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
                                <form action="{{ route('notifications.read-all') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800">Mark all read</button>
                                </form>
                            </div>
                            @php
                                $currentUser = auth()->user();
                            @endphp
                            @forelse($currentUser->unreadNotifications()->take(10)->get() as $notification)
                                @php
                                    $data = $notification->data;
                                    $type = $data['type'] ?? null;
                                    $message = $data['message'] ?? 'New notification';
                                    // Personalize some messages for the current user
                                    if ($type === 'enrollment' && isset($data['user_id']) && $data['user_id'] === $currentUser->id) {
                                        $message = "You have been enrolled in {$data['module_title']}";
                                    }
                                    if ($type === 'module_completed' && isset($data['learner_id']) && $data['learner_id'] === $currentUser->id) {
                                        $message = "You completed the module '{$data['module_title']}'";
                                    }
                                    if ($type === 'module_completion_approval_request' && isset($data['learner_id']) && $data['learner_id'] === $currentUser->id) {
                                        // In the unlikely case the learner themselves is notified, keep it user-centric
                                        $message = "Your completion request for '{$data['module_title']}' has been submitted";
                                    }
                                @endphp
                                <div class="px-4 py-3 hover:bg-gray-50 border-b">
                                    <p class="text-sm text-gray-900">{{ $message }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                                    <form action="{{ route('notifications.read', $notification->id) }}" method="POST" class="mt-1">
                                        @csrf
                                        <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800">Mark as read</button>
                                    </form>
                                </div>
                            @empty
                                <div class="px-4 py-3 text-sm text-gray-500 text-center">
                                    No new notifications
                                </div>
                            @endforelse
                            <div class="px-4 py-2 border-t">
                                <a href="{{ route('notifications.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">View all notifications</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Dropdown -->
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center text-sm focus:outline-none transition">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center">
                                    <span class="text-xs font-medium text-white">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                                </div>
                                <div class="hidden md:block text-left">
                                    <div class="text-sm font-medium text-gray-900">{{ Auth::user()->name }}</div>
                                    <div class="text-xs text-gray-500">
                                        @foreach(Auth::user()->roles as $role)
                                            {{ ucfirst($role->name) }}
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <svg class="ml-2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </div>

</nav>

<!-- Mobile sidebar overlay -->
<div x-data 
     x-show="$store.sidebar && $store.sidebar.open" 
     @click="$store.sidebar.open = false"
     x-transition:enter="transition-opacity ease-linear duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden"
     style="display: none;"></div>
