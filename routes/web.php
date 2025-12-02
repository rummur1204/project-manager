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
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityTypeController;
use App\Http\Controllers\SettingsController;

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
    Route::put('projects/{project}/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/projects/{project}/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::patch('/projects/{project}/tasks/{task}/toggle', [TaskController::class, 'toggleStatus'])->name('tasks.toggle');
    // Bulk task update route
Route::patch('/projects/{project}/tasks/bulk-update', [TaskController::class, 'bulkUpdate'])->name('projects.tasks.bulk-update');
// Bulk task creation route
Route::post('/projects/{project}/tasks/bulk-create', [ProjectController::class, 'bulkCreateTasks'])->name('projects.tasks.bulk-create');
// Temporary debug route in web.php
Route::patch('/projects/{project}/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
    // In web.php
// Route::put('/projects/{project}/tasks/bulk-update', [TaskController::class, 'bulkUpdate'])->name('projects.tasks.bulk-update');
// Route::post('/projects/{project}/tasks', [ProjectController::class, 'storeTasks'])->name('projects.tasks.store');
    // Comments
    // Route::post('/tasks/{task}/comments', [CommentController::class, 'storeComment']);
    // Route::put('/projects/{project}/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');

// Route::post('/projects/{project}/comments', [ProjectController::class, 'addComment'])
//     ->name('projects.comment')
//     ->middleware('auth');
 Route::post('/projects/{project}/comments', [ProjectCommentController::class, 'store'])
        ->name('projects.comments.store');
        Route::post('/tasks/{task}/comments', [TaskCommentController::class, 'store'])
    ->name('tasks.comments.store');


Route::get('/settings/{tab?}', [SettingsController::class, 'index'])
        ->name('settings.index');
    Route::prefix('settings')->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('roles', RoleController::class);
        Route::resource('activity-types', ActivityTypeController::class)
        ->except(['show']);
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
    // Add this route for getting unread count
Route::get('/chats/unread-count', [ChatController::class, 'getUnreadCount'])
    ->middleware('auth')
    ->name('chats.unread-count');
    // Route::post('/chats/{chat}/messages', [MessageController::class, 'store'])->name('chats.messages.store');
    // Route::get('/users/list', [UserController::class, 'list'])->middleware('auth');

//  Route::get('/calendar', [EventController::class, 'index'])->name('calendar.index');
// Route::post('/calendar', [EventController::class, 'store'])->name('calendar.store');
// // Route::put('/calendar/{event}', [EventController::class, 'update'])->name('calendar.update');
// // Route::patch('/calendar/{event}/update-date', [EventController::class, 'updateDate'])->name('calendar.update-date');
// // Route::delete('/calendar/{event}', [EventController::class, 'destroy'])->name('calendar.destroy');

Route::get('/calendar', [EventController::class, 'index'])->name('calendar.index');
Route::post('/calendar', [EventController::class, 'store'])->name('calendar.store');
Route::put('/calendar/{event}', [EventController::class, 'update'])->name('calendar.update');
Route::delete('/calendar/{event}', [EventController::class, 'destroy'])->name('calendar.destroy');

// Route to fetch tasks for a project
// Route::get('/projects/{project}/tasks', [EventController::class, 'getProjectTasks']);


    // Route::get('/settings/activity-types', 
    //     [ActivityTypeController::class, 'index'])
    //     ->middleware('permission:view activity types')
    //     ->name('activity-types.index');

    // Route::post('/settings/activity-types', 
    //     [ActivityTypeController::class, 'store'])
    //     ->middleware('permission:create activity types')
    //     ->name('activity-types.store');

    // Route::put('/settings/activity-types/{activityType}', 
    //     [ActivityTypeController::class, 'update'])
    //     ->middleware('permission:edit activity types')
    //     ->name('activity-types.update');

    // Route::delete('/settings/activity-types/{activityType}', 
    //     [ActivityTypeController::class, 'destroy'])
    //     ->middleware('permission:delete activity types')
    //     ->name('activity-types.destroy');


      // Activities
Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');
Route::get('/activities/create', [ActivityController::class, 'create'])->name('activities.create');
Route::post('/activities', [ActivityController::class, 'store'])->name('activities.store');


Route::get('/activities/{activity}/edit', [ActivityController::class, 'edit'])->name('activities.edit');
Route::put('/activities/{activity}', [ActivityController::class, 'update'])->name('activities.update');
Route::delete('/activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');

 Route::post('/activities/{activity}/accept', [ActivityController::class, 'accept'])->name('activities.accept');
    Route::post('/activities/{activity}/complete', [ActivityController::class, 'complete'])->name('activities.complete');

    // In your web.php, add this line in the activities section:
Route::post('/projects/{project}/activities', [ActivityController::class, 'store'])->name('projects.activities.store');
// Add this route for activity status updates
Route::patch('/activities/{activity}/status', [ActivityController::class, 'updateStatus'])
    ->name('activities.status');

      Route::get('/search', [SearchController::class, 'index'])->name('search');
    Route::get('/api/search/quick', [SearchController::class, 'quickSearch'])->name('api.search.quick');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
