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
        'completed_quizzes',
        'total_quizzes',
        'lesson_percentage',
        'quiz_percentage',
        'percentage',
        'last_accessed_at',
    ];

    protected $casts = [
        'last_accessed_at' => 'datetime',
        'computed_at' => 'datetime',
        'percentage' => 'float',
        'lesson_percentage' => 'float',
        'quiz_percentage' => 'float',
    ];

    /**
     * Only progress rows that belong to a real enrolment (user is assigned to
     * that module). Staff who open a module in preview can end up with a
     * progress row of their own; counting those inflates completion rates
     * above 100%. Every aggregate must use this scope.
     */
    public function scopeEnrolledOnly($query)
    {
        return $query->whereExists(function ($sub) {
            $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                ->from('assigned_modules')
                ->whereColumn('assigned_modules.user_id', 'module_progress.user_id')
                ->whereColumn('assigned_modules.training_module_id', 'module_progress.training_module_id');
        });
    }

    /**
     * Completed means "all tasks done" — the cap is 100, never above it.
     */
    public function scopeCompleted($query)
    {
        return $query->where('percentage', '>=', 100);
    }

    public function module()

    {
        return $this->belongsTo(TrainingModule::class, 'training_module_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
