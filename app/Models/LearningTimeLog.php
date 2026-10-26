<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningTimeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'training_module_id',
        'lesson_id',
        'seconds',
        'tracked_on',
    ];

    protected $casts = [
        'tracked_on' => 'date',
        'seconds' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function module()
    {
        return $this->belongsTo(TrainingModule::class, 'training_module_id');
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
