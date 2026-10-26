<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CertificateVerificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KnowledgeFileController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\TrainingModuleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DiscoverController;
use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\ModuleCompletionApprovalController;
use App\Http\Controllers\EnrollmentApprovalController;
use App\Http\Controllers\LearningTimeController;
use App\Http\Controllers\ModuleTemplateController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;



Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('welcome');
})->name('home');

// Public certificate verification — no login required (linked from the PDF).
Route::get('/verify/{number}', [CertificateVerificationController::class, 'show'])
    ->name('certificates.verify');

// Route::get('/register', [RegisteredUserController::class, 'create'])
//     ->middleware('guest')
//     ->name('register');

// Route::post('/register', [RegisteredUserController::class, 'store'])
//     ->middleware('guest');

// // Login Routes
// Route::get('/login', [AuthenticatedSessionController::class, 'create'])
//     ->middleware('guest')
//     ->name('login');

// Route::post('/login', [AuthenticatedSessionController::class, 'store'])
//     ->middleware('guest');

// // Password Reset (Forgot Password)
// Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])
//     ->middleware('guest')
//     ->name('password.request');

// Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
//     ->middleware('guest')
//     ->name('password.email');

// // Reset Password Form
// Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
//     ->middleware('guest')
//     ->name('password.reset');

// Route::post('/reset-password', [NewPasswordController::class, 'store'])
//     ->middleware('guest')
//     ->name('password.store');

// // Email Verification Prompt
// Route::get('/verify-email', EmailVerificationPromptController::class)
//     ->middleware('auth')
//     ->name('verification.notice');

// // Email Verification Handler
// Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
//     ->middleware(['auth', 'signed', 'throttle:6,1'])
//     ->name('verification.verify');

// // Resend Email Verification Link
// Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
//     ->middleware(['auth', 'throttle:6,1'])
//     ->name('verification.send');

// // Confirm Password (for sensitive actions)
// Route::get('/confirm-password', [ConfirmablePasswordController::class, 'show'])
//     ->middleware('auth')
//     ->name('password.confirm');

// Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store'])
//     ->middleware('auth');

// // Update Password (inside user profile)
// Route::put('/password', [PasswordController::class, 'update'])
//     ->middleware('auth')
//     ->name('password.update');

