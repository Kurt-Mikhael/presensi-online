<?php

use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminLocationController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ConnectionCheckController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

// Autentikasi
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Pemeriksaan koneksi (bisa diakses publik — sengaja ringan, tanpa DB).
Route::get('/api/connection-check', ConnectionCheckController::class);

// Halaman + API presensi pegawai
Route::middleware('auth')->group(function () {
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
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/location', [AdminLocationController::class, 'index'])->name('admin.location');
    Route::get('/attendance', [AdminAttendanceController::class, 'index'])->name('admin.attendance.index');
});

Route::middleware(['auth', 'role:admin'])->prefix('api/admin')->group(function () {
    Route::get('/attendance', [AdminAttendanceController::class, 'list']);
    Route::get('/location', [AdminLocationController::class, 'show']);
    Route::put('/location', [AdminLocationController::class, 'update']);
    Route::patch('/location/{id}/toggle', [AdminLocationController::class, 'toggle']);
    Route::delete('/location/{id}', [AdminLocationController::class, 'destroy']);
});