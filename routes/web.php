<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ClientController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;




Route::get('/', function () {
    return redirect('/login');
});


Route::get('/admin/dashboard', function () {
    $user = Auth::user();
    if (!$user->hasRole('Super Admin')) abort(403);
    return Inertia::render('Admin/Dashboard');
})->middleware('auth')->name('admin.dashboard');

Route::get('/developer/dashboard', function () {
    $user = Auth::user();
    if (!$user->hasRole('Developer')) abort(403);
    return Inertia::render('Developer/Dashboard');
})->middleware('auth')->name('developer.dashboard');

Route::get('/client/dashboard', function () {
    $user = Auth::user();
    if (!$user->hasRole('Client')) abort(403);
    return Inertia::render('Client/Dashboard');
})->middleware('auth')->name('client.dashboard');







// Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');

// Route::middleware(['auth', 'role:client'])->group(function () {
//     Route::get('/client/dashboard', [ClientController::class, 'dashboard'])->name('client.dashboard');
//     Route::get('/client/projects/{project}', [ClientController::class, 'show'])->name('client.project.show');
// });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
