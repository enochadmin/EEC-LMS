<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $module->title }}
                </h2>
                <p class="text-sm text-gray-500">
                    {{ $module->category?->name ?? __('Uncategorized') }} &bull;
                    {{ __('Duration: :minutes mins', ['minutes' => $module->duration_minutes ?? __('N/A')]) }} &bull;
                    {{ __(':lessons lessons', ['lessons' => $module->lessons->count()]) }} &bull;
                    {{ __(':quizzes quizzes', ['quizzes' => $module->quizzes->count()]) }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold capitalize
                    @class([
                        'bg-yellow-100 text-yellow-800' => $module->status === 'draft',
                        'bg-green-100 text-green-800' => $module->status === 'published',
                        'bg-gray-100 text-gray-800' => $module->status === 'archived',
                    ])">
                    {{ $module->status }}
                </span>
                @can('manage modules')
                    <a href="{{ route('modules.edit', $module) }}" class="text-indigo-600 hover:underline text-sm">
                        {{ __('Edit') }}
                    </a>
                    @if ($module->status !== 'published')
                        <form action="{{ route('modules.publish', $module) }}" method="POST">
                            @csrf
                            <button class="text-sm text-green-600 hover:underline">{{ __('Publish') }}</button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('modules.partials.completion-card')

            @if($module->advisors->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-900">{{ __('Course support') }}</h3>
                    <p class="text-xs text-gray-500 mt-1">{{ __('Contact an advisor if you need help with this course.') }}</p>
                    <ul class="mt-3 divide-y divide-gray-100">
                        @foreach($module->advisors as $advisor)
                            <li class="py-2 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $advisor->name }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ $advisor->job_title ?: __('Instructor') }}</p>
                                </div>
                                <div class="flex items-center gap-3 text-xs">
                                    @if($advisor->phone)<span class="text-gray-500">{{ $advisor->phone }}</span>@endif
                                    @if($advisor->email)<a href="mailto:{{ $advisor->email }}" class="text-indigo-600 hover:text-indigo-800">{{ __('Email') }}</a>@endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if (session('status'))
                <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-100 border border-red-200 text-red-800 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <!-- Module introduction: video, document and written overview -->
                    <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                        @if ($module->youtube_link || $module->video_file_path)
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('Introduction Video') }}</h3>
                                @if ($module->youtube_link)
                                    @php
                                        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $module->youtube_link, $matches);
                                        $youtubeId = $matches[1] ?? null;
                                    @endphp
                                    <div class="w-full aspect-video rounded-md overflow-hidden bg-black">
                                        @if ($youtubeId)
                                            <iframe src="https://www.youtube.com/embed/{{ $youtubeId }}"
                                                class="w-full h-full"
                                                frameborder="0"
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                allowfullscreen>
                                            </iframe>
                                        @else
                                            <div class="p-4">
                                                <a href="{{ $module->youtube_link }}" target="_blank" class="text-indigo-600 hover:underline">
                                                    {{ __('Open intro video') }}
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <video controls preload="metadata" class="w-full rounded-md bg-black" style="max-height: 500px;">
                                        <source src="{{ Storage::url($module->video_file_path) }}" type="video/mp4">
                                        {{ __('Your browser does not support the video tag.') }}
                                    </video>
                                @endif
                            </div>
                        @endif

                        @if ($module->pdf_file_path)
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('Module Document') }}</h3>
                                <div class="border border-gray-200 rounded-md p-4 bg-gray-50">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                        <div class="flex items-center space-x-3">
                                            <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                            </svg>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ __('Module PDF') }}</p>
                                                <p class="text-xs text-gray-500">{{ __('Read it online or download a copy') }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <a href="{{ Storage::url($module->pdf_file_path) }}" target="_blank"
                                                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                                                {{ __('View PDF') }}
                                            </a>
                                            <a href="{{ Storage::url($module->pdf_file_path) }}" download
                                                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                                                {{ __('Download') }}
                                            </a>
                                        </div>
                                    </div>

                                    <details class="mt-4">
                                        <summary class="cursor-pointer text-sm text-indigo-600 hover:text-indigo-800">
                                            {{ __('Preview the document here') }}
                                        </summary>
                                        <iframe src="{{ Storage::url($module->pdf_file_path) }}"
                                            class="mt-3 w-full h-[560px] rounded-md border border-gray-200 bg-white"
                                            title="{{ __('Module PDF') }}">
                                        </iframe>
                                    </details>
                                </div>
                            </div>
                        @endif

                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('Overview') }}</h3>
                            <p class="text-gray-700 whitespace-pre-line">{{ $module->description ?? __('No description yet.') }}</p>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-lg font-semibold text-gray-900">{{ __('Lessons') }}</h3>
                            @can('manage lessons')
                                <a href="{{ route('lessons.create', ['module_id' => $module->id]) }}"
                                    class="text-indigo-600 hover:underline text-sm">
                                    {{ __('Add Lesson') }}
                                </a>
                            @endcan
                        </div>
                        <div class="space-y-3">
                            @forelse ($module->lessons as $lesson)
                                @php
                                    $completionStatus = $lessonCompletions[$lesson->id]->status ?? null;
                                @endphp
                                <div class="border border-gray-200 rounded-md p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div>
                                        <h4 class="font-medium text-gray-900">
                                            <a href="{{ route('modules.lessons.show', [$module, $lesson]) }}"
                                                class="hover:underline">
                                                {{ $lesson->title }}
                                            </a>
                                        </h4>
                                        <p class="text-xs text-gray-500">
                                            {{ __('Estimated :minutes mins', ['minutes' => $lesson->estimated_minutes ?? __('N/A')]) }}
                                            @if ($lesson->content)
                                                &bull; {{ Str::limit(strip_tags($lesson->content), 90) }}
                                            @endif
                                        </p>
                                        @if ($completionStatus)
                                            <span class="inline-flex mt-1 px-2 py-0.5 text-xs font-semibold rounded-full
                                                @class([
                                                    'bg-yellow-100 text-yellow-800' => $completionStatus === 'pending',
                                                    'bg-green-100 text-green-800' => $completionStatus === 'approved',
                                                    'bg-red-100 text-red-800' => $completionStatus === 'rejected',
                                                ])">
                                                {{ $completionStatus === 'pending' ? __('Pending review') : ($completionStatus === 'approved' ? __('Approved') : __('Sent back')) }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-3 text-sm">
                                        <a href="{{ route('modules.lessons.show', [$module, $lesson]) }}"
                                            class="text-indigo-600 hover:underline">
                                            {{ $completionStatus === 'approved' ? __('Review') : ($completionStatus === 'pending' ? __('View status') : __('Open lesson')) }}
                                        </a>
                                        @can('manage lessons')
                                            <a href="{{ route('lessons.edit', $lesson) }}"
                                                class="text-gray-600 hover:underline">
                                                {{ __('Edit') }}
                                            </a>
                                            <form action="{{ route('lessons.destroy', $lesson) }}" method="POST"
                                                onsubmit="return confirm('{{ __('Delete this lesson?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-red-600 hover:underline">{{ __('Delete') }}</button>
                                            </form>
                                        @endcan
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">{{ __('No lessons added yet.') }}</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-lg font-semibold text-gray-900">{{ __('Quizzes') }}</h3>
                            @can('manage quizzes')
                                <a href="{{ route('quizzes.create', ['module_id' => $module->id]) }}"
                                    class="text-indigo-600 hover:underline text-sm">
                                    {{ __('Create Quiz') }}
                                </a>
                            @endcan
                        </div>
                        <div class="space-y-3">
                            @forelse ($module->quizzes as $quiz)
                                <div class="border border-gray-200 rounded-md p-4 flex items-center justify-between">
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ $quiz->title }}</h4>
                                        <p class="text-xs text-gray-500">
                                            {{ __('Passing Score: :score%', ['score' => $quiz->passing_score]) }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-3 text-sm">
                                        <a href="{{ route('quizzes.take', $quiz) }}"
                                            class="text-indigo-600 hover:underline">
                                            {{ __('Take Quiz') }}
                                        </a>
                                        @can('manage quizzes')
                                            <a href="{{ route('quizzes.edit', $quiz) }}"
                                                class="text-gray-600 hover:underline">
                                                {{ __('Edit') }}
                                            </a>
                                        @endcan
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">{{ __('No quizzes created for this module.') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    @role('learner')
                        @php
                            $approval = \App\Models\ModuleCompletionApproval::where('training_module_id', $module->id)
                                ->where('user_id', auth()->id())
                                ->first();

                            $nextLesson = $module->lessons->first(function ($lesson) use ($lessonCompletions) {
                                return ($lessonCompletions[$lesson->id]->status ?? null) !== 'approved';
                            });
                        @endphp
                        @if($module->status === 'published')
                            @if(!$isEnrolled)
                                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Enroll in Module') }}</h3>
                                    <p class="text-sm text-gray-600 mb-4">{{ __('Enroll yourself in this module to start learning.') }}</p>
                                    <form action="{{ route('modules.enroll', $module) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            class="w-full inline-flex justify-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                                            {{ __('Enroll Now') }}
                                        </button>
                                    </form>
                                </div>
                            @else
                                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-lg font-semibold text-gray-900">{{ __('Your progress') }}</h3>
                                        <span class="text-sm font-semibold text-gray-900">
                                            {{ number_format($progress['percentage'] ?? 0, 0) }}%
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $progress['percentage'] ?? 0 }}%"></div>
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        {{ __(':done of :total tasks done', ['done' => $progress['completed_tasks'] ?? 0, 'total' => $progress['total_tasks'] ?? 0]) }}
                                    </p>

                                    @if ($nextLesson)
                                        <a href="{{ route('modules.lessons.show', [$module, $nextLesson]) }}"
                                            class="w-full inline-flex justify-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                                            {{ ($lessonCompletions[$nextLesson->id] ?? null) ? __('Continue learning') : __('Start learning') }}
                                        </a>
                                    @endif

                                    <p class="text-sm font-medium text-green-800">✓ {{ __('You are enrolled in this module') }}</p>

                                    @if($approval && $approval->status === 'pending')
                                        <p class="text-xs text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-md px-3 py-2">
                                            {{ __('Your completion request is pending instructor approval.') }}
                                        </p>
                                    @elseif($approval && $approval->status === 'approved')
                                        <p class="text-xs text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2">
                                            {{ __('This module has been approved and completed.') }}
                                        </p>
                                    @else
                                        @if($approval && $approval->status === 'rejected' && $approval->notes)
                                            <p class="text-xs text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">
                                                {{ __('Your last completion request was sent back: :reason', ['reason' => $approval->notes]) }}
                                            </p>
                                        @endif
                                        <form action="{{ route('modules.complete', $module) }}" method="POST" class="pt-3 border-t border-gray-200">
                                            @csrf
                                            <button type="submit"
                                                class="w-full inline-flex justify-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-md hover:bg-emerald-500">
                                                {{ __('Complete Module (Request Approval)') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        @endif
                    @endrole

                    @can('assign modules')
                        <div class="bg-white shadow-sm sm:rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">{{ __('Assign Module') }}</h3>
                                <a href="{{ route('modules.assign.index', $module) }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                                    Manage Assignments &rarr;
                                </a>
                            </div>
                            <form action="{{ route('modules.assign', $module) }}" method="POST" class="space-y-4">
                                @csrf
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __('Learner') }}</label>
                                    <select name="user_id" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">{{ __('Select user') }}</option>
                                        @foreach ($assignableUsers as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __('Due Date') }}</label>
                                    <input type="date" name="due_date"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    @error('due_date')
                                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <button type="submit"
                                    class="w-full inline-flex justify-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                                    {{ __('Assign') }}
                                </button>
                            </form>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <a href="{{ route('modules.evaluations', $module) }}" class="block text-center text-sm text-indigo-600 hover:text-indigo-800">
                                    View All Evaluations &rarr;
                                </a>
                            </div>
                        </div>
                    @endcan

                    @can('assign modules')
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Assignments') }}</h3>
                        <div class="space-y-4 text-sm">
                            @forelse ($module->assignments as $assignment)
                                <div class="border border-gray-100 rounded-md p-3">
                                    <p class="font-medium text-gray-900">{{ $assignment->user?->name ?? __('Unknown user') }}</p>
                                    <p class="text-gray-500">
                                        {{ __('Due :date', ['date' => optional($assignment->due_date)->format('M d, Y') ?? __('TBD')]) }}
                                    </p>
                                    <span class="inline-flex mt-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">
                                        {{ ucfirst($assignment->status) }}
                                    </span>
                                </div>
                            @empty
                                <p class="text-gray-500">{{ __('This module has not been assigned yet.') }}</p>
                            @endforelse
                        </div>
                    </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>
    <x-time-tracker :module-id="$module->id" />
</x-app-layout>