<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Quiz Result') }} — {{ $quiz->title }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center space-y-4">
                <div
                    class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold {{ $passed ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700' }}">
                    {{ $passed ? __('Passed') : __('Needs improvement') }}
                </div>
                <p class="text-4xl font-bold text-gray-900">
                    {{ $percentage }}%
                </p>
                <p class="text-gray-500">
                    {{ __('Passing score required: :score%', ['score' => $quiz->passing_score]) }}
                </p>
                <div class="flex items-center justify-center gap-4 text-sm">
                    <a href="{{ route('quizzes.take', $quiz) }}" class="text-indigo-600 hover:underline">
                        {{ __('Retake Quiz') }}
                    </a>
                    <a href="{{ route('quizzes.index') }}" class="text-gray-600 hover:underline">
                        {{ __('Back to all quizzes') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

