<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Module Completion Approvals') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4 flex items-center justify-between">
                        <form method="GET" action="{{ route('module-approvals.index') }}" class="flex gap-2">
                            <input type="text" name="search" value="{{ request('search') }}"
                                   placeholder="Search by learner or module..."
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-500">
                                Search
                            </button>
                            @if(request('search'))
                                <a href="{{ route('module-approvals.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                                    Clear
                                </a>
                            @endif
                        </form>
                    </div>

                    @if($approvals->isEmpty())
                        <p class="text-gray-500 text-center py-4">No pending module completion approvals.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Learner</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Module</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested At</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($approvals as $approval)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $approval->learner->name }}</div>
                                                <div class="text-sm text-gray-500">{{ $approval->learner->email }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ $approval->module->title }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $approval->created_at->diffForHumans() }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <form action="{{ route('module-approvals.approve', $approval) }}" method="POST" class="inline-flex items-center gap-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="date" name="valid_until" title="{{ __('Certificate expiry (optional)') }}"
                                                        class="w-36 rounded-md border-gray-300 shadow-sm text-sm" />
                                                    <button type="submit" class="text-green-600 hover:text-green-900 mr-3" onclick="return confirm('Approve this module completion and issue a certificate?')">
                                                        Approve
                                                    </button>
                                                </form>
                                                <span class="block text-xs text-gray-400 mt-1">
                                                    {{ __('Leave the date empty for a certificate that never expires.') }}
                                                </span>
                                                <form action="{{ route('module-approvals.reject', $approval) }}" method="POST" class="inline-flex items-center gap-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="text" name="reason" required placeholder="{{ __('Reason for rejection') }}"
                                                        class="w-44 rounded-md border-gray-300 shadow-sm text-sm" />
                                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                                        Reject
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $approvals->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


