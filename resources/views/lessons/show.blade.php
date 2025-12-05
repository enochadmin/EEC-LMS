<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">
                    <a href="{{ route('modules.show', $module) }}" class="text-indigo-600 hover:underline">
                        {{ $module->title }}
                    </a>
                    / {{ __('Lesson') }}
                </p>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $lesson->title }}
                </h2>
            </div>
            @can('manage lessons')
                <a href="{{ route('lessons.edit', $lesson) }}" class="text-indigo-600 hover:underline text-sm">
                    {{ __('Edit Lesson') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex items-center gap-4 text-sm text-gray-500">
                    <span>{{ __('Order #:order', ['order' => $lesson->order]) }}</span>
                    <span>•</span>
                    <span>{{ __('Estimated: :minutes mins', ['minutes' => $lesson->estimated_minutes ?? __('N/A')]) }}</span>
                </div>

                <article class="prose max-w-none">
                    {!! nl2br(e($lesson->content)) !!}
                </article>

                <!-- YouTube Video -->
                @if ($lesson->youtube_link)
                    <div class="mt-6">
                        <h3 class="font-semibold text-gray-900 mb-2">{{ __('Video') }}</h3>
                        <div class="w-full aspect-video rounded-md overflow-hidden">
                            @php
                                // Extract YouTube video ID
                                preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $lesson->youtube_link, $matches);
                                $youtubeId = $matches[1] ?? null;
                            @endphp
                            @if($youtubeId)
                                <iframe src="https://www.youtube.com/embed/{{ $youtubeId }}" 
                                    class="w-full h-full" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen>
                                </iframe>
                            @else
                                <a href="{{ $lesson->youtube_link }}" target="_blank" class="text-indigo-600 hover:underline">
                                    {{ __('Open YouTube Video') }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- MP4 Video File -->
                @if ($lesson->video_path)
                    <div class="mt-6">
                        <h3 class="font-semibold text-gray-900 mb-2">{{ __('Video') }}</h3>
                        <div class="w-full">
                            <video controls class="w-full rounded-md" style="max-height: 500px;">
                                <source src="{{ Storage::url($lesson->video_path) }}" type="video/mp4">
                                {{ __('Your browser does not support the video tag.') }}
                            </video>
                        </div>
                    </div>
                @endif

                <!-- PDF File -->
                @if ($lesson->pdf_file_path)
                    <div class="mt-6">
                        <h3 class="font-semibold text-gray-900 mb-2">{{ __('PDF Document') }}</h3>
                        <div class="border border-gray-200 rounded-md p-4 bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ __('PDF Document') }}</p>
                                        <p class="text-xs text-gray-500">{{ __('Click to view or download') }}</p>
                                    </div>
                                </div>
                                <a href="{{ Storage::url($lesson->pdf_file_path) }}" target="_blank"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                                    {{ __('View PDF') }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Legacy Attachment Path -->
                @if ($lesson->attachment_path)
                    <div class="mt-4">
                        <a href="{{ $lesson->attachment_path }}" target="_blank"
                            class="text-indigo-600 hover:underline text-sm">
                            {{ __('Open attachment') }}
                        </a>
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-between text-sm">
                <a href="{{ route('modules.show', $module) }}" class="text-gray-600 hover:underline">
                    {{ __('Back to module') }}
                </a>
                @if(auth()->check() && auth()->user()->hasRole('learner'))
                    <form action="{{ route('modules.lessons.complete', ['module' => $module, 'lesson' => $lesson]) }}" method="POST" onsubmit="return confirm('Are you sure you want to mark this lesson completed? This will request approval from your instructor.');">
                        @csrf
                        <button type="submit" class="ml-4 inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-500">
                            {{ __('Mark as completed') }}
                        </button>
                    </form>
                @endif
                @can('manage lessons')
                    <form action="{{ route('lessons.destroy', $lesson) }}" method="POST"
                        onsubmit="return confirm('{{ __('Delete this lesson?') }}');">
                        @csrf
                        @method('DELETE')
                        <button class="text-red-600 hover:underline">{{ __('Delete lesson') }}</button>
                    </form>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>

