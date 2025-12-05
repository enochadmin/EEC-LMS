<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Quizzes') }}
            </h2>
            @can('manage quizzes')
                <a href="{{ route('quizzes.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                    {{ __('Create Quiz') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <x-search-form :route="route('quizzes.index')" placeholder="Search by title..." />
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-gray-600 uppercase tracking-wide text-xs">
                            <tr>
                                <th class="px-4 py-2">{{ __('Title') }}</th>
                                <th class="px-4 py-2">{{ __('Module') }}</th>
                                <th class="px-4 py-2">{{ __('Passing Score') }}</th>
                                <th class="px-4 py-2 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($quizzes as $quiz)
                                <tr>
                                    <td class="px-4 py-2 font-medium text-gray-900">{{ $quiz->title }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $quiz->module?->title ?? __('N/A') }}</td>
                                    <td class="px-4 py-2">{{ $quiz->passing_score }}%</td>
                                    <td class="px-4 py-2">
                                        <div class="flex items-center justify-end gap-3 text-sm">
                                            <a href="{{ route('quizzes.take', $quiz) }}"
                                                class="text-indigo-600 hover:underline">
                                                {{ __('Take') }}
                                            </a>
                                            @can('manage quizzes')
                                                <a href="{{ route('quizzes.edit', $quiz) }}"
                                                    class="text-gray-600 hover:underline">
                                                    {{ __('Edit') }}
                                                </a>
                                                <form action="{{ route('quizzes.destroy', $quiz) }}" method="POST"
                                                    onsubmit="return confirm('{{ __('Delete this quiz?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="text-red-600 hover:underline">{{ __('Delete') }}</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                        {{ __('No quizzes found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $quizzes->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

