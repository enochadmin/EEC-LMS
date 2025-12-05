<?php

namespace App\Notifications;

use App\Models\RetakeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RetakeResponseNotification extends Notification
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
            'message' => "Your retake request for {$itemType}: {$itemTitle} has been {$this->request->status}",
            'request_id' => $this->request->id,
            'item_type' => $itemType,
            'item_title' => $itemTitle,
            'status' => $this->request->status,
            'type' => 'retake_response',
        ];
    }
}
