<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Module Evaluations') }} — {{ $module->title }}
            </h2>
            <a href="{{ route('modules.show', $module) }}" class="text-sm text-gray-600 hover:text-gray-900">
                {{ __('Back to Module') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Student Evaluations') }}</h3>
                    @if(count($results) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quizzes Completed</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($results as $result)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $result['assignment']->user->name }}</div>
                                                <div class="text-sm text-gray-500">{{ $result['assignment']->user->email }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($result['progress'])
                                                    <div class="flex items-center">
                                                        <div class="w-24 bg-gray-200 rounded-full h-2 mr-2">
                                                            <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $result['progress']->percentage }}%"></div>
                                                        </div>
                                                        <span class="text-sm text-gray-700">{{ number_format($result['progress']->percentage, 1) }}%</span>
                                                    </div>
                                                @else
                                                    <span class="text-sm text-gray-500">Not started</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $result['assignment']->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                                       ($result['assignment']->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                                    {{ ucfirst($result['assignment']->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $result['quiz_results']->count() }} / {{ $module->quizzes->count() }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('modules.user-evaluation', [$module, $result['assignment']->user]) }}" 
                                                    class="text-indigo-600 hover:text-indigo-900">
                                                    View Details
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No students assigned to this module yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

