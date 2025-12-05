<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KnowledgeFileController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\TrainingModuleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DepartmentController;
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



Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('welcome');
})->name('home');

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

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Training Modules
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
    Route::get('certificates/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');
    
    // Admin Certificate Management
    Route::get('admin/certificates', [CertificateController::class, 'adminIndex'])
        ->middleware('permission:issue certificates')
        ->name('admin.certificates.index');
    Route::post('admin/certificates/issue/{user}/{module}', [CertificateController::class, 'issue'])
        ->middleware('permission:issue certificates')
        ->name('admin.certificates.issue');
    // Knowledge Files
    Route::resource('knowledge', KnowledgeFileController::class)->except(['show']);
    Route::get('knowledge/{knowledge}/download', [KnowledgeFileController::class, 'download'])->name('knowledge.download');

    // User Management (Admin only)
    Route::resource('users', UserController::class)->middleware('permission:manage users');
    // Import users from template and download template
    // Allow optional file extension so URLs like /users/template.csv also work
    Route::get('users/template/{extension?}', [UserController::class, 'downloadTemplate'])
        ->where('extension', 'csv|xls|xlsx')
        ->name('users.template')
        ->middleware('permission:manage users');
    Route::post('users/import', [UserController::class, 'import'])->name('users.import')->middleware('permission:manage users');

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

    // Lesson Completion Approvals
    Route::patch('lesson-completions/{completion}/approve', [App\Http\Controllers\LessonCompletionController::class, 'approve'])->name('lesson-completions.approve');
    Route::delete('lesson-completions/{completion}/reject', [App\Http\Controllers\LessonCompletionController::class, 'reject'])->name('lesson-completions.reject');

    // Notifications
    Route::get('notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/unread', [App\Http\Controllers\NotificationController::class, 'getUnread'])->name('notifications.unread');
    Route::post('notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

require __DIR__ . '/auth.php';
