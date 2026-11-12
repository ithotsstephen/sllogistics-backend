<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');
Route::get('/admin/login', [AdminController::class, 'loginPage'])->name('admin.login');
Route::post('/admin/login', [AdminController::class, 'login'])->middleware('throttle:10,1')->name('admin.login.submit');
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/time-tracking', [AdminController::class, 'timeTracking'])->name('time-tracking');
    Route::get('/leave-management', [AdminController::class, 'leaveManagement'])->name('leave.index');
    Route::get('/employees', [AdminController::class, 'employeeManagement'])->name('employees');
    Route::get('/password', [AdminController::class, 'passwordPage'])->name('password');
    Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
    Route::put('/password', [AdminController::class, 'changePassword'])->name('password.update');
    Route::post('/employees', [AdminController::class, 'createStaff'])->name('staff.store');
    Route::put('/employees/{user}', [AdminController::class, 'updateStaff'])->name('staff.update');
    Route::patch('/leave-requests/{leaveRequest}', [AdminController::class, 'decideLeave'])->name('leave.decide');
});
