<?php

namespace App\Notifications;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CertificateIssuedNotification extends Notification
{
    use Queueable;

    protected $certificate;

    public function __construct(Certificate $certificate)
    {
        $this->certificate = $certificate;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "You have been issued a certificate for '{$this->certificate->module->title}'",
            'certificate_id' => $this->certificate->id,
            'module_id' => $this->certificate->training_module_id,
            'module_title' => $this->certificate->module->title,
            'type' => 'certificate_issued',
        ];
    }
}
