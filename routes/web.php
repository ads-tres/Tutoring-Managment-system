<?php

use App\Http\Controllers\Parent\DashboardController;
use App\Http\Controllers\Parent\AttendanceController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;


// Route::get('/', function () {
//      return view('welcome');
//  });
 
//  Route::get('/dashboard', function () {
//      return view('dashboard');
//  })->middleware(['auth', 'verified'])->name('dashboard');
 
//  Route::middleware('auth')->group(function () {
//      Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//      Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//      Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
//  });
 
 require __DIR__.'/auth.php';

// // Parent-only routes
// Route::middleware(['auth', 'role:parent'])
//     ->prefix('parent')
//     ->name('parent.')
//     ->group(function () {
//         // Dashboard
//         Route::get('/dashboard', DashboardController::class)
//              ->name('dashboard');

//         // Approve a pending attendance
//         Route::put('/attendance/{attendance}/approve', [AttendanceController::class, 'approve'])
//              ->name('attendance.approve');

//         // Dispute a pending attendance (parent adds comment)
//         Route::put('/attendance/{attendance}/dispute', [AttendanceController::class, 'dispute'])
//              ->name('attendance.dispute');

//         // Export all attendance for this parent's children (CSV)
//         Route::get('/attendance/export', [AttendanceController::class, 'export'])
//              ->name('attendance.export');
// });
