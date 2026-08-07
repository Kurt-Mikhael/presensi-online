<?php

use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminLocationController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\ConnectionCheckController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

// Autentikasi
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::get('/profile/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/profile/password', [PasswordController::class, 'update'])
        ->middleware('throttle:password-change')
        ->name('password.update');
});

// Pemeriksaan koneksi (bisa diakses publik — sengaja ringan, tanpa DB).
Route::get('/api/connection-check', ConnectionCheckController::class);

// Halaman + API presensi pegawai
Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/history', [AttendanceController::class, 'history'])->name('attendance.history');

    Route::get('/api/attendance/today', [AttendanceController::class, 'today'])->name('attendance.today');

    Route::post('/api/attendance/check-in', [AttendanceController::class, 'checkIn'])
        ->middleware('throttle:attendance')
        ->name('attendance.check-in');

    Route::post('/api/attendance/check-out', [AttendanceController::class, 'checkOut'])
        ->middleware('throttle:attendance')
        ->name('attendance.check-out');
});

// Admin
Route::middleware(['auth', 'password.changed', 'role:admin,superadmin'])->prefix('admin')->group(function () {
    Route::get('/location', [AdminLocationController::class, 'index'])->name('admin.location');
    Route::patch('/location/work-hours', [AdminLocationController::class, 'updateWorkHours'])->name('admin.location.work-hours');
    Route::get('/attendance', [AdminAttendanceController::class, 'index'])->name('admin.attendance.index');
    Route::get('/attendance/export', [AdminAttendanceController::class, 'export'])->name('admin.attendance.export');
});

Route::middleware(['auth', 'password.changed', 'role:superadmin'])->prefix('admin')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('admin.users.role');
    Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])
        ->middleware('throttle:admin-password-reset')
        ->name('admin.users.reset-password');
    Route::patch('/attendance/{user}/{date}/times', [AdminAttendanceController::class, 'updateTimes'])->name('admin.attendance.times');
});

Route::middleware(['auth', 'password.changed', 'role:admin,superadmin'])->prefix('api/admin')->group(function () {
    Route::get('/attendance', [AdminAttendanceController::class, 'list']);
    Route::get('/location', [AdminLocationController::class, 'show']);
    Route::put('/location', [AdminLocationController::class, 'update']);
    Route::patch('/location/{id}/toggle', [AdminLocationController::class, 'toggle']);
    Route::delete('/location/{id}', [AdminLocationController::class, 'destroy']);
});
