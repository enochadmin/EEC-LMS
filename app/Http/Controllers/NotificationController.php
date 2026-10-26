<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = auth()->user()->notifications()->latest();
        
        if ($search = $request->get('search')) {
            $query->where('data->message', 'like', "%{$search}%");
        }
        
        $notifications = $query->paginate(20)->appends(['search' => $search]);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Open a notification: mark it read, then send the reader to whatever the
     * notification is about (a lesson, an approval queue, a certificate...).
     */
    public function open($id)
    {
        $notification = auth()->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        $url = \App\Support\NotificationUrl::for($notification->data, auth()->user());

        return redirect()->to($url ?? route('notifications.index'));
    }

    public function markAsRead($id)
    {
        $notification = auth()->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return redirect()->back()->with('status', 'Notification marked as read');
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return redirect()->back()->with('status', 'All notifications marked as read');
    }

    public function getUnread()
    {
        $notifications = auth()->user()
            ->unreadNotifications()
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'notifications' => $notifications,
            'count' => auth()->user()->unreadNotifications()->count(),
        ]);
    }
}
