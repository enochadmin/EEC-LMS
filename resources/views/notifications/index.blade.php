<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Notifications') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @php
                        $currentUser = auth()->user();
                    @endphp
                    @if($notifications->isEmpty())
                        <p class="text-gray-500 text-center py-8">No notifications yet.</p>
                    @else
                        <div class="space-y-4">
                            @foreach($notifications as $notification)
                                @php
                                    $data = $notification->data;
                                    $type = $data['type'] ?? null;
                                    $message = $data['message'] ?? 'New notification';

                                    if ($type === 'enrollment' && isset($data['user_id']) && $data['user_id'] === $currentUser->id) {
                                        $message = "You have been enrolled in {$data['module_title']}";
                                    }
                                    if ($type === 'module_completed' && isset($data['learner_id']) && $data['learner_id'] === $currentUser->id) {
                                        $message = "You completed the module '{$data['module_title']}'";
                                    }
                                    if ($type === 'certificate_issued' && isset($data['module_title'])) {
                                        // Already user-centric: "You have been issued..."
                                        $message = $data['message'];
                                    }
                                    if ($type === 'lesson_approved' && isset($data['lesson_title'])) {
                                        $message = $data['message'];
                                    }
                                    if ($type === 'retake_response' && isset($data['item_title'])) {
                                        $message = $data['message'];
                                    }
                                    // Requests/attempts/enrollments targeting managers keep learner names as-is
                                @endphp
                                <div class="border-b pb-4 {{ $notification->read_at ? 'opacity-60' : '' }}">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <p class="text-sm text-gray-900">
                                                {{ $message }}
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ $notification->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                        @if(!$notification->read_at)
                                            <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800 ml-4">
                                                    Mark as read
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $notifications->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
