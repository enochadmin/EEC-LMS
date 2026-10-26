<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_module_id',
        'title',
        'passing_score',
        'time_limit_minutes',
        'attempts_allowed',
        'cooldown_minutes',
        'shuffle_questions',
        'shuffle_options',
    ];

    protected $casts = [
        'passing_score' => 'integer',
        'time_limit_minutes' => 'integer',
        'attempts_allowed' => 'integer',
        'cooldown_minutes' => 'integer',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
    ];

    public function module()
    {
        return $this->belongsTo(TrainingModule::class, 'training_module_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * 0 means unlimited; kept as an accessor so views stay readable.
     */
    public function getHasTimeLimitAttribute(): bool
    {
        return ! empty($this->time_limit_minutes);
    }
}
