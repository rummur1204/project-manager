<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RoleController;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});





// Only authenticated users can access these routes
Route::middleware(['auth'])->group(function () {

     Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Projects (all users can view, but permissions will restrict actions)
    Route::resource('projects', ProjectController::class);

    // 🧑‍💼 User Management (Super Admin only)
    // -> Prefix: /admin/users
    Route::prefix('admin')->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('roles', RoleController::class);
    });
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
