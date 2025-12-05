<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Enrollment Approvals') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded mb-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="GET" action="{{ route('enrollment-approvals.index') }}" class="mb-4 flex gap-3 items-end">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">{{ __('Search') }}</label>
                            <input type="text" name="search" value="{{ request('search') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="{{ __('Search by learner or module...') }}">
                        </div>
                        <div>
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                                {{ __('Filter') }}
                            </button>
                        </div>
                    </form>

                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        {{ $isAdmin ? __('All Pending Enrollment Requests') : __('My Modules\' Enrollment Requests') }}
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left font-semibold text-gray-600">
                                <tr>
                                    <th class="px-4 py-2">{{ __('Learner') }}</th>
                                    <th class="px-4 py-2">{{ __('Module') }}</th>
                                    <th class="px-4 py-2">{{ __('Requested At') }}</th>
                                    <th class="px-4 py-2 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($requests as $requestItem)
                                    <tr>
                                        <td class="px-4 py-2">
                                            <div class="font-medium text-gray-900">{{ $requestItem->user->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $requestItem->user->email }}</div>
                                        </td>
                                        <td class="px-4 py-2 text-gray-700">
                                            {{ $requestItem->module->title }}
                                        </td>
                                        <td class="px-4 py-2 text-gray-500">
                                            {{ $requestItem->created_at->diffForHumans() }}
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="flex items-center justify-end gap-2">
                                                <form action="{{ route('enrollment-approvals.approve', $requestItem) }}" method="POST"
                                                      onsubmit="return confirm('{{ __('Approve this enrollment request?') }}');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="text-xs px-3 py-1 rounded bg-green-600 text-white hover:bg-green-500">
                                                        {{ __('Approve') }}
                                                    </button>
                                                </form>
                                                <form action="{{ route('enrollment-approvals.reject', $requestItem) }}" method="POST"
                                                      onsubmit="return confirm('{{ __('Reject this enrollment request?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="text-xs px-3 py-1 rounded bg-red-600 text-white hover:bg-red-500">
                                                        {{ __('Reject') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                            {{ __('No pending enrollment requests.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $requests->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


