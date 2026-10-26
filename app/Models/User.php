<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'first_name',
        'last_name',
        'name',
        'email',
        'password',
        'department_id',
        'job_title',
        'phone',
        'office_location',
        'employment_type',
        'hire_date',
        'manager_id',
        'signature_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'hire_date' => 'date',
        ];
    }

    public function assignedModules(){ return $this->hasMany(AssignedModule::class); }
    public function department(){ return $this->belongsTo(Department::class); }
    public function manager(){ return $this->belongsTo(User::class, 'manager_id'); }
    public function directReports(){ return $this->hasMany(User::class, 'manager_id'); }
    public function advisedModules(){ return $this->belongsToMany(TrainingModule::class, 'module_advisors')->withPivot('assigned_by')->withTimestamps(); }
    public function progress(){ return $this->hasMany(ModuleProgress::class); }
    public function certificates(){ return $this->hasMany(Certificate::class); }
    public function knowledgeFiles(){ return $this->hasMany(KnowledgeFile::class); }
    public function createdModules(){ return $this->hasMany(TrainingModule::class, 'created_by'); }
    public function createdLessons(){ return $this->hasMany(Lesson::class, 'created_by'); }
    public function createdQuizzes(){ return $this->hasMany(Quiz::class, 'created_by'); }
    public function createdQuestions(){ return $this->hasMany(Question::class, 'created_by'); }
    public function createdQuestionOptions(){ return $this->hasMany(QuestionOption::class, 'created_by'); }
    public function createdUserAnswers(){ return $this->hasMany(UserAnswer::class, 'created_by'); }
    public function createdCertificates(){ return $this->hasMany(Certificate::class, 'created_by'); }
    public function createdKnowledgeFiles(){ return $this->hasMany(KnowledgeFile::class, 'created_by'); }
    
    /**
     * Get the user role mappings (custom table)
     */
    public function userHasRoles()
    {
        return $this->hasMany(UserHasRole::class);
    }

    /**
     * Generate the next incremental employee ID in the format E00001, E00002, ...
     */
    public static function generateEmployeeId(): string
    {
        // Get the highest existing numeric part from IDs that follow the E00001 pattern
        $lastId = static::whereNotNull('employee_id')
            ->where('employee_id', 'like', 'E%')
            ->orderBy('employee_id', 'desc')
            ->value('employee_id');

        $nextNumber = 1;

        if ($lastId) {
            $numericPart = (int) preg_replace('/\D/', '', $lastId);
            $nextNumber = $numericPart + 1;
        }

        return 'E' . str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }
}
