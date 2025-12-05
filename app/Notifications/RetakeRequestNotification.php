<?php

namespace App\Notifications;

use App\Models\RetakeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RetakeRequestNotification extends Notification
{
    use Queueable;

    protected $request;

    public function __construct(RetakeRequest $request)
    {
        $this->request = $request;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $itemType = class_basename($this->request->requestable_type);
        $itemTitle = $this->request->requestable->title ?? 'Unknown';
        
        return [
            'message' => "{$this->request->user->name} requested retake for {$itemType}: {$itemTitle}",
            'user_id' => $this->request->user_id,
            'request_id' => $this->request->id,
            'user_name' => $this->request->user->name,
            'item_type' => $itemType,
            'item_title' => $itemTitle,
            'type' => 'retake_request',
        ];
    }
}
