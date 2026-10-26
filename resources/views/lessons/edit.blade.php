<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Lesson') }} — {{ $lesson->title }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('lessons.update', $lesson) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Training Module') }}</label>
                        <select name="training_module_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($modules as $id => $title)
                                <option value="{{ $id }}" @selected(old('training_module_id', $lesson->training_module_id) == $id)>
                                    {{ $title }}
                                </option>
                            @endforeach
                        </select>
                        @error('training_module_id')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Title') }}</label>
                        <input name="title" value="{{ old('title', $lesson->title) }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        @error('title')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Content') }}</label>
                        <textarea name="content" rows="6"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('content', $lesson->content) }}</textarea>
                        @error('content')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- File Attachments Section -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Media & Attachments') }}</h3>
                        
                        <div class="space-y-4">
                            <!-- Video File (MP4) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('Video File (MP4)') }}
                                    <span class="text-xs text-gray-500">(Max: 30MB — MP4, MOV, AVI, WMV)</span>
                                </label>
                                @if($lesson->video_path)
                                    <div class="mb-2 p-3 bg-gray-50 rounded-md flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                            <a href="{{ Storage::url($lesson->video_path) }}" target="_blank" class="text-sm text-indigo-600 hover:underline">
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

                            <!-- YouTube Links (multiple) -->
                            @php
                                $existingLinks = [];
                                if ($lesson->youtube_links) {
                                    $existingLinks = json_decode($lesson->youtube_links, true) ?: [];
                                } elseif ($lesson->youtube_link) {
                                    $existingLinks = [$lesson->youtube_link];
                                }
                                $oldLinks = collect(old('youtube_links', []))->filter()->values()->all();
                                if ($oldLinks) {
                                    $existingLinks = $oldLinks;
                                }
                            @endphp
                            <div x-data="{ links: @json($existingLinks ?: ['']) }">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('YouTube Links') }}
                                    <span class="text-xs text-gray-500">(You can add multiple links)</span>
                                </label>
                                <template x-for="(link, index) in links" :key="index">
                                    <div class="flex items-center gap-2 mb-2">
                                        <input x-model="links[index]" type="url" name="youtube_links[]"
                                            placeholder="https://www.youtube.com/watch?v=..."
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                        <button type="button" @click="links.splice(index,1)" class="text-xs text-red-600 hover:underline">
                                            {{ __('Remove') }}
                                        </button>
                                    </div>
                                </template>
                                <button type="button" @click="links.push('')" class="text-xs text-indigo-600 hover:underline">
                                    + {{ __('Add Video') }}
                                </button>
                                <p class="mt-1 text-xs text-gray-500">You can upload a video file OR provide one or more YouTube links.</p>
                                @error('youtube_links.*')
                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- PDF File -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('PDF File') }}
                                    <span class="text-xs text-gray-500">(Max: 10MB)</span>
                                </label>
                                @if($lesson->pdf_file_path)
                                    <div class="mb-2 p-3 bg-gray-50 rounded-md flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                            </svg>
                                            <a href="{{ Storage::url($lesson->pdf_file_path) }}" target="_blank" class="text-sm text-indigo-600 hover:underline">
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
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Display Order') }}</label>
                            <input type="number" name="order" value="{{ old('order', $lesson->order) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            @error('order')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Estimated Minutes') }}</label>
                            <input type="number" name="estimated_minutes"
                                value="{{ old('estimated_minutes', $lesson->estimated_minutes) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            @error('estimated_minutes')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <a href="{{ route('modules.show', $lesson->module) }}" class="text-gray-600 hover:underline">
                            {{ __('Back') }}
                        </a>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                            {{ __('Update Lesson') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