// // Logout Route
// Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
//     ->middleware('auth')
//     ->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Monitoring reports (admins see everything, instructors see their own modules)
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    // Learning-time heartbeat used by lesson/module pages
    Route::post('api/learning-time', [LearningTimeController::class, 'store'])->name('api.learning-time');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Training Modules
    // The two creation paths are declared before the resource route so
    // "create/template" can never be mistaken for a module id.
    Route::get('modules/create/manual', [TrainingModuleController::class, 'createManual'])->name('modules.create.manual');
    Route::get('modules/create/template', [ModuleTemplateController::class, 'structure'])->name('modules.create.template');
    Route::post('modules/create/template/structure', [ModuleTemplateController::class, 'storeStructure'])->name('modules.create.template.structure');
    Route::get('modules/create/template/content', [ModuleTemplateController::class, 'content'])->name('modules.create.template.content');
    Route::get('modules/create/template/download', [ModuleTemplateController::class, 'downloadTemplate'])->name('modules.create.template.download');
    Route::post('modules/create/template', [ModuleTemplateController::class, 'store'])->name('modules.create.template.store');
    Route::get('discover', [DiscoverController::class, 'index'])->name('discover.index');

    Route::resource('modules', TrainingModuleController::class);
    Route::post('modules/{module}/publish', [TrainingModuleController::class, 'publish'])
        ->middleware('permission:manage modules')
        ->name('modules.publish');
    Route::get('modules/{module}/assign', [AssignmentController::class, 'index'])
        ->middleware('permission:assign modules')
        ->name('modules.assign.index');
    Route::post('modules/{module}/assign', [AssignmentController::class, 'assign'])
        ->middleware('permission:assign modules')
        ->name('modules.assign');

    // Lessons
    Route::get('lessons', [LessonController::class, 'index'])->name('lessons.index');
    Route::get('modules/{module}/lessons/{lesson}', [LessonController::class, 'show'])->name('modules.lessons.show');
    Route::post('modules/{module}/lessons/{lesson}/complete', [LessonController::class, 'complete'])->name('modules.lessons.complete');
    Route::resource('lessons', LessonController::class)->except(['show', 'index']);

    // Quizzes
    Route::resource('quizzes', QuizController::class)->except(['show']);
    // Questions CRUD for instructors/admins
    Route::post('quizzes/{quiz}/questions', [App\Http\Controllers\QuestionController::class, 'store'])->name('quizzes.questions.store');
    Route::delete('questions/{question}', [App\Http\Controllers\QuestionController::class, 'destroy'])->name('questions.destroy');
    // Grade a user's short answer
    Route::post('answers/{answer}/grade', [App\Http\Controllers\QuestionController::class, 'gradeAnswer'])->name('answers.grade');
    Route::get('quizzes/{quiz}/take', [QuizController::class, 'take'])->name('quizzes.take');
    Route::post('quizzes/{quiz}/submit', [QuizController::class, 'submit'])->name('quizzes.submit');
    Route::get('quizzes/{quiz}/results', [QuizController::class, 'results'])->name('quizzes.results');

    // Certificates
    Route::get('certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::get('certificates/{certificate}/view', [CertificateController::class, 'view'])->name('certificates.view');
    Route::get('certificates/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');
    
    // Admin Certificate Management
    Route::get('admin/certificates', [CertificateController::class, 'adminIndex'])
        ->middleware('permission:issue certificates')
        ->name('admin.certificates.index');
    Route::post('admin/certificates/issue/{user}/{module}', [CertificateController::class, 'issue'])
        ->middleware('permission:issue certificates')
        ->name('admin.certificates.issue');
    Route::delete('admin/certificates/{certificate}', [CertificateController::class, 'destroy'])
        ->middleware('permission:issue certificates')
        ->name('admin.certificates.destroy');

    // Admin-only system tooling
    Route::middleware('role:admin')->group(function () {
        Route::resource('roles', RoleController::class)->except(['show']);
        Route::get('admin/activity', [ActivityLogController::class, 'index'])->name('admin.activity.index');
        Route::get('admin/branding', [BrandingController::class, 'index'])->name('admin.branding.index');
        Route::post('admin/branding', [BrandingController::class, 'update'])->name('admin.branding.update');
        Route::post('admin/branding/signature/{user}', [BrandingController::class, 'signature'])->name('admin.branding.signature');
        Route::delete('admin/branding/signature/{user}', [BrandingController::class, 'removeSignature'])->name('admin.branding.signature.destroy');
    });
    // Knowledge Files
    Route::resource('knowledge', KnowledgeFileController::class)->except(['show']);
    Route::get('knowledge/{knowledge}/download', [KnowledgeFileController::class, 'download'])->name('knowledge.download');

    // User Management (Admin only)
    // Template/import routes must be registered BEFORE the {user} resource route,
    // otherwise GET /users/template is bound as a user id and 404s.
    Route::get('users/template/{extension?}', [UserController::class, 'downloadTemplate'])
        ->where('extension', 'csv|xls|xlsx')
        ->name('users.template')
        ->middleware('permission:manage users');
    Route::post('users/import', [UserController::class, 'import'])->name('users.import')->middleware('permission:manage users');
    Route::resource('users', UserController::class)->middleware('permission:manage users');

    // Department Management (Admin only)
    Route::resource('departments', DepartmentController::class)->middleware('permission:manage users');

    // Category Management (Admin/Module managers)
    Route::resource('categories', CategoryController::class)->middleware('permission:manage modules');

    // Enrollment (Learners)
    Route::post('modules/{module}/enroll', [UserController::class, 'enroll'])->name('modules.enroll');
    Route::get('my-enrollments', [UserController::class, 'myEnrollments'])->name('my.enrollments');
    Route::get('my-progress', [UserController::class, 'myProgress'])->name('my.progress');
    // JSON endpoint for live progress polling
    Route::get('api/my-progress', [UserController::class, 'progressJson'])->name('api.my.progress');

    // Evaluation (Instructors)
    Route::get('modules/{module}/evaluations', [AssignmentController::class, 'evaluations'])
        ->middleware('permission:assign modules')
        ->name('modules.evaluations');
    Route::get('modules/{module}/evaluations/{user}', [AssignmentController::class, 'userEvaluation'])
        ->middleware('permission:assign modules')
        ->name('modules.user-evaluation');
    Route::post('modules/{module}/evaluations/{user}/complete', [AssignmentController::class, 'complete'])
        ->middleware('permission:assign modules')
        ->name('modules.user-evaluation.complete');

    // Learner triggers module completion request
    Route::post('modules/{module}/complete', [ModuleCompletionApprovalController::class, 'request'])
        ->name('modules.complete');

    // Enrollment Approvals (Instructors/Admins)
    Route::get('enrollment-approvals', [EnrollmentApprovalController::class, 'index'])
        ->name('enrollment-approvals.index');
    Route::patch('enrollment-approvals/{enrollmentRequest}/approve', [EnrollmentApprovalController::class, 'approve'])
        ->name('enrollment-approvals.approve');
    Route::delete('enrollment-approvals/{enrollmentRequest}/reject', [EnrollmentApprovalController::class, 'reject'])
        ->name('enrollment-approvals.reject');

    // Retake Requests
    Route::post('retake-requests', [App\Http\Controllers\RetakeRequestController::class, 'store'])->name('retake-requests.store');
    Route::get('retake-requests', [App\Http\Controllers\RetakeRequestController::class, 'index'])
        ->middleware('permission:assign modules|manage users') // Instructors/managers
        ->name('retake-requests.index');
    Route::patch('retake-requests/{retakeRequest}', [App\Http\Controllers\RetakeRequestController::class, 'update'])
        ->middleware('permission:assign modules|manage users')
        ->name('retake-requests.update');

    // Module completion approvals
    Route::get('module-approvals', [ModuleCompletionApprovalController::class, 'index'])
        ->name('module-approvals.index');
    Route::patch('module-approvals/{approval}/approve', [ModuleCompletionApprovalController::class, 'approve'])
        ->name('module-approvals.approve');
    Route::patch('module-approvals/{approval}/reject', [ModuleCompletionApprovalController::class, 'reject'])
        ->name('module-approvals.reject');

    // Lesson Completion Approvals (their own page — no longer mixed into retake requests)
    Route::get('lesson-approvals', [App\Http\Controllers\LessonCompletionController::class, 'index'])
        ->middleware('permission:assign modules|manage users|manage modules|issue certificates')
        ->name('lesson-approvals.index');
    Route::patch('lesson-completions/{completion}/approve', [App\Http\Controllers\LessonCompletionController::class, 'approve'])->name('lesson-completions.approve');
    Route::patch('lesson-completions/{completion}/reject', [App\Http\Controllers\LessonCompletionController::class, 'reject'])->name('lesson-completions.reject');

    // Notifications
    Route::get('notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/unread', [App\Http\Controllers\NotificationController::class, 'getUnread'])->name('notifications.unread');
    Route::get('notifications/{id}/open', [App\Http\Controllers\NotificationController::class, 'open'])->name('notifications.open');
    Route::post('notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

require __DIR__ . '/auth.php';
