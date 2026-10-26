<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Module') }} — {{ $module->title }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('modules.update', $module) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Title') }}</label>
                        <input name="title" value="{{ old('title', $module->title) }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        @error('title')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Category') }}</label>
                        <select name="category_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($categories as $id => $name)
                                <option value="{{ $id }}" @selected(old('category_id', $module->category_id) == $id)>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Department') }}</label>
                            <select name="department_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('No department') }}</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected(old('department_id', $module->department_id) == $department->id)>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">{{ __('Used for department reporting. Optional.') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Advisors') }}</label>
                            <select name="advisors[]" multiple size="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ($advisorOptions as $advisor)
                                    <option value="{{ $advisor->id }}" @selected(in_array($advisor->id, (array) old('advisors', $selectedAdvisors)))>
                                        {{ $advisor->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">{{ __('Instructors learners can contact for help.') }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Description') }}</label>
                        <textarea name="description" rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $module->description) }}</textarea>
                        @error('description')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Duration (minutes)') }}</label>
                            <input type="number" min="1" name="duration_minutes"
                                value="{{ old('duration_minutes', $module->duration_minutes) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            @error('duration_minutes')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Status') }}</label>
                            <select name="status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach (['draft', 'published', 'archived'] as $status)
                                    <option value="{{ $status }}" @selected(old('status', $module->status) === $status)>
                                        {{ ucfirst($status) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- File Attachments Section -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('File Attachments') }}</h3>
                        
                        <div class="space-y-4">
                            <!-- PDF File -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('PDF File') }}
                                    <span class="text-xs text-gray-500">(Max: 10MB)</span>
                                </label>
                                @if($module->pdf_file_path)
                                    <div class="mb-2 p-3 bg-gray-50 rounded-md flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                            </svg>
                                            <a href="{{ Storage::url($module->pdf_file_path) }}" target="_blank" class="text-sm text-indigo-600 hover:underline">
                                                View PDF
                                            </a>
                                        </div>
                                        <label class="flex items-center text-sm text-red-600 cursor-pointer">
                                            <input type="checkbox" name="remove_pdf" value="1" class="mr-1">
                                            Remove
                                        </label>
                                    </div>
                                @endif
                                <input type="file" name="pdf_file" accept=".pdf"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                @error('pdf_file')
                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Video File (MP4) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('Video File (MP4)') }}
                                    <span class="text-xs text-gray-500">(Max: 50MB)</span>
                                </label>
                                @if($module->video_file_path)
                                    <div class="mb-2 p-3 bg-gray-50 rounded-md flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                            <a href="{{ Storage::url($module->video_file_path) }}" target="_blank" class="text-sm text-indigo-600 hover:underline">
                                                View Video
                                            </a>
                                        </div>
                                        <label class="flex items-center text-sm text-red-600 cursor-pointer">
                                            <input type="checkbox" name="remove_video" value="1" class="mr-1">
                                            Remove
                                        </label>
                                    </div>
                                @endif
                                <input type="file" name="video_file" accept=".mp4,.avi,.mov,.wmv"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                @error('video_file')
                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- YouTube Link -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('YouTube Link') }}
                                    <span class="text-xs text-gray-500">(Alternative to video file)</span>
                                </label>
                                @if($module->youtube_link)
                                    <div class="mb-2 p-3 bg-gray-50 rounded-md">
                                        <a href="{{ $module->youtube_link }}" target="_blank" class="text-sm text-indigo-600 hover:underline">
                                            {{ $module->youtube_link }}
                                        </a>
                                    </div>
                                @endif
                                <input type="url" name="youtube_link" value="{{ old('youtube_link', $module->youtube_link) }}" 
                                    placeholder="https://www.youtube.com/watch?v=..."
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                <p class="mt-1 text-xs text-gray-500">You can upload a video file OR provide a YouTube link</p>
                                @error('youtube_link')
                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <button type="submit" form="delete-module-form"
                            onclick="return confirm('{{ __('Delete this module?') }}');"
                            class="text-red-600 hover:underline text-sm">
                            {{ __('Delete') }}
                        </button>

                        <div class="flex items-center gap-3">
                            <a href="{{ route('modules.show', $module) }}" class="text-gray-600 hover:underline">
                                {{ __('Back') }}
                            </a>
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                                {{ __('Update Module') }}
                            </button>
                        </div>
                    </div>
                </form>

                <form id="delete-module-form" action="{{ route('modules.destroy', $module) }}" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

