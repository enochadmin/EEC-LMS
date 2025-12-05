<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Evaluation') }} — {{ $user->name }}
            </h2>
            <a href="{{ route('modules.evaluations', $module) }}" class="text-sm text-gray-600 hover:text-gray-900">
                {{ __('Back to Evaluations') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Student Info -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Student Information') }}</h3>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Assignment Status</dt>
                        <dd class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $assignment->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                   ($assignment->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                {{ ucfirst($assignment->status) }}
                            </span>
                            @if($assignment->status !== 'completed')
                                <form action="{{ route('modules.user-evaluation.complete', [$module, $user]) }}" method="POST" class="mt-2">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-3 py-1 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150" onclick="return confirm('Are you sure you want to mark this module as completed for the user?');">
                                        {{ __('Mark as Completed') }}
                                    </button>
                                </form>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Certificate</dt>
                        <dd class="mt-1">
                            @php
                                $certificate = \App\Models\Certificate::where('user_id', $user->id)
                                    ->where('training_module_id', $module->id)
                                    ->first();
                            @endphp

                            @if($certificate)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Issued on {{ $certificate->issued_at->format('M d, Y') }}
                                </span>
                                <a href="{{ route('certificates.download', $certificate) }}" class="block mt-1 text-xs text-indigo-600 hover:text-indigo-900">Download</a>
                            @elseif($assignment->status === 'completed')
                                <form action="{{ route('admin.certificates.issue', [$user, $module]) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-3 py-1 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150" onclick="return confirm('Issue certificate to {{ $user->name }}?');">
                                        {{ __('Issue Certificate') }}
                                    </button>
                                </form>
                            @else
                                <span class="text-xs text-gray-500">Not eligible yet</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Progress Overview -->
            @if($progress)
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Module Progress') }}</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Overall Progress</span>
                            <span class="text-sm font-semibold text-gray-900">{{ number_format($progress->percentage, 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-indigo-600 h-3 rounded-full" style="width: {{ $progress->percentage }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $progress->completed_lessons }} of {{ $progress->total_lessons }} lessons completed
                        </p>
                    </div>
                    @if($progress->last_accessed_at)
                        <p class="text-sm text-gray-500">Last accessed: {{ $progress->last_accessed_at->diffForHumans() }}</p>
                    @endif
                </div>
            </div>
            @endif

            <!-- Quiz Results -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Quiz Results') }}</h3>
                @if(count($quizResults) > 0)
                    <div class="space-y-6">
                        @foreach($quizResults as $result)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-md font-semibold text-gray-900">{{ $result['quiz']->title }}</h4>
                                    <div class="flex items-center space-x-4">
                                        <div class="text-right">
                                            <p class="text-sm text-gray-500">Score</p>
                                            <p class="text-lg font-semibold {{ $result['passed'] ? 'text-green-600' : 'text-red-600' }}">
                                                {{ number_format($result['score'], 1) }}%
                                            </p>
                                        </div>
                                        <div>
                                            <span class="px-3 py-1 text-sm font-semibold rounded-full 
                                                {{ $result['passed'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $result['passed'] ? 'Passed' : 'Failed' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Correct Answers</p>
                                        <p class="text-lg font-semibold text-gray-900">
                                            {{ $result['correct_answers'] }} / {{ $result['total_questions'] }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Passing Score</p>
                                        <p class="text-lg font-semibold text-gray-900">{{ $result['quiz']->passing_score }}%</p>
                                    </div>
                                </div>
                                
                                <!-- Detailed Answers -->
                                <div class="mt-4 border-t border-gray-200 pt-4">
                                    <h5 class="text-sm font-medium text-gray-700 mb-2">Answer Details</h5>
                                    <div class="space-y-3">
                                        @foreach($result['user_answers'] as $answer)
                                            @php
                                                $correctOption = $answer->question->options()->where('is_correct', true)->first();
                                                $isMCQ = $answer->question->options()->count() > 0;
                                            @endphp
                                            <div class="p-3 rounded-md {{ $isMCQ && ($correctOption && $answer->selected_option_id == $correctOption->id) ? 'bg-green-50' : ($isMCQ ? 'bg-red-50' : 'bg-white') }}">
                                                <p class="text-sm font-medium text-gray-900 mb-1">{{ $answer->question->question }}</p>

                                                @if($isMCQ)
                                                    @php $isCorrect = $correctOption && $answer->selected_option_id == $correctOption->id; @endphp
                                                    <div class="flex items-center space-x-2">
                                                        <span class="text-xs {{ $isCorrect ? 'text-green-700' : 'text-red-700' }}">
                                                            {{ $isCorrect ? '✓ Correct' : '✗ Incorrect' }}
                                                        </span>
                                                        @if($answer->selectedOption)
                                                            <span class="text-xs text-gray-600">Selected: {{ $answer->selectedOption->option_text }}</span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <div class="text-sm text-gray-700 mb-2">
                                                        <strong>Answer:</strong>
                                                        <p class="mt-1 text-gray-800">{{ $answer->text_answer }}</p>
                                                    </div>

                                                    <div>
                                                        @if($answer->is_graded)
                                                            <p class="text-sm text-gray-600">Points awarded: <strong>{{ $answer->points_awarded }}</strong></p>
                                                        @else
                                                            <form action="{{ route('answers.grade', $answer) }}" method="POST" class="flex items-center gap-2">
                                                                @csrf
                                                                <label class="text-sm text-gray-600">Points:</label>
                                                                <input type="number" name="points_awarded" min="0" max="{{ $answer->question->points }}" value="0" class="rounded-md border-gray-300 shadow-sm w-24" />
                                                                <button type="submit" class="px-3 py-1 bg-green-600 text-white text-sm rounded-md">Grade</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500">No quiz results available yet.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

