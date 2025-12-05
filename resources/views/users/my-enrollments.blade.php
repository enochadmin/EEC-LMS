<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Enrollments') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- My Enrollments -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Enrolled Modules') }}</h3>
                @if($enrollments->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($enrollments as $enrollment)
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                <h4 class="font-semibold text-gray-900 mb-2">{{ $enrollment->module->title }}</h4>
                                <p class="text-sm text-gray-500 mb-3">{{ $enrollment->module->category->name ?? 'Uncategorized' }}</p>
                                <div class="flex items-center justify-between mb-3">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        {{ $enrollment->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                           ($enrollment->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                        {{ ucfirst($enrollment->status) }}
                                    </span>
                                    @if($enrollment->due_date)
                                        <span class="text-xs text-gray-500">Due: {{ $enrollment->due_date->format('M d, Y') }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-4 text-xs text-gray-500 mb-3">
                                    <span>{{ $enrollment->module->lessons_count }} lessons</span>
                                    <span>{{ $enrollment->module->quizzes_count }} quizzes</span>
                                </div>
                                <a href="{{ route('modules.show', $enrollment->module) }}" class="inline-block text-sm text-indigo-600 hover:text-indigo-800">
                                    View Module →
                                </a>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4">
                        {{ $enrollments->links() }}
                    </div>
                @else
                    <p class="text-sm text-gray-500">You haven't enrolled in any modules yet.</p>
                @endif
            </div>

            <!-- Available Modules -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Available Modules') }}</h3>
                @if($availableModules->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($availableModules as $module)
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                <h4 class="font-semibold text-gray-900 mb-2">{{ $module->title }}</h4>
                                <p class="text-sm text-gray-500 mb-3">{{ $module->category->name ?? 'Uncategorized' }}</p>
                                <p class="text-sm text-gray-700 mb-3 line-clamp-2">{{ Str::limit($module->description, 100) }}</p>
                                <div class="flex items-center space-x-4 text-xs text-gray-500 mb-3">
                                    <span>{{ $module->lessons_count }} lessons</span>
                                    <span>{{ $module->quizzes_count }} quizzes</span>
                                </div>
                                <form action="{{ route('modules.enroll', $module) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                                        Enroll Now
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4">
                        {{ $availableModules->links() }}
                    </div>
                @else
                    <p class="text-sm text-gray-500">No available modules to enroll in.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

