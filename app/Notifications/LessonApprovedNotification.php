<?php

namespace App\Notifications;

use App\Models\LessonCompletion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LessonApprovedNotification extends Notification
{
    use Queueable;

    protected $completion;

    public function __construct(LessonCompletion $completion)
    {
        $this->completion = $completion;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "Your lesson completion for '{$this->completion->lesson->title}' has been approved",
            'lesson_id' => $this->completion->lesson_id,
            'module_id' => $this->completion->training_module_id,
            'lesson_title' => $this->completion->lesson->title,
            'module_title' => $this->completion->module->title,
            'type' => 'lesson_approved',
        ];
    }
}
