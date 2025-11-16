<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ProjectCommentController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\EventController;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});





// Only authenticated users can access these routes
Route::middleware(['auth'])->group(function () {
Route::middleware(['auth'])->get('/chat/list', [ChatController::class, 'list']);

     Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Projects (all users can view, but permissions will restrict actions)
    Route::resource('projects', ProjectController::class);
      Route::post('/projects/{project}/accept', [ProjectController::class, 'accept'])->name('projects.accept');
    Route::post('/projects/{project}/decline', [ProjectController::class, 'decline'])->name('projects.decline');

     Route::get('/projects/{project}/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('/projects/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::delete('/projects/{project}/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::patch('/projects/{project}/tasks/{task}/toggle', [TaskController::class, 'toggleStatus'])->name('tasks.toggle');

    // Comments
    // Route::post('/tasks/{task}/comments', [CommentController::class, 'storeComment']);
    Route::put('/projects/{project}/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');

// Route::post('/projects/{project}/comments', [ProjectController::class, 'addComment'])
//     ->name('projects.comment')
//     ->middleware('auth');
 Route::post('/projects/{project}/comments', [ProjectCommentController::class, 'store'])
        ->name('projects.comments.store');
        Route::post('/tasks/{task}/comments', [TaskCommentController::class, 'store'])
    ->name('tasks.comments.store');



    Route::prefix('admin')->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('roles', RoleController::class);
    });
 Route::get('/chat/list', [ChatController::class, 'list']);
    // Route::get('/chats/{chat}', [ChatController::class, 'show'])->name('chats.show');
    Route::post('/chats/{chat}/messages', [ChatController::class, 'store'])->name('chats.store');
    // Route::post('/chat/private', [ChatController::class, 'createPrivateChat'])->name('chat.private');
    // Route::get('/users/list', [UserController::class, 'chatList']);
    // Route::post('/chat/message', [ChatController::class, 'sendMessage']);
 Route::get('/chats', [ChatController::class, 'index'])->name('chats.index');
    Route::get('/chats/{chat}', [ChatController::class, 'show'])->name('chats.show');
    Route::post('/chats', [ChatController::class, 'store'])->name('chats.store');
    // Route::post('/chats/{chat}/messages', [MessageController::class, 'store'])->name('chats.messages.store');
    // Route::get('/users/list', [UserController::class, 'list'])->middleware('auth');

  Route::get('/calendar', [EventController::class, 'index'])->name('calendar.index');
    Route::post('/calendar', [EventController::class, 'store'])->name('calendar.store');
    Route::put('/calendar/{event}', [EventController::class, 'update'])->name('calendar.update');
    Route::delete('/calendar/{event}', [EventController::class, 'destroy'])->name('calendar.destroy');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
