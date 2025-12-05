<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Lesson') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('lessons.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Training Module') }}</label>
                        <select name="training_module_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('Select module') }}</option>
                            @foreach ($modules as $id => $title)
                                <option value="{{ $id }}"
                                    @selected(old('training_module_id', $selectedModule) == $id)>
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
                        <input name="title" value="{{ old('title') }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        @error('title')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Content') }}</label>
                        <textarea name="content" rows="6"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('content') }}</textarea>
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
                                    <span class="text-xs text-gray-500">(Max: 50MB)</span>
                                </label>
                                <input type="file" name="video_file" accept=".mp4,.avi,.mov,.wmv"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                @error('video_file')
                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- YouTube Links (multiple) -->
                            <div x-data="{ links: [{ value: '{{ old('youtube_link') }}' }@if(old('youtube_links')) , @json(collect(old('youtube_links'))->map(fn($l)=>['value'=>$l])->values()) @endif].filter(l => l.value !== '') }">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('YouTube Links') }}
                                    <span class="text-xs text-gray-500">(You can add multiple links)</span>
                                </label>
                                <template x-for="(link, index) in links" :key="index">
                                    <div class="flex items-center gap-2 mb-2">
                                        <input x-model="link.value" type="url" name="youtube_links[]"
                                            placeholder="https://www.youtube.com/watch?v=..."
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                        <button type="button" @click="links.splice(index,1)" class="text-xs text-red-600 hover:underline">
                                            {{ __('Remove') }}
                                        </button>
                                    </div>
                                </template>
                                <button type="button" @click="links.push({value:''})" class="text-xs text-indigo-600 hover:underline">
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
                            <input type="number" name="order" value="{{ old('order', 0) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            @error('order')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Estimated Minutes') }}</label>
                            <input type="number" name="estimated_minutes" value="{{ old('estimated_minutes') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            @error('estimated_minutes')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ url()->previous() }}" class="text-gray-600 hover:underline">
                            {{ __('Cancel') }}
                        </a>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                            {{ __('Save Lesson') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

