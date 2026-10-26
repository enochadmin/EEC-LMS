<?php

namespace App\Notifications;

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\RetakeRequest;
use App\Models\TrainingModule;
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
        $item = $this->request->requestable;
        $itemType = class_basename($this->request->requestable_type);
        $itemTitle = $item->title ?? 'Unknown';

        return [
            'message' => "Your retake request for {$itemType}: {$itemTitle} has been {$this->request->status}",
            'request_id' => $this->request->id,
            'item_type' => $itemType,
            'item_id' => $this->request->requestable_id,
            'item_title' => $itemTitle,
            'status' => $this->request->status,
            'type' => 'retake_response',
            'url' => $this->destinationFor($item),
        ];
    }

    /**
     * A retake opens the item itself so the learner can start over straight
     * from the notification.
     */
    protected function destinationFor($item): ?string
    {
        if ($item instanceof Quiz) {
            return route('quizzes.take', $item);
        }

        if ($item instanceof Lesson && $item->module) {
            return route('modules.lessons.show', [$item->module, $item]);
        }

        if ($item instanceof TrainingModule) {
            return route('modules.show', $item);
        }

        return null;
    }
}