<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full items-center justify-between gap-3">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">
                    {{ $quiz->module?->title ?? __('Module quiz') }}
                </p>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Results') }} — {{ $quiz->title }}
                </h2>
            </div>
            @if($quiz->module)
                <a href="{{ route('modules.show', $quiz->module) }}" class="text-sm text-indigo-600 hover:underline">
                    {{ __('Back to module') }}
                </a>
            @endif
        </div>
    </x-slot>

    @php
        $totalQuestions = $quiz->questions->count();
        $correct = 0;
        $answered = 0;

        foreach ($quiz->questions as $question) {
            $answer = $userAnswers->get($question->id);
            if (! $answer) {
                continue;
            }
            $answered++;
            $correctOption = $question->options->firstWhere('is_correct', true);
            if ($correctOption && (int) $answer->selected_option_id === (int) $correctOption->id) {
                $correct++;
            }
        }

        $score = $totalQuestions > 0 ? round(($correct / $totalQuestions) * 100) : 0;
    @endphp

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
                    <div>
                        <p class="text-xs uppercase text-gray-500">{{ __('Score') }}</p>
                        <p class="text-3xl font-semibold text-gray-900">{{ $score }}%</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-gray-500">{{ __('Correct') }}</p>
                        <p class="text-3xl font-semibold text-gray-900">{{ $correct }} / {{ $totalQuestions }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-gray-500">{{ __('Passing score') }}</p>
                        <p class="text-3xl font-semibold text-gray-900">{{ $quiz->passing_score }}%</p>
                    </div>
                </div>

                @if($answered < $totalQuestions)
                    <p class="mt-4 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded px-3 py-2">
                        {{ __('You have not answered every question yet (:answered of :total).', ['answered' => $answered, 'total' => $totalQuestions]) }}
                    </p>
                @endif
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">{{ __('Your answers') }}</h3>

                @forelse($quiz->questions as $question)
                    @php
                        $answer = $userAnswers->get($question->id);
                        $correctOption = $question->options->firstWhere('is_correct', true);
                        $isCorrect = $answer && $correctOption && (int) $answer->selected_option_id === (int) $correctOption->id;
                    @endphp
                    <div class="border border-gray-200 rounded-md p-4">
                        <div class="flex items-start justify-between gap-4">
                            <p class="font-medium text-gray-900">
                                {{ $loop->iteration }}. {{ $question->question }}
                            </p>
                            @if($answer)
                                <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-semibold {{ $isCorrect ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700' }}">
                                    {{ $isCorrect ? __('Correct') : __('Incorrect') }}
                                </span>
                            @else
                                <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                    {{ __('Not answered') }}
                                </span>
                            @endif
                        </div>

                        <div class="mt-3 space-y-1 text-sm">
                            @foreach($question->options as $option)
                                @php
                                    $chosen = $answer && (int) $answer->selected_option_id === (int) $option->id;
                                @endphp
                                <div class="flex items-center gap-2 {{ $option->is_correct ? 'text-green-700 font-medium' : ($chosen ? 'text-red-700' : 'text-gray-600') }}">
                                    <span class="inline-block w-2 h-2 rounded-full {{ $option->is_correct ? 'bg-green-500' : ($chosen ? 'bg-red-500' : 'bg-gray-300') }}"></span>
                                    <span>{{ $option->option_text }}</span>
                                    @if($chosen)
                                        <span class="text-xs text-gray-500">({{ __('your answer') }})</span>
                                    @endif
                                    @if($option->is_correct)
                                        <span class="text-xs">({{ __('correct answer') }})</span>
                                    @endif
                                </div>
                            @endforeach

                            @if($question->options->isEmpty())
                                <p class="text-gray-700">
                                    <span class="text-gray-500">{{ __('Your answer:') }}</span>
                                    {{ $answer->text_answer ?? __('No answer submitted') }}
                                </p>
                                <p class="text-xs text-amber-700">{{ __('This answer is graded manually by your instructor.') }}</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">{{ __('This quiz has no questions.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
