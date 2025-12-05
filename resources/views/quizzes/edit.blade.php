<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">
                    {{ $quiz->module?->title ?? __('Unassigned Module') }}
                </p>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Edit Quiz') }} — {{ $quiz->title }}
                </h2>
            </div>
            <a href="{{ route('quizzes.index') }}" class="text-gray-600 hover:underline text-sm">
                {{ __('Back to list') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('quizzes.update', $quiz) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Quiz Title') }}</label>
                        <input name="title" value="{{ old('title', $quiz->title) }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        @error('title')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Passing Score (%)') }}</label>
                        <input type="number" name="passing_score" min="1" max="100"
                            value="{{ old('passing_score', $quiz->passing_score) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        @error('passing_score')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                            {{ __('Save Changes') }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ __('Questions & Options') }}
                    </h3>
                    <span class="text-sm text-gray-500">
                        {{ $quiz->questions->count() }}
                        {{ $quiz->questions->count() === 1 ? __('question') : __('questions') }}
                    </span>
                </div>

                <div class="space-y-4">
                    <!-- Add Question Form -->
                    <div class="border border-dashed border-gray-200 rounded-md p-4">
                        <form action="{{ route('quizzes.questions.store', $quiz) }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700">{{ __('Question') }}</label>
                                <textarea name="question" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" rows="3">{{ old('question') }}</textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __('Points') }}</label>
                                    <input type="number" name="points" min="0" value="{{ old('points', 1) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __('Type') }}</label>
                                    <select name="type" id="question-type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                        <option value="mcq">Multiple Choice</option>
                                        <option value="short">Short Answer</option>
                                    </select>
                                </div>
                            </div>

                            <div id="mcq-options" class="space-y-2 mt-2">
                                <p class="text-xs text-gray-500">{{ __('Provide up to 4 options. Select the correct one.') }}</p>
                                @for($i=0;$i<4;$i++)
                                    <div class="flex items-center gap-2">
                                        <input type="radio" name="correct_option_index" value="{{ $i }}" class="form-radio" {{ old('correct_option_index') == $i ? 'checked' : '' }} />
                                        <input type="text" name="options[{{ $i }}]" placeholder="Option {{ $i+1 }}" value="{{ old('options.'.$i) }}" class="flex-1 rounded-md border-gray-300 shadow-sm" />
                                    </div>
                                @endfor
                            </div>

                            <div class="flex items-center justify-end">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">Add Question</button>
                            </div>
                        </form>
                    </div>

                    <script>
                        (function(){
                            const typeSelect = document.getElementById('question-type');
                            const mcq = document.getElementById('mcq-options');
                            function toggle(){
                                if(typeSelect.value === 'mcq'){ mcq.style.display = 'block'; } else { mcq.style.display = 'none'; }
                            }
                            typeSelect.addEventListener('change', toggle);
                            toggle();
                        })();
                    </script>
                    @forelse ($quiz->questions as $question)
                        <div class="border border-gray-200 rounded-md p-4">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $question->question }}</p>
                                    <p class="text-xs text-gray-500">{{ __('Points: :points', ['points' => $question->points]) }}</p>
                                </div>
                            </div>
                            <ul class="mt-3 space-y-2 text-sm">
                                @foreach ($question->options as $option)
                                    <li class="flex items-center gap-2">
                                        <span
                                            class="inline-flex w-2 h-2 rounded-full {{ $option->is_correct ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                        <span class="{{ $option->is_correct ? 'font-semibold text-gray-900' : 'text-gray-600' }}">
                                            {{ $option->option_text }}
                                            @if ($option->is_correct)
                                                <span class="text-xs text-green-600 ml-2">{{ __('Correct answer') }}</span>
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">{{ __('No questions available yet.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

