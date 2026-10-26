<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Lesson Completion Approvals') }}
                </h2>
                <p class="text-sm text-gray-500">
                    {{ __('Review the lessons learners have marked as completed.') }}
                </p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                {{ __(':count pending', ['count' => $counts['pending']]) }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-100 border border-red-200 text-red-800 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex flex-wrap gap-2">
                            @foreach ([
                                'pending' => __('Pending'),
                                'approved' => __('Approved'),
                                'rejected' => __('Rejected'),
                                'all' => __('All'),
                            ] as $key => $label)
                                <a href="{{ route('lesson-approvals.index', array_filter(['status' => $key, 'search' => $search])) }}"
                                   class="px-3 py-1.5 rounded-full text-sm font-medium border
                                   {{ $status === $key
                                        ? 'bg-indigo-600 border-indigo-600 text-white'
                                        : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                                    {{ $label }}
                                    @if ($key !== 'all')
                                        <span class="ml-1 text-xs opacity-80">{{ $counts[$key] }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>

                        <form method="GET" action="{{ route('lesson-approvals.index') }}" class="flex gap-2">
                            <input type="hidden" name="status" value="{{ $status }}">
                            <input type="text" name="search" value="{{ $search }}"
                                   placeholder="{{ __('Search learner, lesson or module...') }}"
                                   class="w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-500">
                                {{ __('Search') }}
                            </button>
                            @if ($search !== '')
                                <a href="{{ route('lesson-approvals.index', ['status' => $status]) }}"
                                   class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded-md hover:bg-gray-300">
                                    {{ __('Clear') }}
                                </a>
                            @endif
                        </form>
                    </div>

                    @if ($completions->isEmpty())
                        <p class="text-gray-500 text-center py-8">
                            {{ $status === 'pending'
                                ? __('No lesson completions waiting for review.')
                                : __('No lesson completions match this filter.') }}
                        </p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Learner') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Lesson') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Submitted') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($completions as $completion)
                                        <tr class="align-top">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $completion->user?->name ?? __('Unknown learner') }}</div>
                                                <div class="text-sm text-gray-500">{{ $completion->user?->email }}</div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900">{{ $completion->lesson?->title ?? __('Deleted lesson') }}</div>
                                                <div class="text-xs text-gray-500">{{ $completion->module?->title }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $completion->created_at->diffForHumans() }}
                                                @if ($completion->reviewed_at)
                                                    <div class="text-xs text-gray-400">
                                                        {{ __('Reviewed :time', ['time' => $completion->reviewed_at->diffForHumans()]) }}
                                                        @if ($completion->reviewer)
                                                            {{ __('by :name', ['name' => $completion->reviewer->name]) }}
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if ($completion->status === 'pending')
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ __('Pending') }}</span>
                                                @elseif ($completion->status === 'approved')
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">{{ __('Approved') }}</span>
                                                @else
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">{{ __('Rejected') }}</span>
                                                    @if ($completion->rejection_reason)
                                                        <p class="mt-1 text-xs text-red-700 max-w-xs">{{ $completion->rejection_reason }}</p>
                                                    @endif
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                @if ($completion->status === 'pending')
                                                    <form action="{{ route('lesson-completions.approve', $completion->id) }}" method="POST" class="inline-block">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="text-green-600 hover:text-green-900 mr-3"
                                                                onclick="return confirm('{{ __('Approve this lesson completion?') }}')">
                                                            {{ __('Approve') }}
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('lesson-completions.reject', $completion->id) }}" method="POST" class="inline-flex items-center gap-2">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="text" name="reason" required placeholder="{{ __('Reason for rejection') }}"
                                                               class="w-48 rounded-md border-gray-300 shadow-sm text-sm">
                                                        <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Reject') }}</button>
                                                    </form>
                                                @else
                                                    <a href="{{ $completion->module ? route('modules.lessons.show', [$completion->module, $completion->lesson]) : '#' }}"
                                                       class="text-indigo-600 hover:text-indigo-800">
                                                        {{ __('Open lesson') }}
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $completions->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
