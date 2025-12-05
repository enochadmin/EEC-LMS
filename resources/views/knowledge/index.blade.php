<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Knowledge Base') }}
            </h2>
            @can('manage knowledge')
                <a href="{{ route('knowledge.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                    {{ __('Upload File') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="mb-6 space-y-4">
                    <form method="GET" action="{{ route('knowledge.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Search') }}</label>
                            <input type="text" name="search" value="{{ request('search') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="{{ __('Search by title or description...') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Department') }}</label>
                            <select name="department_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('All') }}</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" @selected($departmentId == $dept->id)>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Module') }}</label>
                            <select name="module_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('All') }}</option>
                                @foreach($modules as $module)
                                    <option value="{{ $module->id }}" @selected($moduleId == $module->id)>
                                        {{ $module->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-3 flex items-center justify-end gap-3">
                            <a href="{{ route('knowledge.index') }}" class="text-sm text-gray-600 hover:underline">
                                {{ __('Clear') }}
                            </a>
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                                {{ __('Filter') }}
                            </button>
                        </div>
                    </form>
                </div>

                <div class="space-y-6">
                    @php
                        $groupedByDept = $files->groupBy(function($file) {
                            return $file->department->name ?? __('No Department');
                        });
                    @endphp

                    @forelse($groupedByDept as $deptName => $deptFiles)
                        <div>
                            <h3 class="text-md font-semibold text-gray-800 mb-2">{{ $deptName }}</h3>
                            @php
                                $groupedByModule = $deptFiles->groupBy(function($file) {
                                    return $file->module->title ?? __('No Module');
                                });
                            @endphp
                            <div class="space-y-4">
                                @foreach($groupedByModule as $moduleTitle => $moduleFiles)
                                    <div class="border border-gray-200 rounded-md">
                                        <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                                            <span class="text-sm font-medium text-gray-700">{{ $moduleTitle }}</span>
                                            <span class="text-xs text-gray-500">{{ $moduleFiles->count() }} {{ __('file(s)') }}</span>
                                        </div>
                                        <div class="divide-y divide-gray-100">
                                            @foreach($moduleFiles as $knowledge)
                                                <div class="px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                                    <div>
                                                        <p class="font-medium text-gray-900">{{ $knowledge->title }}</p>
                                                        <p class="text-sm text-gray-500">{{ $knowledge->description ?? __('No description') }}</p>
                                                        <p class="text-xs text-gray-400 mt-1">
                                                            {{ __('Uploaded by :name', ['name' => $knowledge->uploader?->name ?? __('Unknown')]) }}
                                                        </p>
                                                    </div>
                                                    <div class="flex items-center gap-3 text-sm">
                                                        <a href="{{ route('knowledge.download', $knowledge) }}"
                                                           class="text-indigo-600 hover:underline">
                                                            {{ __('Download') }}
                                                        </a>
                                                        @can('manage knowledge')
                                                            <a href="{{ route('knowledge.edit', $knowledge) }}" class="text-indigo-600 hover:underline">
                                                                {{ __('Edit') }}
                                                            </a>
                                                            <form action="{{ route('knowledge.destroy', $knowledge) }}" method="POST"
                                                                onsubmit="return confirm('{{ __('Delete this file?') }}');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="text-red-600 hover:underline">{{ __('Delete') }}</button>
                                                            </form>
                                                        @endcan
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">{{ __('No knowledge files yet.') }}</p>
                    @endforelse
                </div>

                <div class="mt-4">
                    {{ $files->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

