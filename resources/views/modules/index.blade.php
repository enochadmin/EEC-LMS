<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Training Modules') }}
            </h2>
            @can('manage modules')
                <a href="{{ route('modules.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                    {{ __('Create Module') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <x-search-form :route="route('modules.index')" placeholder="Search by title or description..." />
                    
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('All Modules') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left font-semibold text-gray-600">
                                <tr>
                                    <th class="px-4 py-2">{{ __('Title') }}</th>
                                    <th class="px-4 py-2">{{ __('Category') }}</th>
                                    <th class="px-4 py-2">{{ __('Status') }}</th>
                                    <th class="px-4 py-2">{{ __('Lessons') }}</th>
                                    <th class="px-4 py-2">{{ __('Quizzes') }}</th>
                                    <th class="px-4 py-2 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($modules as $module)
                                    <tr>
                                        <td class="px-4 py-2">
                                            <a href="{{ route('modules.show', $module) }}"
                                                class="text-indigo-600 hover:underline font-medium">
                                                {{ $module->title }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-2">{{ $module->category?->name ?? __('Uncategorized') }}</td>
                                        <td class="px-4 py-2 capitalize">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                    @class([
                                                        'bg-yellow-100 text-yellow-800' => $module->status === 'draft',
                                                        'bg-green-100 text-green-800' => $module->status === 'published',
                                                        'bg-gray-100 text-gray-800' => $module->status === 'archived',
                                                    ])">
                                                {{ $module->status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2">{{ $module->lessons_count }}</td>
                                        <td class="px-4 py-2">{{ $module->quizzes_count }}</td>
                                        <td class="px-4 py-2">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('modules.show', $module) }}"
                                                    class="text-indigo-600 hover:underline text-sm">
                                                    {{ __('View') }}
                                                </a>
                                                @can('manage modules')
                                                    <a href="{{ route('modules.edit', $module) }}"
                                                        class="text-gray-600 hover:underline text-sm">
                                                        {{ __('Edit') }}
                                                    </a>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-4 text-center text-gray-500">
                                            {{ __('No modules found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $modules->links() }}
                    </div>
                </div>
            </div>

            @if (isset($assignments) && $assignments->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Your assignments') }}</h3>
                        <ul class="space-y-3">
                            @foreach ($assignments as $assignment)
                                <li class="flex items-center justify-between text-sm">
                                    <div>
                                        <p class="font-medium text-gray-900">
                                            {{ $assignment->module?->title ?? __('Unknown module') }}
                                        </p>
                                        <p class="text-gray-500">
                                            {{ __('Due :date', ['date' => optional($assignment->due_date)->format('M d, Y') ?? __('TBD')]) }}
                                        </p>
                                    </div>
                                    <span class="text-xs uppercase tracking-wide text-gray-500">
                                        {{ $assignment->status }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

