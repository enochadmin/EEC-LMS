<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Lessons') }}
            </h2>
            @can('manage lessons')
                <a href="{{ route('modules.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                    {{ __('Manage Modules') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded mb-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <x-search-form :route="route('lessons.index')" placeholder="Search by title, content, or module..." />
                        @can('manage lessons')
                            <a href="{{ route('lessons.create') }}"
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                                {{ __('Create Lesson') }}
                            </a>
                        @endcan
                    </div>
                    
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('All Lessons') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left font-semibold text-gray-600">
                                <tr>
                                    <th class="px-4 py-2">{{ __('Title') }}</th>
                                    <th class="px-4 py-2">{{ __('Module') }}</th>
                                    <th class="px-4 py-2">{{ __('Order') }}</th>
                                    <th class="px-4 py-2">{{ __('Created') }}</th>
                                    <th class="px-4 py-2 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($lessons as $lesson)
                                    <tr>
                                        <td class="px-4 py-2">
                                            <a href="{{ route('modules.lessons.show', [$lesson->module, $lesson]) }}"
                                                class="text-indigo-600 hover:underline font-medium">
                                                {{ $lesson->title }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-2">
                                            <a href="{{ route('modules.show', $lesson->module) }}"
                                                class="text-gray-600 hover:underline">
                                                {{ $lesson->module->title }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-2">{{ $lesson->order }}</td>
                                        <td class="px-4 py-2 text-gray-500">
                                            {{ $lesson->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('modules.lessons.show', [$lesson->module, $lesson]) }}"
                                                    class="text-indigo-600 hover:underline text-sm">
                                                    {{ __('View') }}
                                                </a>
                                                @can('manage lessons')
                                                    <a href="{{ route('lessons.edit', $lesson) }}"
                                                        class="text-gray-600 hover:underline text-sm">
                                                        {{ __('Edit') }}
                                                    </a>
                                                    <form action="{{ route('lessons.destroy', $lesson) }}" 
                                                          method="POST" class="inline"
                                                          onsubmit="return confirm('{{ __('Delete this lesson?') }}');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="text-red-600 hover:underline text-sm">
                                                            {{ __('Delete') }}
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                            {{ __('No lessons found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $lessons->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
