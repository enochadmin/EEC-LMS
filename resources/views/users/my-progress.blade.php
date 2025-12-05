<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Progress') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @forelse($enrollments as $enrollment)
                @php
                    $moduleProgress = $progress->get($enrollment->training_module_id);
                @endphp
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                <a href="{{ route('modules.show', $enrollment->module) }}" class="hover:text-indigo-600">
                                    {{ $enrollment->module->title }}
                                </a>
                            </h3>
                            <p class="text-sm text-gray-500">{{ $enrollment->module->category->name ?? 'Uncategorized' }}</p>
                        </div>
                        <div class="text-right">
                            <span class="px-3 py-1 text-sm font-semibold rounded-full 
                                {{ $enrollment->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                   ($enrollment->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                {{ ucfirst($enrollment->status) }}
                            </span>
                        </div>
                    </div>

                    @if($moduleProgress)
                        <div class="mb-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Overall Progress</span>
                                <span class="text-sm font-semibold text-gray-900 progress-percent" data-module-id="{{ $enrollment->training_module_id }}">{{ number_format($moduleProgress->percentage, 1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-indigo-600 h-3 rounded-full transition-all duration-300 progress-bar" data-module-id="{{ $enrollment->training_module_id }}" style="width: {{ $moduleProgress->percentage }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1 progress-text" data-module-id="{{ $enrollment->training_module_id }}">
                                {{ $moduleProgress->completed_lessons }} of {{ $moduleProgress->total_lessons }} lessons completed
                            </p>
                        </div>
                    @else
                        <div class="mb-4">
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-gray-300 h-3 rounded-full" style="width: 0%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Not started</p>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs text-gray-500">Lessons</p>
                            <p class="text-lg font-semibold text-gray-900">
                                {{ $enrollment->module->lessons->count() }} total
                            </p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs text-gray-500">Quizzes</p>
                            <p class="text-lg font-semibold text-gray-900">
                                {{ $enrollment->module->quizzes->count() }} total
                            </p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs text-gray-500">Last Accessed</p>
                            <p class="text-lg font-semibold text-gray-900">
                                {{ $moduleProgress && $moduleProgress->last_accessed_at ? $moduleProgress->last_accessed_at->diffForHumans() : 'Never' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <a href="{{ route('modules.show', $enrollment->module) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                            Continue Learning →
                        </a>
                        @if($enrollment->due_date)
                            <span class="text-xs text-gray-500">
                                Due: {{ $enrollment->due_date->format('M d, Y') }}
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center">
                    <p class="text-gray-500">You haven't enrolled in any modules yet.</p>
                    <a href="{{ route('my.enrollments') }}" class="mt-4 inline-block text-indigo-600 hover:text-indigo-800">
                        Browse Available Modules →
                    </a>
                </div>
            @endforelse
        </div>
    </div>
    <script>
        (function(){
            // Poll backend every 5 seconds for progress updates
            const pollInterval = 5000;
            async function fetchProgress(){
                try{
                    const res = await fetch('{{ route('api.my.progress') }}', {credentials: 'same-origin'});
                    if(!res.ok) return;
                    const data = await res.json();
                    // data keyed by training_module_id
                    for(const [moduleId, progress] of Object.entries(data)){
                        const percentEl = document.querySelector('.progress-percent[data-module-id="'+moduleId+'"]');
                        const barEl = document.querySelector('.progress-bar[data-module-id="'+moduleId+'"]');
                        const textEl = document.querySelector('.progress-text[data-module-id="'+moduleId+'"]');
                        if(percentEl && progress.percentage !== undefined){
                            percentEl.textContent = parseFloat(progress.percentage).toFixed(1) + '%';
                        }
                        if(barEl && progress.percentage !== undefined){
                            barEl.style.width = parseFloat(progress.percentage) + '%';
                        }
                        if(textEl){
                            textEl.textContent = (progress.completed_lessons || 0) + ' of ' + (progress.total_lessons || 0) + ' lessons completed';
                        }
                    }
                }catch(e){
                    // ignore polling errors silently
                }
            }

            // Start polling
            setInterval(fetchProgress, pollInterval);
            // Fetch immediately once
            fetchProgress();
        })();
    </script>
</x-app-layout>

