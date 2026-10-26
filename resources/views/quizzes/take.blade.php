<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full items-center justify-between gap-3">
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
                @if (!empty($attempt) && $attempt->expires_at)
                    <div class="mb-4 flex items-center justify-between rounded-md border border-amber-200 bg-amber-50 px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-amber-800">{{ __('Time remaining') }}</p>
                            <p class="text-xs text-amber-700">{{ __('The quiz submits automatically when the timer reaches zero.') }}</p>
                        </div>
                        <span id="quiz-timer-value" class="font-mono text-2xl font-semibold text-amber-900">--:--</span>
                    </div>
                @endif

                @if (!empty($attempt) && $quiz->attempts_allowed > 0)
                    <p class="mb-4 text-xs text-gray-500">
                        {{ __('Attempt :n of :max', ['n' => $attempt->attempt_number, 'max' => $quiz->attempts_allowed]) }}
                    </p>
                @endif

                <form id="quiz-form" action="{{ route('quizzes.submit', $quiz) }}" method="POST" class="space-y-6">
                    @csrf
                    @forelse ($quiz->questions as $question)
                        <div class="border border-gray-200 rounded-md p-4 space-y-3">
                            <p class="font-medium text-gray-900">
                                {{ $loop->iteration }}. {{ $question->question }}
                                <span class="text-xs text-gray-500">({{ $question->points }} {{ __('pts') }})</span>
                            </p>
                            @if ($question->options->isNotEmpty())
                                <div class="space-y-2">
                                    @foreach ($question->options as $option)
                                        <label class="flex items-center gap-2 text-sm text-gray-700">
                                            <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option->id }}"
                                                class="text-indigo-600 border-gray-300 focus:ring-indigo-500" />
                                            <span>{{ $option->option_text }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Your answer') }}</label>
                                    <textarea name="text_answers[{{ $question->id }}]" rows="4"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        placeholder="{{ __('Type your answer here') }}">{{ old('text_answers.' . $question->id) }}</textarea>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('Written answers are graded by your instructor.') }}</p>
                                    @error('text_answers.' . $question->id)
                                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif
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

    @if (!empty($attempt) && $attempt->expires_at)
        <script>
            (function () {
                let remaining = {{ (int) ($secondsRemaining ?? 0) }};
                const display = document.getElementById('quiz-timer-value');
                const form = document.getElementById('quiz-form');

                function tick() {
                    remaining = Math.max(0, remaining);
                    if (display) {
                        display.textContent =
                            String(Math.floor(remaining / 60)).padStart(2, '0') + ':' +
                            String(remaining % 60).padStart(2, '0');
                    }
                    if (remaining === 0) {
                        if (form && !form.dataset.submitted) {
                            form.dataset.submitted = '1';
                            form.submit();
                        }
                        return;
                    }
                    remaining--;
                }

                tick();
                setInterval(tick, 1000);
            })();
        </script>
    @endif
</x-app-layout>

