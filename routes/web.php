<?php

use App\Http\Controllers\Admin\MatchNotificationController;
use App\Http\Controllers\Admin\TeacherManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeacherDirectoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return view('welcome');
    }

    return auth()->user()->hasRole('admin')
        ? redirect()->route('admin.sudins')
        : redirect()->route('dashboard');
})->name('welcome');
Route::middleware('auth')->get('/akun/password', fn () => view('account-password'))->name('account.password');
Route::redirect('/admin/login', '/login')->name('admin.login');

Route::middleware(['auth', 'verified', 'role:guru'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/daftar-guru', TeacherDirectoryController::class)->name('teacher-directory');
    Route::view('/teacher-profile', 'teacher-profile')->name('teacher-profile.edit');
    Route::view('/panduan', 'guide')->name('guide');

});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/sudins', 'admin.sudins')->name('sudins');
    Route::view('/subjects', 'admin.subjects')->name('subjects');
    Route::get('/users', [TeacherManagementController::class, 'index'])->name('users');
    Route::get('/deletion-requests', [TeacherManagementController::class, 'deletionRequests'])->name('deletion-requests');
    Route::get('/match-notifications', [MatchNotificationController::class, 'index'])->name('match-notifications');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
