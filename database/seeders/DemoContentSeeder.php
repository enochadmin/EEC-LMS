<?php

namespace Database\Seeders;

use App\Models\AssignedModule;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\KnowledgeFile;
use App\Models\Lesson;
use App\Models\ModuleProgress;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\TrainingModule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch users that were already created by RolePermissionSeeder
        $admin = User::where('email', 'admin@example.com')->first();
        $instructor = User::where('email', 'instructor@example.com')->first();
        $learner = User::where('email', 'learner@example.com')->first();

        // If users don't exist, create them (fallback)
        if (!$admin) {
            $admin = User::create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
            ]);
            $admin->assignRole('admin');
        }

        if (!$instructor) {
            $instructor = User::create([
                'name' => 'Instructor User',
                'email' => 'instructor@example.com',
                'password' => Hash::make('password'),
            ]);
            $instructor->assignRole('instructor');
        }

        if (!$learner) {
            $learner = User::create([
                'name' => 'Learner User',
                'email' => 'learner@example.com',
                'password' => Hash::make('password'),
            ]);
            $learner->assignRole('learner');
        }

        $category = Category::firstOrCreate(
            ['name' => 'Compliance'],
            ['description' => 'Policies and regulatory learning modules.']
        );

        $module = TrainingModule::firstOrCreate(
            ['title' => 'Workplace Safety Basics'],
            [
                'category_id' => $category->id,
                'description' => 'Covers the fundamentals of staying safe at work.',
                'duration_minutes' => 45,
                'created_by' => $admin->id,
                'status' => 'published',
            ]
        );

        collect([
            [
                'title' => 'Emergency Procedures',
                'content' => 'Identify exits, alarm types, and assembly points.',
                'order' => 1,
            ],
            [
                'title' => 'Personal Protective Equipment',
                'content' => 'Understand when and how to use PPE.',
                'order' => 2,
            ],
        ])->each(function ($lesson) use ($module) {
            Lesson::firstOrCreate(
                [
                    'training_module_id' => $module->id,
                    'title' => $lesson['title'],
                ],
                [
                    'content' => $lesson['content'],
                    'order' => $lesson['order'],
                    'estimated_minutes' => 15,
                ]
            );
        });

        $quiz = Quiz::firstOrCreate(
            [
                'training_module_id' => $module->id,
                'title' => 'Safety Knowledge Check',
            ],
            [
                'passing_score' => 70,
            ]
        );

        $question = Question::firstOrCreate(
            [
                'quiz_id' => $quiz->id,
                'question' => 'What is the first action when you hear a fire alarm?',
            ],
            [
                'points' => 1,
            ]
        );

        QuestionOption::firstOrCreate(
            [
                'question_id' => $question->id,
                'option_text' => 'Gather your belongings',
            ],
            ['is_correct' => false]
        );

        QuestionOption::firstOrCreate(
            [
                'question_id' => $question->id,
                'option_text' => 'Evacuate using the nearest exit',
            ],
            ['is_correct' => true]
        );

        AssignedModule::firstOrCreate(
            [
                'training_module_id' => $module->id,
                'user_id' => $learner->id,
            ],
            [
                'assigned_by' => $instructor->id,
                'due_date' => now()->addWeek(),
                'status' => 'in_progress',
            ]
        );

        ModuleProgress::firstOrCreate(
            [
                'training_module_id' => $module->id,
                'user_id' => $learner->id,
            ],
            [
                'completed_lessons' => 1,
                'total_lessons' => 2,
                'percentage' => 50,
                'last_accessed_at' => now()->subDay(),
            ]
        );

        $certificatePath = 'certificates/sample-certificate.pdf';
        if (!Storage::disk('local')->exists($certificatePath)) {
            Storage::disk('local')->put($certificatePath, 'Sample certificate content');
        }

        Certificate::firstOrCreate(
            [
                'user_id' => $learner->id,
                'training_module_id' => $module->id,
            ],
            [
                'certificate_number' => Str::upper(Str::random(10)),
                'file_path' => $certificatePath,
                'issued_at' => now()->subDay(),
                'valid_until' => now()->addYear(),
            ]
        );

        $knowledgePath = 'knowledge/sample-guide.txt';
        if (!Storage::disk('local')->exists($knowledgePath)) {
            Storage::disk('local')->put($knowledgePath, 'Process checklist for supervisors.');
        }

        KnowledgeFile::firstOrCreate(
            [
                'title' => 'Supervisor Checklist',
            ],
            [
                'description' => 'Quick reference for onboarding new hires.',
                'file_path' => $knowledgePath,
                'uploaded_by' => $instructor->id,
            ]
        );
    }

}


