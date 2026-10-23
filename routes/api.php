<?php

use App\Http\Controllers\Admin\MatchNotificationController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TeacherManagementController;
use App\Http\Controllers\Api\TokenController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\SudinController;
use App\Http\Controllers\TeacherDirectoryController;
use App\Http\Controllers\TeacherLifecycleController;
use App\Http\Controllers\TeacherProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [TokenController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('api.login');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::delete('/logout', [TokenController::class, 'destroy'])->name('api.logout');

    Route::middleware(['verified', 'role:guru', 'abilities:guru'])->group(function (): void {
        Route::get('/sudins', [SudinController::class, 'publicIndex']);
        Route::get('/sudins/{sudin}/districts', [SudinController::class, 'districts']);
        Route::get('/sudins/{sudin}/districts/{district}/villages', [SudinController::class, 'villages']);
        Route::get('/profile', [TeacherProfileController::class, 'show']);
        Route::post('/profile', [TeacherProfileController::class, 'store'])->middleware('throttle:10,1');
        Route::get('/teachers', [TeacherDirectoryController::class, 'apiIndex'])->name('api.teachers.index');
        Route::get('/matches', [MatchController::class, 'index'])->middleware('throttle:30,1');
        Route::patch('/teacher-profile/status', [TeacherLifecycleController::class, 'updateStatus']);
        Route::post('/teacher-profile/deletion-request', [TeacherLifecycleController::class, 'requestDeletion']);
    });

    Route::middleware(['role:admin', 'abilities:admin'])
        ->prefix('admin')
        ->name('api.admin.')
        ->group(function (): void {
            Route::get('/districts', [SudinController::class, 'allDistricts']);
            Route::patch('/users/{user}/reset-password', [TeacherManagementController::class, 'resetPassword'])
                ->name('users.reset-password');
            Route::get('/sudins', [SudinController::class, 'index']);
            Route::post('/sudins', [SudinController::class, 'store']);
            Route::put('/sudins/{sudin}', [SudinController::class, 'update']);
            Route::delete('/sudins/{sudin}', [SudinController::class, 'destroy']);
            Route::get('/subjects', [SubjectController::class, 'index']);
            Route::post('/subjects', [SubjectController::class, 'store']);
            Route::post('/subjects/import', [SubjectController::class, 'import']);
            Route::put('/subjects/{subject}', [SubjectController::class, 'update']);
            Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy']);
            Route::post('/match-notifications/scan', [MatchNotificationController::class, 'scan'])
                ->name('match-notifications.scan');
            Route::post('/match-notifications/send-pending', [MatchNotificationController::class, 'sendPending'])
                ->name('match-notifications.send-pending');
            Route::post('/match-notifications/{pair}/send', [MatchNotificationController::class, 'send'])
                ->name('match-notifications.send');
            Route::patch('/users/{user}/verify-email', [TeacherManagementController::class, 'verifyEmail'])
                ->name('users.verify-email');
            Route::delete('/users/{user}', [TeacherManagementController::class, 'destroyUser'])
                ->name('users.destroy');
            Route::patch('/teachers/{teacherProfile}/status', [TeacherManagementController::class, 'updateStatus'])
                ->name('teachers.status');
            Route::post('/profile-deletion-requests/{deletionRequest}/review', [TeacherManagementController::class, 'reviewDeletion'])
                ->name('profile-deletion-requests.review');
        });
});
