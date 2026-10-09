<?php

use App\Http\Controllers\Admin\MatchNotificationController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TeacherManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SudinController;
use App\Http\Controllers\TeacherDirectoryController;
use App\Http\Controllers\TeacherLifecycleController;
use App\Http\Controllers\TeacherProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return view('welcome');
    }

    return auth()->user()->hasRole('admin')
        ? redirect()->route('admin.sudins')
        : redirect()->route('dashboard');
})->name('welcome');
// Route::middleware('auth')->get('/akun/password', fn () => view('account-password'))->name('account.password');
// Route::redirect('/admin/login', '/login')->name('admin.login');

// Route::middleware(['auth', 'verified', 'role:guru'])->group(function () {
//     Route::get('/dashboard', DashboardController::class)->name('dashboard');
//     Route::get('/daftar-guru', TeacherDirectoryController::class)->name('teacher-directory');
//     Route::view('/teacher-profile', 'teacher-profile')->name('teacher-profile.edit');
//     Route::view('/panduan', 'guide')->name('guide');

//     Route::prefix('api')->group(function () {
//         Route::get('/sudins', [SudinController::class, 'publicIndex']);
//         Route::get('/sudins/{sudin}/districts', [SudinController::class, 'districts']);
//         Route::get('/profile', [TeacherProfileController::class, 'show']);
//         Route::post('/profile', [TeacherProfileController::class, 'store'])->middleware('throttle:10,1');
//         Route::get('/matches', [MatchController::class, 'index'])->middleware('throttle:30,1');
//         Route::patch('/teacher-profile/status', [TeacherLifecycleController::class, 'updateStatus']);
//         Route::post('/teacher-profile/deletion-request', [TeacherLifecycleController::class, 'requestDeletion']);
//     });
// });

// Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
//     Route::view('/sudins', 'admin.sudins')->name('sudins');
//     Route::view('/subjects', 'admin.subjects')->name('subjects');
//     Route::get('/users', [TeacherManagementController::class, 'index'])->name('users');
//     Route::get('/deletion-requests', [TeacherManagementController::class, 'deletionRequests'])->name('deletion-requests');
//     Route::get('/match-notifications', [MatchNotificationController::class, 'index'])->name('match-notifications');
// });

// Route::middleware(['auth', 'role:admin'])->prefix('api/admin')->name('api.admin.')->group(function () {
//     Route::get('/districts', [SudinController::class, 'allDistricts']);
//     Route::patch('/users/{user}/reset-password', [TeacherManagementController::class, 'resetPassword'])->name('users.reset-password');
//     Route::get('/sudins', [SudinController::class, 'index']);
//     Route::post('/sudins', [SudinController::class, 'store']);
//     Route::put('/sudins/{sudin}', [SudinController::class, 'update']);
//     Route::delete('/sudins/{sudin}', [SudinController::class, 'destroy']);
//     Route::get('/subjects', [SubjectController::class, 'index']);
//     Route::post('/subjects', [SubjectController::class, 'store']);
//     Route::post('/subjects/import', [SubjectController::class, 'import']);
//     Route::put('/subjects/{subject}', [SubjectController::class, 'update']);
//     Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy']);
//     Route::post('/match-notifications/scan', [MatchNotificationController::class, 'scan'])->name('match-notifications.scan');
//     Route::post('/match-notifications/send-pending', [MatchNotificationController::class, 'sendPending'])->name('match-notifications.send-pending');
//     Route::post('/match-notifications/{pair}/send', [MatchNotificationController::class, 'send'])->name('match-notifications.send');
//     Route::patch('/users/{user}/verify-email', [TeacherManagementController::class, 'verifyEmail'])
//         ->name('users.verify-email');
//     Route::delete('/users/{user}', [TeacherManagementController::class, 'destroyUser'])
//         ->name('users.destroy');
//     Route::patch('/teachers/{teacherProfile}/status', [TeacherManagementController::class, 'updateStatus'])
//         ->name('teachers.status');
//     Route::post('/profile-deletion-requests/{deletionRequest}/review', [TeacherManagementController::class, 'reviewDeletion'])
//         ->name('profile-deletion-requests.review');
// });

// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

require __DIR__.'/auth.php';
