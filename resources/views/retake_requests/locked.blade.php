<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Content Locked') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 text-center">
                    <div class="mb-6">
                        <svg class="mx-auto h-20 w-22 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <h3 class="mt-2 text-lg font-medium text-gray-900">
                            @if(isset($isCompletionRequest) && $isCompletionRequest)
                                {{ __('Completion Pending Approval') }}
                            @else
                                {{ __('This content is locked') }}
                            @endif
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            @if(isset($isCompletionRequest) && $isCompletionRequest)
                                You have requested to mark this {{ $type }} as completed. Waiting for instructor approval.
                            @else
                                You have already completed this {{ $type }}. To retake it, you must request approval from your instructor.
                            @endif
                        </p>
                    </div>

                    @if($existingRequest)
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 text-left max-w-lg mx-auto">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        You have a pending request submitted on {{ $existingRequest->created_at->format('M d, Y') }}.
                                        <br>
                                        Status: <span class="font-bold uppercase">{{ $existingRequest->status }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <a href="{{ route('modules.show', $module) }}" class="text-indigo-600 hover:text-indigo-900">
                                &larr; Back to Module
                            </a>
                        </div>
                    @else
                        <form action="{{ route('retake-requests.store') }}" method="POST" class="max-w-lg mx-auto text-left">
                            @csrf
                            <input type="hidden" name="requestable_type" value="{{ $type }}">
                            <input type="hidden" name="requestable_id" value="{{ $item->id }}">

                            <div class="mb-4">
                                <label for="reason" class="block text-sm font-medium text-gray-700">Reason for Retake</label>
                                <textarea id="reason" name="reason" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Please explain why you need to retake this..."></textarea>
                            </div>

                            <div class="flex justify-between items-center">
                                <a href="{{ route('modules.show', $module) }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                                <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    Request Approval
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
