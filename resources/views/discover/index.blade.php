<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Discover courses') }}</h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ __('Browse every published course. Request enrolment and your instructor will approve it.') }}
                </p>
            </div>
            <a href="{{ route('modules.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                {{ __('My courses') }} &rarr;
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">{{ session('status') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            {{-- Filters --}}
            <form method="GET" action="{{ route('discover.index') }}" class="bg-white shadow-sm sm:rounded-lg p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('Search') }}</label>
                        <input type="search" name="q" value="{{ request('q') }}"
                               placeholder="{{ __('Course name or description') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('Category') }}</label>
                        <select name="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">{{ __('All categories') }}</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('Department') }}</label>
                        <select name="department" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">{{ __('All departments') }}</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) request('department') === (string) $department->id)>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        {{ __('Filter') }}
                    </button>
                    @if(request()->hasAny(['q', 'category', 'department']))
                        <a href="{{ route('discover.index') }}" class="text-sm text-gray-600 hover:underline">{{ __('Reset') }}</a>
                    @endif
                    <span class="ml-auto text-xs text-gray-500">
                        {{ trans_choice(':count course available|:count courses available', $modules->total(), ['count' => $modules->total()]) }}
                    </span>
                </div>
            </form>

            {{-- Catalogue --}}
            @if($modules->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <h3 class="mt-3 text-sm font-semibold text-gray-900">{{ __('No courses match your filters') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ __('Try a different search term, or clear the filters to see the whole catalogue.') }}
                    </p>
                    @if($modules->total() === 0)
                        <a href="{{ route('discover.index') }}" class="mt-4 inline-block text-sm text-indigo-600 hover:text-indigo-800">{{ __('Clear filters') }}</a>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                    @foreach($modules as $module)
                        @php $isPending = in_array($module->id, $pendingIds, true); @endphp
                        <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden flex flex-col hover:shadow-md transition-shadow">
                            <div class="h-1.5 bg-gradient-to-r from-indigo-500 to-violet-500"></div>

                            <div class="p-5 flex-1 flex flex-col">
                                <div class="flex items-start justify-between gap-2">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wide bg-indigo-50 text-indigo-700">
                                        {{ $module->category?->name ?? __('Uncategorised') }}
                                    </span>
                                    <span class="text-[11px] text-gray-500 whitespace-nowrap">
                                        {{ $module->duration_minutes ? $module->duration_minutes . ' min' : '' }}
                                    </span>
                                </div>

                                <h3 class="mt-3 text-base font-semibold text-gray-900 leading-snug">
                                    {{ $module->title }}
                                </h3>

                                <p class="mt-2 text-sm text-gray-600 line-clamp-3 flex-1">
                                    {{ \Illuminate\Support\Str::limit($module->description ?: __('No description provided.'), 140) }}
                                </p>

                                <div class="mt-4 flex items-center gap-4 text-xs text-gray-500">
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                        {{ trans_choice(':count lesson|:count lessons', $module->lessons_count) }}
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        {{ trans_choice(':count quiz|:count quizzes', $module->quizzes_count) }}
                                    </span>
                                </div>

                                <p class="mt-2 text-xs text-gray-400">
                                    {{ __('By :name', ['name' => $module->creator?->name ?? __('Unknown')]) }}
                                    @if($module->creator?->department)
                                        &bull; {{ $module->creator->department->name }}
                                    @endif
                                </p>
                            </div>

                            <div class="px-5 py-4 bg-gray-50 border-t border-gray-100">
                                @if($isPending)
                                    <span class="inline-flex items-center gap-2 text-xs font-medium text-amber-700">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        {{ __('Request pending approval') }}
                                    </span>
                                @else
                                    <form action="{{ route('modules.enroll', $module) }}" method="POST"
                                          data-confirm="Send an enrolment request for '{{ $module->title }}' to the instructor?"
                                          data-confirm-title="Request enrolment"
                                          data-confirm-button="Send request">
                                        @csrf
                                        <button type="submit"
                                                class="w-full inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                            {{ __('Request enrolment') }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-2">
                    {{ $modules->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
