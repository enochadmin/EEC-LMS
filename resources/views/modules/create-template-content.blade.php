@php
    $lessons = collect($draft['rows'])->where('type', 'lesson')->values();
    $quizzes = collect($draft['rows'])->where('type', 'quiz')->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $draft['title'] }}</h2>
            <p class="text-sm text-gray-500 mt-1">{{ __('Step 2 of 3 — add content') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Stepper --}}
            <ol class="flex items-center gap-3 text-sm">
                <li class="flex items-center gap-2 text-gray-400">
                    <span class="w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs">✓</span>
                    {{ __('Structure') }}
                </li>
                <li class="text-gray-300">—</li>
                <li class="flex items-center gap-2 font-semibold text-indigo-600">
                    <span class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs">2</span>
                    {{ __('Content') }}
                </li>
                <li class="text-gray-300">—</li>
                <li class="flex items-center gap-2 text-gray-400">
                    <span class="w-6 h-6 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-xs">3</span>
                    {{ __('Create') }}
                </li>
            </ol>

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-4">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-4">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Structure summary --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('Your structure') }}</h3>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $categoryName }}
                            @if ($draft['duration_minutes'])
                                · {{ $draft['duration_minutes'] }} {{ __('min') }}
                            @endif
                            · {{ ucfirst($draft['status'] ?? 'draft') }}
                        </p>
                    </div>
                    <a href="{{ route('modules.create.template') }}" class="text-sm text-indigo-600 hover:underline">{{ __('Edit structure') }}</a>
                </div>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase">{{ __('Lessons') }} ({{ $lessonCount }})</p>
                        <ol class="mt-2 space-y-1 text-sm text-gray-700 list-decimal list-inside">
                            @forelse ($lessons as $lesson)
                                <li>
                                    {{ $lesson['title'] }}
                                    @if (! empty($lesson['minutes']))
                                        <span class="text-xs text-gray-400">· {{ $lesson['minutes'] }} min</span>
                                    @endif
                                </li>
                            @empty
                                <li class="text-gray-400 list-none">{{ __('None') }}</li>
                            @endforelse
                        </ol>
                    </div>

                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase">{{ __('Quizzes') }} ({{ $quizCount }})</p>
                        <ol class="mt-2 space-y-1 text-sm text-gray-700 list-decimal list-inside">
                            @forelse ($quizzes as $quiz)
                                <li>{{ $quiz['title'] }} <span class="text-xs text-gray-400">· pass {{ $quiz['passing_score'] }}%</span></li>
                            @empty
                                <li class="text-gray-400 list-none">{{ __('None') }}</li>
                            @endforelse
                        </ol>
                    </div>
                </div>
            </div>

            {{-- Template download + upload --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('Content template') }}</h3>

                <div class="mt-4 grid grid-cols-1 lg:grid-cols-5 gap-6">
                    {{-- Instructions --}}
                    <div class="lg:col-span-3 space-y-4">
                        <ol class="text-sm text-gray-700 space-y-2 list-decimal list-inside">
                            <li>{{ __('Download the template below — it already lists your lessons and quizzes with the right numbers.') }}</li>
                            <li>{{ __('Open it in Excel, LibreOffice or Google Sheets and fill in the empty columns.') }}</li>
                            <li>{{ __('Save as CSV (UTF-8) and upload it together with your video/PDF files.') }}</li>
                        </ol>

                        <div class="rounded-md bg-gray-50 border border-gray-200 p-3 text-xs text-gray-600">
                            <p class="font-semibold text-gray-700 mb-1">{{ __('Columns') }}</p>
                            <ul class="space-y-1">
                                <li><code class="text-gray-800">content</code> — {{ __('lesson body text; line breaks are kept') }}</li>
                                <li><code class="text-gray-800">youtube_link</code> — {{ __('optional, one URL per lesson') }}</li>
                                <li><code class="text-gray-800">media_file</code> — {{ __('filename of a video you upload, e.g. lesson-1.mp4') }}</li>
                                <li><code class="text-gray-800">attachment_file</code> — {{ __('filename of a PDF/handout you upload') }}</li>
                                <li><code class="text-gray-800">quiz_no / question / option_a–d / correct_option</code> — {{ __('one row per question; leave option cells empty for a written answer') }}</li>
                            </ul>
                        </div>

                        <p class="text-xs text-gray-500">
                            {{ __('Filenames in the CSV must match the files you select below exactly (case-insensitive). Unmatched names are reported after creation and the lesson is still created.') }}
                        </p>

                        <a href="{{ route('modules.create.template.download') }}"
                            class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            {{ __('Download CSV template') }}
                        </a>
                    </div>

                    {{-- Upload form --}}
                    <div class="lg:col-span-2">
                        <form action="{{ route('modules.create.template.store') }}" method="POST"
                            enctype="multipart/form-data" class="space-y-4 rounded-lg border border-gray-200 p-4 bg-gray-50">
                            @csrf

                            <div>
                                <label class="block text-sm font-medium text-gray-700">{{ __('Filled template (.csv)') }}</label>
                                <input type="file" name="template_file" accept=".csv,text/csv"
                                    class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-white file:text-gray-700 hover:file:bg-gray-100" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">{{ __('Media files (optional)') }}</label>
                                <input type="file" name="media_files[]" multiple accept="video/*,application/pdf,.doc,.docx,.ppt,.pptx,.zip"
                                    class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-white file:text-gray-700 hover:file:bg-gray-100" />
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ __('Max :size per file, up to :count files per upload.', ['size' => ini_get('upload_max_filesize'), 'count' => ini_get('max_file_uploads')]) }}
                                </p>
                            </div>

                            <div class="pt-2 border-t border-gray-200">
                                <button type="submit"
                                    class="w-full inline-flex justify-center items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                                    {{ __('Create module') }}
                                </button>
                                <p class="mt-2 text-xs text-gray-500 text-center">
                                    {{ __('You can also create the structure now and add content later.') }}
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <a href="{{ route('modules.create.template') }}" class="text-sm text-gray-600 hover:underline">{{ __('Back to structure') }}</a>
                <a href="{{ route('modules.index') }}" class="text-sm text-gray-500 hover:underline">{{ __('Cancel') }}</a>
            </div>
        </div>
    </div>
</x-app-layout>
