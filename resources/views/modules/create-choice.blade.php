<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Training Module') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <p class="text-sm text-gray-600 mb-6">
                {{ __('Choose how you want to build this module. Both paths produce the same result — pick the one that fits how much material you already have prepared.') }}
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- Manual builder --}}
                <a href="{{ route('modules.create.manual') }}"
                    class="group bg-white shadow-sm sm:rounded-lg p-6 border-2 border-transparent hover:border-indigo-500 transition-colors flex flex-col">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 text-gray-600 group-hover:bg-indigo-50 group-hover:text-indigo-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ __('Manual builder') }}</h3>
                            <p class="text-xs text-gray-500">{{ __('What you use today') }}</p>
                        </div>
                    </div>

                    <p class="mt-4 text-sm text-gray-600 flex-1">
                        {{ __('Create the module shell, then add every lesson and quiz one by one, uploading files as you go. Best for one or two lessons, or when content lives in different places.') }}
                    </p>

                    <ul class="mt-4 space-y-1 text-sm text-gray-600">
                        <li class="flex items-start gap-2"><span class="text-gray-400">•</span>{{ __('Full control over each field') }}</li>
                        <li class="flex items-start gap-2"><span class="text-gray-400">•</span>{{ __('Nothing to prepare in advance') }}</li>
                    </ul>

                    <span class="mt-6 inline-flex items-center px-4 py-2 bg-gray-100 text-gray-800 text-sm font-medium rounded-md group-hover:bg-gray-200">
                        {{ __('Start manual') }}
                    </span>
                </a>

                {{-- Template --}}
                <a href="{{ route('modules.create.template') }}"
                    class="group bg-white shadow-sm sm:rounded-lg p-6 border-2 border-indigo-500 flex flex-col relative">
                    <span class="absolute top-4 right-4 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">
                        {{ __('Recommended for multi-lesson modules') }}
                    </span>

                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ __('Create from template') }}</h3>
                            <p class="text-xs text-gray-500">{{ __('Structure first, content second') }}</p>
                        </div>
                    </div>

                    <p class="mt-4 text-sm text-gray-600 flex-1">
                        {{ __('Outline the lessons and quizzes once, download a spreadsheet already filled with that outline, paste your descriptions and file names into it, then upload it back — everything is created in one go.') }}
                    </p>

                    <ol class="mt-4 space-y-1 text-sm text-gray-600 list-decimal list-inside">
                        <li>{{ __('Define the structure') }}</li>
                        <li>{{ __('Fill the Excel/CSV template') }}</li>
                        <li>{{ __('Upload it with your media files') }}</li>
                    </ol>

                    <span class="mt-6 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md group-hover:bg-indigo-500">
                        {{ __('Start from template') }}
                    </span>
                </a>
            </div>

            <div class="mt-6">
                <a href="{{ route('modules.index') }}" class="text-sm text-gray-600 hover:underline">{{ __('Cancel') }}</a>
            </div>
        </div>
    </div>
</x-app-layout>
