<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <script>
            document.addEventListener('alpine:init', () => {
                // Sidebar open/closed state (shared across components)
                Alpine.store('sidebar', {
                    open: window.innerWidth >= 1024
                });

                // Theme (light/dark) state with localStorage persistence
                const storedTheme = localStorage.getItem('theme') || 'light';
                const isDark = storedTheme === 'dark';

                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }

                Alpine.store('theme', {
                    dark: isDark,
                    toggle() {
                        this.dark = !this.dark;
                        if (this.dark) {
                            document.documentElement.classList.add('dark');
                            localStorage.setItem('theme', 'dark');
                        } else {
                            document.documentElement.classList.remove('dark');
                            localStorage.setItem('theme', 'light');
                        }
                    }
                });
            });
        </script>
    </head>
    <body class="font-sans antialiased bg-gray-100 dark:bg-gray-900">
        <div class="flex min-h-screen">
            <!-- Sidebar -->
            @auth
                <x-sidebar :user="auth()->user()" />
            @endauth

            <!-- Main Content -->
            <div 
                x-data 
                class="flex flex-col flex-1 transition-[margin-left] duration-300 ease-in-out"
                :class="{ 'lg:ml-64': $store.sidebar.open, 'lg:ml-0': !$store.sidebar.open }">
                <!-- Navbar -->
                @include('layouts.navigation')

                <!-- Page Content -->
                <main class="flex-1 bg-gray-100 dark:bg-gray-900">
                    <div class="w-full px-4 sm:px-6 lg:px-8 py-6">
                        @if(session('status'))
                            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                                <span class="block sm:inline">{{ session('status') }}</span>
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                                <ul class="list-disc list-inside">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
