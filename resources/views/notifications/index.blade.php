<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Notifications') }}
            </h2>
            @if(auth()->user()->unreadNotifications()->count() > 0)
                <form action="{{ route('notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-800">
                        {{ __('Mark all as read') }}
                    </button>
                </form>
            @endif
        </div>
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
                                    if (in_array($type, ['certificate_issued', 'lesson_approved', 'retake_response'], true) && isset($data['message'])) {
                                        // Already user-centric: keep the stored wording.
                                        $message = $data['message'];
                                    }

                                    // Where a click should take the reader (null = not clickable).
                                    $url = \App\Support\NotificationUrl::for($data, $currentUser);
                                @endphp
                                <div class="border-b pb-4 {{ $notification->read_at ? 'opacity-60' : '' }}">
                                    <div class="flex justify-between items-start gap-4">
                                        <div class="flex-1">
                                            @if($url)
                                                <a href="{{ route('notifications.open', $notification->id) }}" class="group block">
                                                    <p class="text-sm text-gray-900 group-hover:text-indigo-700 flex items-start gap-2">
                                                        @if(!$notification->read_at)
                                                            <span class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full bg-indigo-500"></span>
                                                        @endif
                                                        <span>{{ $message }}</span>
                                                    </p>
                                                    <p class="text-xs text-gray-500 mt-1 ml-4">
                                                        {{ $notification->created_at->diffForHumans() }}
                                                        <span class="text-indigo-600 group-hover:underline ml-1">{{ __('View') }} &rarr;</span>
                                                    </p>
                                                </a>
                                            @else
                                                <p class="text-sm text-gray-900 flex items-start gap-2">
                                                    @if(!$notification->read_at)
                                                        <span class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full bg-indigo-500"></span>
                                                    @endif
                                                    <span>{{ $message }}</span>
                                                </p>
                                                <p class="text-xs text-gray-500 mt-1 ml-4">
                                                    {{ $notification->created_at->diffForHumans() }}
                                                </p>
                                            @endif
                                        </div>
                                        @if(!$notification->read_at)
                                            <form action="{{ route('notifications.read', $notification->id) }}" method="POST" class="flex-shrink-0">
                                                @csrf
                                                <button type="submit" class="text-xs text-gray-500 hover:text-gray-700 ml-4">
                                                    {{ __('Mark as read') }}
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