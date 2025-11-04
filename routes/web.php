<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DeveloperController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;



// 👇 Default: show login page first
Route::get('/', function () {
    return redirect('/login');
});

// 🧭 Dashboards by role
Route::middleware(['auth'])->group(function () {

    Route::get('/admin/dashboard', function () {
        $user = Auth::user();
        if (!$user->hasRole('Super Admin')) abort(403);
        return Inertia::render('Admin/Dashboard');
    })->name('admin.dashboard');

    Route::get('/developer/dashboard', function () {
        $user = Auth::user();
        if (!$user->hasRole('Developer')) abort(403);
        return Inertia::render('Developer/Dashboard');
    })->name('developer.dashboard');

    Route::get('/client/dashboard', function () {
        $user = Auth::user();
        if (!$user->hasRole('Client')) abort(403);
        return Inertia::render('Client/Dashboard');
    })->name('client.dashboard');
    
    Route::get('/admin/dashboard', fn() => Inertia::render('Admin/Projects/Dashboard'))->name('admin.dashboard');
    // Route::get('/admin/developers', fn() => Inertia::render('Admin/Developers'))->name('admin.developers');
    // Route::get('/admin/clients', fn() => Inertia::render('Admin/Clients/Clients'))->name('admin.clients');

     Route::get('/admin/clients', [ClientController::class, 'index'])->name('admin.clients');
    Route::get('/admin/clients/create', [ClientController::class, 'create'])->name('admin.clients.create');
    Route::post('/admin/clients', [ClientController::class, 'store'])->name('admin.clients.store');
    Route::get('/admin/clients/{id}/edit', [ClientController::class, 'edit'])->name('admin.clients.edit');
    Route::put('/admin/clients/{id}', [ClientController::class, 'update'])->name('admin.clients.update');
    Route::delete('/admin/clients/{id}', [ClientController::class, 'destroy'])->name('admin.clients.destroy');

    Route::get('/admin/developers', [DeveloperController::class, 'index'])->name('admin.developers');
    Route::get('/admin/developers/create', [DeveloperController::class, 'create'])->name('admin.developers.create');
    Route::post('/admin/developers', [DeveloperController::class, 'store'])->name('admin.developers.store');
    Route::get('/admin/developers/{id}/edit', [DeveloperController::class, 'edit'])->name('admin.developers.edit');
    Route::put('/admin/developers/{id}', [DeveloperController::class, 'update'])->name('admin.developers.update');
    Route::delete('/admin/developers/{id}', [DeveloperController::class, 'destroy'])->name('admin.developers.destroy');

    // Route::resource('developers' , DeveloperController::class);
});







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
