<?php

namespace App\Notifications;

use App\Models\QuizAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuizAttemptNotification extends Notification
{
    use Queueable;

    protected $attempt;

    public function __construct(QuizAttempt $attempt)
    {
        $this->attempt = $attempt;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $status = $this->attempt->status === 'passed' ? 'passed' : 'failed';
        
        return [
            'message' => "{$this->attempt->user->name} {$status} quiz '{$this->attempt->quiz->title}' with {$this->attempt->score}%",
            'user_id' => $this->attempt->user_id,
            'quiz_id' => $this->attempt->quiz_id,
            'user_name' => $this->attempt->user->name,
            'quiz_title' => $this->attempt->quiz->title,
            'score' => $this->attempt->score,
            'status' => $status,
            'type' => 'quiz_attempt',
            'url' => route('quizzes.results', $this->attempt->quiz_id),
        ];
    }
}
