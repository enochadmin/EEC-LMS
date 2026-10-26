<?php

namespace App\Notifications;

use App\Models\TrainingModule;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ModuleCompletionApprovalRequestNotification extends Notification
{
    use Queueable;

    protected $module;
    protected $learner;

    public function __construct(TrainingModule $module, User $learner)
    {
        $this->module = $module;
        $this->learner = $learner;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "{$this->learner->name} has completed the module '{$this->module->title}'. Please review and approve.",
            'module_id' => $this->module->id,
            'learner_id' => $this->learner->id,
            'learner_name' => $this->learner->name,
            'module_title' => $this->module->title,
            'type' => 'module_completion_approval_request',
            'url' => route('module-approvals.index'),
        ];
    }
}


