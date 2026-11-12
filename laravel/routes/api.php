<?php

use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\AdminEmployeeController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::prefix('admin/auth')->group(function (): void {
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware(['auth:sanctum'])->group(function (): void {
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::middleware('admin')->prefix('admin')->group(function (): void {
        Route::post('/auth/logout', [AdminAuthController::class, 'logout']);
        Route::post('/employees', [AdminEmployeeController::class, 'store'])->middleware('throttle:10,1');
    });
    Route::middleware('password.changed')->group(function (): void {
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::get('/attendance', [AttendanceController::class, 'index']);
        Route::post('/attendance/time-in', [AttendanceController::class, 'timeIn']);
        Route::post('/attendance/time-out', [AttendanceController::class, 'timeOut']);
        Route::get('/leave-requests', [LeaveRequestController::class, 'index']);
        Route::post('/leave-requests', [LeaveRequestController::class, 'store']);
    });
});
