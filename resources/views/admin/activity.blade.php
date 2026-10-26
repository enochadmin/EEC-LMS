<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Activity Log') }}</h2>
            <span class="text-xs text-gray-500">{{ __('Admin only') }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <form method="GET" action="{{ route('admin.activity.index') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('User') }}</label>
                        <select name="user_id" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="">{{ __('All users') }}</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Action contains') }}</label>
                        <input type="text" name="action" value="{{ request('action') }}"
                            class="w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="e.g. modules.store">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('From') }}</label>
                        <input type="date" name="from" value="{{ request('from') }}"
                            class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('To') }}</label>
                        <input type="date" name="to" value="{{ request('to') }}"
                            class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                    </div>
                    <div class="sm:col-span-5 flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                            {{ __('Filter') }}
                        </button>
                        <a href="{{ route('admin.activity.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">
                            {{ __('Reset') }}
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('When') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('User') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Action') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Request') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('IP') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($logs as $log)
                            <tr class="align-top">
                                <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">
                                    {{ $log->created_at?->format('M d, Y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="font-medium text-gray-900">{{ $log->user?->name ?? __('System') }}</div>
                                    <div class="text-xs text-gray-500">{{ $log->user?->email }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="text-gray-900">{{ $log->action }}</div>
                                    @if($log->description)
                                        <div class="text-xs text-gray-500">{{ $log->description }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">
                                    <span class="inline-block px-1.5 py-0.5 rounded bg-gray-100 font-mono">{{ $log->method }}</span>
                                    {{ $log->path }}
                                    @if(!empty($log->meta['status']))
                                        <span class="ml-1 text-gray-400">· {{ $log->meta['status'] }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">{{ $log->ip }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
                                    {{ __('No activity recorded yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $logs->links() }}</div>
        </div>
    </div>
</x-app-layout>
