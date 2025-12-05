<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleProgress extends Model
{
    use HasFactory;

    protected $table = 'module_progress';

    protected $fillable = [
        'training_module_id',
        'user_id',
        'completed_lessons',
        'total_lessons',
        'lesson_percentage',
        'quiz_percentage',
        'percentage',
        'last_accessed_at',
    ];

    protected $casts = [
        'last_accessed_at' => 'datetime',
        'percentage' => 'float',
        'lesson_percentage' => 'float',
        'quiz_percentage' => 'float',
    ];

    public function module()
    {
        return $this->belongsTo(TrainingModule::class, 'training_module_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
