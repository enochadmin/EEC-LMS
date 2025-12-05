<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">
                    {{ $quiz->module?->title ?? __('Module quiz') }}
                </p>
            </div>
            <a href="{{ route('quizzes.index') }}" class="text-gray-600 hover:underline text-sm">
                {{ __('All quizzes') }}
            </a>
        </div>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $quiz->title }}
        </h2>
        <p class="text-sm text-gray-500">
            {{ __('Passing score: :score%', ['score' => $quiz->passing_score]) }}
        </p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @if (session('status'))
                    <div class="mb-4 text-sm text-green-700 bg-green-100 border border-green-200 rounded px-4 py-2">
                        {{ session('status') }}
                    </div>
                @endif
                <form action="{{ route('quizzes.submit', $quiz) }}" method="POST" class="space-y-6">
                    @csrf
                    @forelse ($quiz->questions as $question)
                        <div class="border border-gray-200 rounded-md p-4 space-y-3">
                            <p class="font-medium text-gray-900">
                                {{ $loop->iteration }}. {{ $question->question }}
                                <span class="text-xs text-gray-500">({{ $question->points }} {{ __('pts') }})</span>
                            </p>
                            <div class="space-y-2">
                                @foreach ($question->options as $option)
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option->id }}"
                                            class="text-indigo-600 border-gray-300 focus:ring-indigo-500" />
                                        <span>{{ $option->option_text }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('answers.' . $question->id)
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">{{ __('This quiz has no questions yet.') }}</p>
                    @endforelse

                    <div class="flex items-center justify-end">
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                            {{ __('Submit Quiz') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

