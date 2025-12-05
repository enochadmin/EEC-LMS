<?php

namespace App\Notifications;

use App\Models\AssignedModule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserEnrolledNotification extends Notification
{
    use Queueable;

    protected $assignment;

    public function __construct(AssignedModule $assignment)
    {
        $this->assignment = $assignment;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "{$this->assignment->user->name} has been enrolled in {$this->assignment->module->title}",
            'user_id' => $this->assignment->user_id,
            'module_id' => $this->assignment->training_module_id,
            'user_name' => $this->assignment->user->name,
            'module_title' => $this->assignment->module->title,
            'type' => 'enrollment',
        ];
    }
}
