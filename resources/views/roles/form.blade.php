@php
    $isEdit = $role->exists;
    $isProtected = $role->name === 'admin';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $isEdit ? __('Edit role — :name', ['name' => $role->name]) : __('New role') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">{{ __('Tick the permissions this role should have.') }}</p>
            </div>
            <a href="{{ route('roles.index') }}" class="text-sm text-gray-600 hover:underline">{{ __('Back to roles') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            <form action="{{ $isEdit ? route('roles.update', $role) : route('roles.store') }}" method="POST" class="space-y-6">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <label class="block text-sm font-medium text-gray-700">{{ __('Role name') }}</label>
                    <input name="name" value="{{ old('name', $role->name) }}" required
                           @if($isProtected) readonly @endif
                           class="mt-1 block w-full max-w-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @if($isProtected) bg-gray-100 @endif" />
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $isProtected
                            ? __('The admin role is protected: its name cannot change.')
                            : __('Lowercase letters, numbers, spaces, dashes or underscores.') }}
                    </p>
                    @error('name')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-900">{{ __('Permissions') }}</h3>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        @foreach($groups as $group => $permissions)
                            <div class="border border-gray-200 rounded-md p-4">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ $group }}</p>
                                <div class="space-y-2">
                                    @foreach($permissions as $permission)
                                        <label class="flex items-center gap-2 text-sm text-gray-700">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                   {{ in_array($permission->name, $assigned, true) ? 'checked' : '' }} />
                                            {{ $permission->name }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @error('permissions')
                        <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('roles.index') }}" class="text-sm text-gray-600 hover:underline">{{ __('Cancel') }}</a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        {{ $isEdit ? __('Save role') : __('Create role') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
