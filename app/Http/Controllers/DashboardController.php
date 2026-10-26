<?php

namespace App\Http\Controllers;

use App\Models\AssignedModule;
use App\Models\Certificate;
use App\Models\KnowledgeFile;
use App\Models\LearningTimeLog;
use App\Models\Lesson;
use App\Models\LessonCompletion;
use App\Models\ModuleProgress;
use App\Models\QuizAttempt;
use App\Models\Quiz;
use App\Models\TrainingModule;
use App\Models\User;
use App\Models\UserAnswer;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $isRealAdmin = $user->hasRole('admin');
        $isInstructor = $user->hasRole('instructor');

        $managedAssignments = collect();
        $managedModuleIds = collect();
        $managedLearnerIds = collect();

        if ($isInstructor && !$isRealAdmin) {
            $managedAssignments = AssignedModule::where('assigned_by', $user->id)
                ->get(['id', 'training_module_id', 'user_id']);
            $managedModuleIds = $managedAssignments->pluck('training_module_id')->unique()->values();
            $managedLearnerIds = $managedAssignments->pluck('user_id')->unique()->values();
        }
        $departmentData = ($departmentData ?? collect())->values();

        $learnerModuleIds = $user->assignedModules()->pluck('training_module_id')->unique();

        // Determine if we should show admin/instructor reports
        $showReports = $isRealAdmin || ($isInstructor && $managedAssignments->isNotEmpty());

        $managedModuleIdArray = $managedModuleIds->all();
        $managedLearnerIdArray = $managedLearnerIds->all();
        $learnerModuleIdArray = $learnerModuleIds->all();

        // Role-aware Statistics
        if ($isRealAdmin) {
            $stats = [
                'total_modules' => TrainingModule::count(),
                'published_modules' => TrainingModule::where('status', 'published')->count(),
                'total_lessons' => Lesson::count(),
                'total_quizzes' => Quiz::count(),
                'total_questions' => \App\Models\Question::count(),
                'total_knowledge_files' => KnowledgeFile::count(),
                'total_users' => User::count(),
                'total_assignments' => AssignedModule::count(),
                'total_certificates' => Certificate::count(),
            ];
        } elseif ($showReports && $isInstructor) {
            $stats = [
                'total_modules' => TrainingModule::whereIn('id', $managedModuleIdArray)->count(),
                'published_modules' => TrainingModule::whereIn('id', $managedModuleIdArray)->where('status', 'published')->count(),
                'total_lessons' => Lesson::whereIn('training_module_id', $managedModuleIdArray)->count(),
                'total_quizzes' => Quiz::whereIn('training_module_id', $managedModuleIdArray)->count(),
                'total_questions' => \App\Models\Question::whereHas('quiz', function ($query) use ($managedModuleIdArray) {
                    $query->whereIn('training_module_id', $managedModuleIdArray);
                })->count(),
                'total_knowledge_files' => KnowledgeFile::where('uploaded_by', $user->id)->count(),
                'total_users' => count($managedLearnerIdArray),
                'total_assignments' => $managedAssignments->count(),
                'total_certificates' => Certificate::whereIn('user_id', $managedLearnerIdArray)
                    ->whereIn('training_module_id', $managedModuleIdArray)
                    ->count(),
            ];
        } else {
            $stats = [
                'total_modules' => count($learnerModuleIdArray),
                'published_modules' => TrainingModule::whereIn('id', $learnerModuleIdArray)->where('status', 'published')->count(),
                'total_lessons' => Lesson::whereIn('training_module_id', $learnerModuleIdArray)->count(),
                'total_quizzes' => Quiz::whereIn('training_module_id', $learnerModuleIdArray)->count(),
                'total_questions' => \App\Models\Question::whereHas('quiz', function ($query) use ($learnerModuleIdArray) {
                    $query->whereIn('training_module_id', $learnerModuleIdArray);
                })->count(),
                'total_knowledge_files' => KnowledgeFile::where('uploaded_by', $user->id)->count(),
                'total_users' => 1,
                'total_assignments' => $user->assignedModules()->count(),
                'total_certificates' => $user->certificates()->count(),
            ];
        }

        // User-specific statistics (for learners)
        $userStats = [];
        // Show learner stats if they are NOT showing reports (pure learner) 
        // OR if they are an instructor who also wants to see their own progress?
        // For now, let's keep the existing logic: if not admin/instructor, show user stats.
        // But wait, an instructor might want to see their own learning too.
        // The previous logic was: if (!$isAdmin) { ... }
        // Let's stick to: if (!$showReports) { ... } or maybe separate them.
        // The user said "if he is not enrolled... he dont need to see reports".
        // This implies if he IS enrolled (as learner?), he sees learner stats?
        // Let's keep it simple: If showReports is false, show userStats.
        
        if (!$showReports) {
            $userStats = [
                'my_assignments' => $user->assignedModules()->count(),
                'completed_modules' => $user->progress()->where('percentage', 100)->count(),
                'in_progress_modules' => $user->progress()->where('percentage', '<', 100)->where('percentage', '>', 0)->count(),
                'my_certificates' => $user->certificates()->count(),
                'quizzes_taken' => UserAnswer::where('user_id', $user->id)
                    ->distinct('question_id')
                    ->join('questions', 'user_answers.question_id', '=', 'questions.id')
                    ->distinct('questions.quiz_id')
                    ->count(),
            ];
        }

        // Admin/Instructor Statistics
        $adminStats = [];
        if ($showReports) {
            if ($isRealAdmin) {
                $adminStats['users_assigned_to_modules'] = AssignedModule::distinct('user_id')->count('user_id');

                $adminStats['users_per_module'] = TrainingModule::withCount('assignments')
                    ->get()
                    ->map(function ($module) {
                        return [
                            'module_id' => $module->id,
                            'module' => $module->title,
                            'users_count' => $module->assignments_count,
                        ];
                    })
                    ->sortByDesc('users_count')
                    ->take(10)
                    ->values();

                $adminStats['certified_users'] = Certificate::distinct('user_id')->count('user_id');
                $adminStats['total_questions_answered'] = UserAnswer::count();

                $adminStats['quizzes_completed'] = DB::table('user_answers')
                    ->join('questions', 'user_answers.question_id', '=', 'questions.id')
                    ->join('quizzes', 'questions.quiz_id', '=', 'quizzes.id')
                    ->select('quizzes.id', 'quizzes.title', DB::raw('COUNT(DISTINCT user_answers.user_id) as completed_count'))
                    ->groupBy('quizzes.id', 'quizzes.title')
                    ->orderByDesc('completed_count')
                    ->take(10)
                    ->get();

                $adminStats['module_completions'] = ModuleProgress::query()
                    ->enrolledOnly()
                    ->completed()
                    ->select('training_module_id', DB::raw('COUNT(*) as completion_count'))
                    ->groupBy('training_module_id')
                    ->get()
                    ->map(function ($progress) {
                        $module = TrainingModule::find($progress->training_module_id);
                        return [
                            'module_id' => $progress->training_module_id,
                            'module' => $module->title ?? 'Unknown',
                            'completions' => $progress->completion_count,
                        ];
                    })
                    ->sortByDesc('completions')
                    ->take(10)
                    ->values();

                $adminStats['recent_assignments'] = AssignedModule::with(['user', 'module'])
                    ->latest()
                    ->take(10)
                    ->get();

                $adminStats['recent_certificates'] = Certificate::with(['user', 'module'])
                    ->latest()
                    ->take(10)
                    ->get();

                $adminStats['users_by_role'] = DB::table('model_has_roles')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->select('roles.name as role', DB::raw('COUNT(*) as count'))
                    ->groupBy('roles.name')
                    ->get()
                    ->map(function ($stat) {
                        return [
                            'role' => $stat->role,
                            'count' => $stat->count,
                        ];
                    })
                    ->values();

                $adminStats['module_progress_overview'] = ModuleProgress::query()
                    ->enrolledOnly()
                    ->select(
                        DB::raw('AVG(percentage) as avg_progress'),
                        DB::raw('COUNT(CASE WHEN percentage = 100 THEN 1 END) as completed'),
                        DB::raw('COUNT(CASE WHEN percentage > 0 AND percentage < 100 THEN 1 END) as in_progress'),
                        DB::raw('COUNT(CASE WHEN percentage = 0 THEN 1 END) as not_started')
                    )
                    ->first();

                $adminStats['top_modules'] = TrainingModule::withCount(['assignments', 'lessons', 'quizzes'])
                    ->with(['category'])
                    ->get()
                    ->map(function ($module) {
                        $completions = ModuleProgress::where('training_module_id', $module->id)
                            ->enrolledOnly()
                            ->completed()
                            ->count();
                        $assignments = $module->assignments_count;
                        $completion_rate = $assignments > 0 ? min(100, ($completions / $assignments) * 100) : 0;

                        return [
                            'module_id' => $module->id,
                            'title' => $module->title,
                            'category' => $module->category->name ?? 'Uncategorized',
                            'assignments' => $assignments,
                            'completions' => $completions,
                            'completion_rate' => round($completion_rate, 1),
                            'lessons_count' => $module->lessons_count,
                            'quizzes_count' => $module->quizzes_count,
                            'status' => $module->status,
                        ];
                    })
                    ->sortByDesc('completion_rate')
                    ->take(10)
                    ->values();
            } else {
                $adminStats['users_assigned_to_modules'] = count($managedLearnerIdArray);

                $adminStats['users_per_module'] = TrainingModule::whereIn('id', $managedModuleIdArray)
                    ->withCount([
                        'assignments as instructor_assignments_count' => function ($query) use ($user) {
                            $query->where('assigned_by', $user->id);
                        },
                    ])
                    ->get()
                    ->map(function ($module) {
                        return [
                            'module_id' => $module->id,
                            'module' => $module->title,
                            'users_count' => $module->instructor_assignments_count,
                        ];
                    })
                    ->sortByDesc('users_count')
                    ->take(10)
                    ->values();

                $adminStats['certified_users'] = Certificate::whereIn('user_id', $managedLearnerIdArray)
                    ->whereIn('training_module_id', $managedModuleIdArray)
                    ->distinct('user_id')
                    ->count('user_id');

                $adminStats['total_questions_answered'] = UserAnswer::whereIn('user_id', $managedLearnerIdArray)->count();

                $adminStats['quizzes_completed'] = DB::table('user_answers')
                    ->join('questions', 'user_answers.question_id', '=', 'questions.id')
                    ->join('quizzes', 'questions.quiz_id', '=', 'quizzes.id')
                    ->whereIn('user_answers.user_id', $managedLearnerIdArray)
                    ->whereIn('quizzes.training_module_id', $managedModuleIdArray)
                    ->select('quizzes.id', 'quizzes.title', DB::raw('COUNT(DISTINCT user_answers.user_id) as completed_count'))
                    ->groupBy('quizzes.id', 'quizzes.title')
                    ->orderByDesc('completed_count')
                    ->take(10)
                    ->get();

                $adminStats['module_completions'] = ModuleProgress::query()
                    ->enrolledOnly()
                    ->completed()
                    ->whereIn('training_module_id', $managedModuleIdArray)
                    ->whereIn('user_id', $managedLearnerIdArray)
                    ->select('training_module_id', DB::raw('COUNT(*) as completion_count'))
                    ->groupBy('training_module_id')
                    ->get()
                    ->map(function ($progress) {
                        $module = TrainingModule::find($progress->training_module_id);
                        return [
                            'module_id' => $progress->training_module_id,
                            'module' => $module->title ?? 'Unknown',
                            'completions' => $progress->completion_count,
                        ];
                    })
                    ->sortByDesc('completions')
                    ->take(10)
                    ->values();

                $adminStats['recent_assignments'] = AssignedModule::with(['user', 'module'])
                    ->where('assigned_by', $user->id)
                    ->latest()
                    ->take(10)
                    ->get();

                $adminStats['recent_certificates'] = Certificate::with(['user', 'module'])
                    ->whereIn('user_id', $managedLearnerIdArray)
                    ->whereIn('training_module_id', $managedModuleIdArray)
                    ->latest()
                    ->take(10)
                    ->get();

                $adminStats['users_by_role'] = DB::table('model_has_roles')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->whereIn('model_has_roles.model_id', $managedLearnerIdArray)
                    ->select('roles.name as role', DB::raw('COUNT(*) as count'))
                    ->groupBy('roles.name')
                    ->get()
                    ->map(function ($stat) {
                        return [
                            'role' => $stat->role,
                            'count' => $stat->count,
                        ];
                    })
                    ->values();

                $adminStats['module_progress_overview'] = ModuleProgress::whereIn('training_module_id', $managedModuleIdArray)
                    ->whereIn('user_id', $managedLearnerIdArray)
                    ->select(
                        DB::raw('AVG(percentage) as avg_progress'),
                        DB::raw('COUNT(CASE WHEN percentage = 100 THEN 1 END) as completed'),
                        DB::raw('COUNT(CASE WHEN percentage > 0 AND percentage < 100 THEN 1 END) as in_progress'),
                        DB::raw('COUNT(CASE WHEN percentage = 0 THEN 1 END) as not_started')
                    )
                    ->first();

                $adminStats['top_modules'] = TrainingModule::whereIn('id', $managedModuleIdArray)
                    ->with(['category'])
                    ->withCount([
                        'lessons',
                        'quizzes',
                        'assignments as instructor_assignments_count' => function ($query) use ($user) {
                            $query->where('assigned_by', $user->id);
                        },
                    ])
                    ->get()
                    ->map(function ($module) use ($managedLearnerIdArray) {
                        $completions = ModuleProgress::where('training_module_id', $module->id)
                            ->whereIn('user_id', $managedLearnerIdArray)
                            ->enrolledOnly()
                            ->completed()
                            ->count();
                        $assignments = $module->instructor_assignments_count;
                        $completion_rate = $assignments > 0 ? min(100, ($completions / $assignments) * 100) : 0;

                        return [
                            'module_id' => $module->id,
                            'title' => $module->title,
                            'category' => $module->category->name ?? 'Uncategorized',
                            'assignments' => $assignments,
                            'completions' => $completions,
                            'completion_rate' => round($completion_rate, 1),
                            'lessons_count' => $module->lessons_count,
                            'quizzes_count' => $module->quizzes_count,
                            'status' => $module->status,
                        ];
                    })
                    ->sortByDesc('completion_rate')
                    ->take(10)
                    ->values();
            }
        }

        // Recent modules tailored per role
        if ($isRealAdmin) {
            $recentModules = TrainingModule::with(['category', 'creator'])
                ->withCount(['lessons', 'quizzes', 'assignments'])
                ->latest()
                ->take(5)
                ->get();
        } elseif ($showReports && $isInstructor) {
            $recentModules = TrainingModule::whereIn('id', $managedModuleIdArray)
                ->with(['category', 'creator'])
                ->withCount([
                    'lessons',
                    'quizzes',
                    'assignments as assignments_count' => function ($query) use ($user) {
                        $query->where('assigned_by', $user->id);
                    },
                ])
                ->latest()
                ->take(5)
                ->get();
        } else {
            $recentModules = TrainingModule::whereIn('id', $learnerModuleIdArray)
                ->with(['category', 'creator'])
                ->withCount(['lessons', 'quizzes'])
                ->latest()
                ->take(5)
                ->get();
        }

        // Recent knowledge files
        if ($isRealAdmin) {
            $recentKnowledge = KnowledgeFile::with('uploader')
                ->latest()
                ->take(5)
                ->get();
        } else {
            $recentKnowledge = KnowledgeFile::with('uploader')
                ->where('uploaded_by', $user->id)
                ->latest()
                ->take(5)
                ->get();
        }

        // User's assignments
        $assignments = $user->assignedModules()
            ->with('module')
            ->latest()
            ->take(5)
            ->get();

        // User's certificates
        $certificates = $user->certificates()
            ->with('module')
            ->latest()
            ->take(5)
            ->get();

        // User's progress
        $userProgress = $user->progress()
            ->with('module')
            ->orderBy('percentage', 'desc')
            ->take(5)
            ->get();
        // Chart Data (Quiz Attempts)
        if ($isRealAdmin) {
            $relevantQuizIds = Quiz::pluck('id');
            $attempts = \App\Models\QuizAttempt::whereHas('user', function ($query) {
                $query->role('learner');
            })->whereNotNull('submitted_at')->whereIn('quiz_id', $relevantQuizIds)->get();
        } elseif ($showReports && $isInstructor) {
            $relevantQuizIds = Quiz::whereIn('training_module_id', $managedModuleIdArray)->pluck('id');
            $attempts = \App\Models\QuizAttempt::whereIn('user_id', $managedLearnerIdArray)
                ->whereNotNull('submitted_at')
                ->whereIn('quiz_id', $relevantQuizIds)
                ->get();
        } else {
            $relevantQuizIds = Quiz::whereIn('training_module_id', $learnerModuleIdArray)->pluck('id');
            $attempts = \App\Models\QuizAttempt::where('user_id', $user->id)
                ->whereNotNull('submitted_at')
                ->whereIn('quiz_id', $relevantQuizIds)
                ->get();
        }

        $passedCount = $attempts->where('status', 'passed')->count();
        $failedCount = $attempts->where('status', 'failed')->count();
        $totalAttempts = $attempts->count();
        
        $takenQuizIds = $attempts->pluck('quiz_id')->unique();
        $notStartedCount = max($relevantQuizIds->count() - $takenQuizIds->count(), 0);

        $chartData = [
            'taken' => $totalAttempts,
            'passed' => $passedCount,
            'failed' => $failedCount,
            'not_started' => $notStartedCount, 
        ];

        // Enrollment Chart Data (for bar chart)
        // Show modules, categories, and lessons enrolled by users
        if ($isRealAdmin) {
            $enrollmentData = [
                'modules' => AssignedModule::whereHas('user', function ($q) {
                    $q->role('learner');
                })->distinct('training_module_id')->count('training_module_id'),
                'categories' => \App\Models\Category::whereHas('modules.assignments.user', function ($q) {
                    $q->role('learner');
                })->count(),
                'lessons' => \App\Models\LessonCompletion::whereHas('user', function ($q) {
                    $q->role('learner');
                })->distinct('lesson_id')->count('lesson_id'),
            ];
        } elseif ($showReports && $isInstructor) {
            $enrollmentData = [
                'modules' => count($managedModuleIdArray),
                'categories' => \App\Models\Category::whereHas('modules', function ($q) use ($managedModuleIdArray) {
                    $q->whereIn('id', $managedModuleIdArray);
                })->count(),
                'lessons' => \App\Models\LessonCompletion::whereIn('user_id', $managedLearnerIdArray)
                    ->whereIn('training_module_id', $managedModuleIdArray)
                    ->where('status', 'approved')
                    ->distinct('lesson_id')
                    ->count('lesson_id'),
            ];
        } else {
            $enrollmentData = [
                'modules' => $user->assignedModules()->count(),
                'categories' => \App\Models\Category::whereHas('modules.assignments', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->count(),
                'lessons' => \App\Models\LessonCompletion::where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->distinct('lesson_id')
                    ->count('lesson_id'),
            ];
        }

        // Department Chart Data (users per department)
        if ($isRealAdmin) {
            $departmentData = \App\Models\Department::withCount(['users' => function ($q) {
                $q->role('learner');
            }])->get()->map(function ($dept) {
                return [
                    'name' => $dept->name,
                    'count' => $dept->users_count,
                ];
            });
        } elseif ($showReports && $isInstructor) {
            if (empty($managedLearnerIdArray)) {
                $departmentData = collect();
            } else {
                $departmentData = \App\Models\Department::whereHas('users', function ($q) use ($managedLearnerIdArray) {
                    $q->whereIn('users.id', $managedLearnerIdArray);
                })->withCount(['users as learners_count' => function ($q) use ($managedLearnerIdArray) {
                    $q->whereIn('users.id', $managedLearnerIdArray);
                }])->get()->map(function ($dept) {
                    return [
                        'name' => $dept->name,
                        'count' => $dept->learners_count,
                    ];
                });
            }
        } else {
            $departmentData = collect([
                [
                    'name' => $user->department->name ?? 'Unassigned',
                    'count' => 1,
                ],
            ]);
        }

        // Top learners by quiz score (month & quarter)
        $topLearnersMonth = collect();
        $topLearnersQuarter = collect();

        if ($isRealAdmin || ($showReports && $isInstructor)) {
            $baseQuizAttemptQuery = QuizAttempt::with(['user', 'quiz.module'])->whereNotNull('submitted_at');

            if ($isRealAdmin) {
                $learnerFilter = function ($q) {
                    $q->role('learner');
                };
            } else {
                $managedLearnerIdArray = $managedLearnerIds->all();
                $managedModuleIdArray = $managedModuleIds->all();

                $baseQuizAttemptQuery = $baseQuizAttemptQuery
                    ->whereIn('user_id', $managedLearnerIdArray)
                    ->whereHas('quiz', function ($q) use ($managedModuleIdArray) {
                        $q->whereIn('training_module_id', $managedModuleIdArray);
                    });

                $learnerFilter = null;
            }

            // Helper closure to get top learners for a given period
            $computeTopLearners = function (string $period) use ($baseQuizAttemptQuery, $learnerFilter) {
                $startDate = match ($period) {
                    'month' => now()->subMonth(),
                    'quarter' => now()->subMonths(3),
                    default => now()->subMonth(),
                };

                $query = clone $baseQuizAttemptQuery;

                $query->where('created_at', '>=', $startDate);

                if ($learnerFilter) {
                    $query->whereHas('user', $learnerFilter);
                }

                $attempts = $query->get();

                // Group by user and take best score
                $grouped = $attempts->groupBy('user_id')->map(function ($userAttempts) {
                    $best = $userAttempts->sortByDesc('score')->first();
                    return [
                        'user' => $best->user,
                        'best_score' => $best->score,
                        'quiz_title' => $best->quiz->title ?? null,
                        'module_title' => $best->quiz->module->title ?? null,
                        'attempted_at' => $best->created_at,
                    ];
                })->values()->sortByDesc('best_score')->take(10)->values();

                return $grouped;
            };

            $topLearnersMonth = $computeTopLearners('month');
            $topLearnersQuarter = $computeTopLearners('quarter');
        }

        // ---- Continue learning ------------------------------------------------
        // Resume point: the incomplete module the learner touched most recently,
        // and the first lesson still waiting for them.
        $resume = null;

        if ($learnerModuleIds->isNotEmpty()) {
            $incomplete = ModuleProgress::where('user_id', $user->id)
                ->whereIn('training_module_id', $learnerModuleIdArray)
                ->where('percentage', '<', 100)
                ->get(['training_module_id', 'percentage', 'last_accessed_at']);

            if ($incomplete->isNotEmpty()) {
                $incompleteIds = $incomplete->pluck('training_module_id')->all();

                $lastHeartbeat = LearningTimeLog::where('user_id', $user->id)
                    ->whereIn('training_module_id', $incompleteIds)
                    ->orderByDesc('updated_at')
                    ->first();

                $chosen = $incomplete->sortByDesc(function ($row) use ($lastHeartbeat) {
                    $heartbeat = ($lastHeartbeat && (int) $lastHeartbeat->training_module_id === (int) $row->training_module_id)
                        ? $lastHeartbeat->updated_at?->timestamp
                        : null;

                    $access = $row->last_accessed_at?->timestamp;

                    return max($heartbeat ?? 0, $access ?? 0);
                })->first();

                $resumeModule = TrainingModule::find($chosen->training_module_id);

                if ($resumeModule) {
                    $moduleLessons = $resumeModule->lessons()->get(['id', 'title', 'estimated_minutes']);

                    $approvedLessonIds = $moduleLessons->isEmpty()
                        ? []
                        : LessonCompletion::where('user_id', $user->id)
                            ->whereIn('lesson_id', $moduleLessons->pluck('id'))
                            ->where('status', 'approved')
                            ->pluck('lesson_id')
                            ->all();

                    $remaining = $moduleLessons->reject(fn ($lesson) => in_array($lesson->id, $approvedLessonIds, true));

                    $resume = [
                        'module' => $resumeModule,
                        'lesson' => $remaining->first(),
                        'percentage' => round((float) $chosen->percentage, 1),
                        'minutes_left' => (int) $remaining->sum('estimated_minutes'),
                        'lessons_left' => $remaining->count(),
                    ];
                }
            }
        }

        return view('dashboard', [
            'stats' => $stats,
            'userStats' => $userStats,
            'adminStats' => $adminStats,
            'chartData' => $chartData,
            'enrollmentData' => $enrollmentData,
            'departmentData' => $departmentData,
            'recentModules' => $recentModules,
            'recentKnowledge' => $recentKnowledge,
            'assignments' => $assignments,
            'certificates' => $certificates,
            'userProgress' => $userProgress,
            'showReports' => $showReports,
            'topLearnersMonth' => $topLearnersMonth,
            'topLearnersQuarter' => $topLearnersQuarter,
            'resume' => $resume,
        ]);
    }
}
