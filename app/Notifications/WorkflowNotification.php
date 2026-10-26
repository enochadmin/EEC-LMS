<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Generic in-app notification for workflow events.
 *
 * Uses the same payload shape as the dedicated notification classes
 * (`type`, `message`, optional `url`) so the bell, the notifications page and
 * future activity logging all read it the same way.
 */
class WorkflowNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $type,
        public string $message,
        public ?string $url = null,
        public array $meta = [],
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return array_merge([
            'type' => $this->type,
            'message' => $this->message,
            'url' => $this->url,
        ], $this->meta);
    }
}
