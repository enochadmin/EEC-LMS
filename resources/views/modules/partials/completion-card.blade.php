{{--
    Learner-facing "finish the whole course" panel.

    Lessons and quizzes are approved one at a time; this is the single action
    that closes the course, tells the instructor, and unlocks the certificate
    once they approve.
--}}
@php
    $completionUser = auth()->user();
    $isLearner = $completionUser?->hasRole('learner');
    $summary = null;
    $completion = null;

    if ($isLearner) {
        $summary = app(\App\Services\ProgressService::class)->summary($completionUser->id, $module->id);

        $completion = \App\Models\ModuleCompletionApproval::where('training_module_id', $module->id)
            ->where('user_id', $completionUser->id)
            ->first();
    }

    $allTasksDone = $summary && $summary['total_tasks'] > 0
        && $summary['completed_tasks'] >= $summary['total_tasks'];
@endphp

@if($isLearner && $summary && $summary['total_tasks'] > 0)

    @if($completion && $completion->status === 'approved')
        <div class="bg-green-50 border border-green-200 rounded-lg p-6 flex items-start gap-4">
            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div class="min-w-0">
                <h3 class="text-base font-semibold text-green-900">{{ __('Course completed') }}</h3>
                <p class="text-sm text-green-800 mt-1">
                    {{ __('Your instructor approved this course. Your certificate is ready to download.') }}
                </p>
                <a href="{{ route('certificates.index') }}"
                   class="inline-flex items-center mt-3 text-sm font-medium text-green-700 hover:text-green-900">
                    {{ __('View certificates') }} &rarr;
                </a>
            </div>
        </div>

    @elseif($completion && $completion->status === 'pending')
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-6 flex items-start gap-4">
            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-amber-100 flex items-center justify-center">
                <svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="min-w-0">
                <h3 class="text-base font-semibold text-amber-900">{{ __('Waiting for instructor approval') }}</h3>
                <p class="text-sm text-amber-800 mt-1">
                    {{ __('Your completion request was submitted. You will be notified as soon as it is reviewed.') }}
                </p>
            </div>
        </div>

    @elseif($allTasksDone)
        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                    <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-base font-semibold text-indigo-900">{{ __('You have finished every lesson and quiz') }}</h3>
                    <p class="text-sm text-indigo-800 mt-1">
                        {{ __('Finalise the whole course to send it to your instructor for approval. This is the step that unlocks your certificate.') }}
                    </p>

                    @if($completion && $completion->status === 'rejected' && $completion->notes)
                        <div class="mt-3 rounded-md bg-red-50 border border-red-200 px-3 py-2">
                            <p class="text-xs font-semibold text-red-800">{{ __('Your last request was not approved') }}</p>
                            <p class="text-xs text-red-700 mt-1">{{ $completion->notes }}</p>
                        </div>
                    @endif

                    <form action="{{ route('modules.complete', $module) }}" method="POST" class="mt-4"
                          data-confirm="You are submitting the whole course for final approval. Your instructor will review it and issue your certificate if approved. You will not be able to submit again while it is pending."
                          data-confirm-title="Finish the entire course?"
                          data-confirm-button="Submit for approval">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            {{ __('Mark as complete — entire course') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

    @elseif($completion && $completion->status === 'rejected' && $completion->notes)
        <div class="bg-red-50 border border-red-200 rounded-lg p-6">
            <h3 class="text-base font-semibold text-red-900">{{ __('Your completion request was not approved') }}</h3>
            <p class="text-sm text-red-800 mt-1">{{ $completion->notes }}</p>
            <p class="text-xs text-red-700 mt-2">
                {{ __('Finish the remaining :count task(s) and submit again.', ['count' => $summary['remaining_tasks']]) }}
            </p>
        </div>
    @endif

@endif
