<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'category_id',
        'department_id',
        'description',
        'duration_minutes',
        'created_by',
        'status',
        'pdf_file_path',
        'video_file_path',
        'youtube_link',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }

    public function assignments()
    {
        return $this->hasMany(AssignedModule::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Instructors a learner can turn to for this course. Any number per module;
     * `assigned_by` records who put them there (self, or an instructor manager).
     */
    public function advisors()
    {
        return $this->belongsToMany(User::class, 'module_advisors')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    /**
     * The department a report should attribute this course to: its own
     * department, falling back to the creator's for older courses.
     */
    public function getReportingDepartmentAttribute(): ?Department
    {
        return $this->department ?? $this->creator?->department;
    }
}
