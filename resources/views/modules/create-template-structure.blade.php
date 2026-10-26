@php
    $initialRows = [];

    if (old('structure')) {
        $decoded = json_decode(old('structure'), true);
        if (is_array($decoded)) {
            $initialRows = $decoded;
        }
    } elseif (! empty($draft['rows'])) {
        $initialRows = $draft['rows'];
    }

    if ($initialRows === []) {
        $initialRows = [
            ['type' => 'lesson', 'title' => '', 'minutes' => ''],
        ];
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Create Module from Template') }}</h2>
            <p class="text-sm text-gray-500 mt-1">{{ __('Step 1 of 3 — outline the structure') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Stepper --}}
            <ol class="flex items-center gap-3 text-sm">
                <li class="flex items-center gap-2 font-semibold text-indigo-600">
                    <span class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs">1</span>
                    {{ __('Structure') }}
                </li>
                <li class="text-gray-300">—</li>
                <li class="flex items-center gap-2 text-gray-400">
                    <span class="w-6 h-6 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-xs">2</span>
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

            <form action="{{ route('modules.create.template.structure') }}" method="POST" class="space-y-6">
                @csrf

                {{-- Module basics --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('Module details') }}</h3>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Title') }}</label>
                        <input name="title" value="{{ old('title', $draft['title'] ?? '') }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        @error('title')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Category') }}</label>
                            <select name="category_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('Select category') }}</option>
                                @foreach ($categories as $id => $name)
                                    <option value="{{ $id }}" @selected(old('category_id', $draft['category_id'] ?? null) == $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('category_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Duration (minutes)') }}</label>
                            <input type="number" min="1" name="duration_minutes"
                                value="{{ old('duration_minutes', $draft['duration_minutes'] ?? '') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            @error('duration_minutes')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Department') }}</label>
                            <select name="department_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('No department') }}</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected(old('department_id', $draft['department_id'] ?? null) == $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                            @error('department_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Advisors') }}</label>
                            <select name="advisors[]" multiple size="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ($advisorOptions as $advisor)
                                    <option value="{{ $advisor->id }}" @selected(in_array($advisor->id, (array) old('advisors', $draft['advisors'] ?? [])))>{{ $advisor->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">{{ __('Learners can contact these instructors.') }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Description') }}</label>
                        <textarea name="description" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $draft['description'] ?? '') }}</textarea>
                        @error('description')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="w-full sm:w-1/2">
                        <label class="block text-sm font-medium text-gray-700">{{ __('Status') }}</label>
                        <select name="status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach (['draft' => __('Draft'), 'published' => __('Published'), 'archived' => __('Archived')] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $draft['status'] ?? 'draft') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Structure builder --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6" x-data="moduleBuilder(@js($initialRows))">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('Structure') }}</h3>
                            <p class="text-xs text-gray-500 mt-1">{{ __('List the lessons and quizzes in the order learners should work through them. Content is added in the next step.') }}</p>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="addLesson()"
                                class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 text-sm font-medium rounded-md hover:bg-indigo-100">
                                + {{ __('Lesson') }}
                            </button>
                            <button type="button" @click="addQuiz()"
                                class="inline-flex items-center px-3 py-1.5 bg-emerald-50 text-emerald-700 text-sm font-medium rounded-md hover:bg-emerald-100">
                                + {{ __('Quiz') }}
                            </button>
                        </div>
                    </div>

                    <input type="hidden" name="structure" :value="JSON.stringify(rows)">

                    <div class="mt-4 space-y-2">
                        <template x-for="(row, index) in rows" :key="index">
                            <div class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 bg-gray-50">
                                <div class="pt-1 text-xs font-semibold text-gray-400 w-6 text-right" x-text="index + 1"></div>

                                <div class="flex-1 grid grid-cols-1 sm:grid-cols-12 gap-2">
                                    <div class="sm:col-span-2">
                                        <select x-model="row.type"
                                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="lesson">{{ __('Lesson') }}</option>
                                            <option value="quiz">{{ __('Quiz') }}</option>
                                        </select>
                                    </div>

                                    <div class="sm:col-span-6">
                                        <input x-model="row.title" placeholder="Title" required
                                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    </div>

                                    <div class="sm:col-span-2" x-show="row.type === 'lesson'">
                                        <input type="number" min="0" x-model="row.minutes" placeholder="Minutes"
                                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    </div>

                                    <div class="sm:col-span-2" x-show="row.type === 'quiz'">
                                        <input type="number" min="1" max="100" x-model="row.passing_score" placeholder="Pass %"
                                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    </div>
                                </div>

                                <div class="flex items-center gap-1 pt-1">
                                    <button type="button" @click="move(index, -1)" :disabled="index === 0"
                                        class="p-1 text-gray-400 hover:text-gray-700 disabled:opacity-30" title="{{ __('Move up') }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                    </button>
                                    <button type="button" @click="move(index, 1)" :disabled="index === rows.length - 1"
                                        class="p-1 text-gray-400 hover:text-gray-700 disabled:opacity-30" title="{{ __('Move down') }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <button type="button" @click="remove(index)"
                                        class="p-1 text-gray-400 hover:text-red-600" title="{{ __('Remove') }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <p class="mt-3 text-xs text-gray-500">
                        <span x-text="lessonCount"></span> {{ __('lesson(s)') }} ·
                        <span x-text="quizCount"></span> {{ __('quiz(zes)') }}
                    </p>

                    @error('structure')
                        <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <a href="{{ route('modules.create') }}" class="text-sm text-gray-600 hover:underline">{{ __('Back') }}</a>
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                        {{ __('Continue to content') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('moduleBuilder', (initial) => ({
                rows: Array.isArray(initial) && initial.length ? initial : [{ type: 'lesson', title: '', minutes: '' }],

                get lessonCount() {
                    return this.rows.filter(r => r.type === 'lesson').length;
                },
                get quizCount() {
                    return this.rows.filter(r => r.type === 'quiz').length;
                },
                addLesson() {
                    this.rows.push({ type: 'lesson', title: '', minutes: '' });
                },
                addQuiz() {
                    this.rows.push({ type: 'quiz', title: '', passing_score: 70 });
                },
                remove(index) {
                    this.rows.splice(index, 1);
                    if (!this.rows.length) this.addLesson();
                },
                move(index, direction) {
                    const target = index + direction;
                    if (target < 0 || target >= this.rows.length) return;
                    const [row] = this.rows.splice(index, 1);
                    this.rows.splice(target, 0, row);
                },
            }));
        });
    </script>
</x-app-layout>
