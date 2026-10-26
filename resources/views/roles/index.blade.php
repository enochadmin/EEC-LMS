<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Roles & Permissions') }}</h2>
                <p class="text-sm text-gray-500 mt-1">{{ __('Who can do what. Roles are assigned to users; permissions are bundled into roles.') }}</p>
            </div>
            <a href="{{ route('roles.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                {{ __('New role') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">{{ session('status') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('Role') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('Users') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('Permissions') }}</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($roles as $role)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-900">{{ $role->name }}</span>
                                        @if($role->name === 'admin')
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-700 uppercase">{{ __('protected') }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600">{{ $role->users_count }}</td>
                                <td class="px-5 py-4 text-sm text-gray-600">
                                    {{ $role->permissions_count }} / {{ $permissionCount }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('roles.edit', $role) }}" class="text-sm text-indigo-600 hover:text-indigo-800">{{ __('Edit') }}</a>

                                        @if($role->name !== 'admin')
                                            <form action="{{ route('roles.destroy', $role) }}" method="POST"
                                                  data-confirm="Delete the role '{{ $role->name }}'? Users must be reassigned first."
                                                  data-confirm-title="Delete role"
                                                  data-confirm-button="Delete">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="text-xs text-gray-500">
                {{ __('Deleting a role never deletes its users. The admin role is locked so the system cannot be left without an administrator.') }}
            </p>
        </div>
    </div>
</x-app-layout>
