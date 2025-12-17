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
use App\Http\Controllers\SearchController;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Only authenticated users can access these routes
Route::middleware(['auth'])->group(function () {
    
    // Dashboard - accessible to all authenticated users
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Profile - accessible to all authenticated users
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // =============================================
    // PROJECTS SECTION - PERMISSION PROTECTED ROUTES
    // =============================================
    
    // Projects index - require 'view projects' permission
    Route::middleware(['can:view projects'])->group(function () {
        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    });
    
    // CRITICAL: CREATE route must come BEFORE SHOW route with {project} parameter
    Route::middleware(['can:create projects'])->group(function () {
        Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    });
    
    // Project show route - require 'view projects' permission
    Route::middleware(['can:view projects'])->group(function () {
        Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
        Route::post('/projects/{project}/accept', [ProjectController::class, 'accept'])->name('projects.accept');
        Route::post('/projects/{project}/decline', [ProjectController::class, 'decline'])->name('projects.decline');
    });
    
    // Project edit, update routes - require 'edit projects' permission
    Route::middleware(['can:edit projects'])->group(function () {
        Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
        Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::post('/projects/{project}/tasks/bulk-create', [ProjectController::class, 'bulkCreateTasks'])->name('projects.tasks.bulk-create');
    });
    
    // Project delete routes - require 'delete projects' permission
    Route::middleware(['can:delete projects'])->group(function () {
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    });
    
    // =============================================
    // TASKS SECTION - PERMISSION PROTECTED ROUTES
    // =============================================
    
    // View tasks - require 'view projects' permission
    Route::middleware(['can:view projects'])->group(function () {
        Route::get('/projects/{project}/tasks', [TaskController::class, 'index'])->name('tasks.index');
        Route::patch('/projects/{project}/tasks/{task}/toggle', [TaskController::class, 'toggleStatus'])->name('tasks.toggle');
        Route::patch('/projects/{project}/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
    });
    
    // Create tasks - require 'create tasks' permission
    Route::middleware(['can:create tasks'])->group(function () {
        Route::post('/projects/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
    });
    
    // Edit tasks - require 'edit tasks' permission
    Route::middleware(['can:edit tasks'])->group(function () {
        Route::put('/projects/{project}/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::patch('/projects/{project}/tasks/bulk-update', [TaskController::class, 'bulkUpdate'])->name('projects.tasks.bulk-update');
    });
    
    // Delete tasks - require 'delete tasks' permission
    Route::middleware(['can:delete tasks'])->group(function () {
        Route::delete('/projects/{project}/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    });
    
    // =============================================
    // COMMENTS SECTION - ACCESSIBLE TO ALL AUTHENTICATED USERS
    // =============================================
    Route::post('/projects/{project}/comments', [ProjectCommentController::class, 'store'])->name('projects.comments.store');
    Route::put('/projects/{project}/comments/{comment}', [ProjectCommentController::class, 'update'])->name('projects.comments.update');
    Route::delete('/projects/{project}/comments/{comment}', [ProjectCommentController::class, 'destroy'])->name('projects.comments.destroy');
    Route::post('/projects/{project}/comments/{comment}/seen', [ProjectCommentController::class, 'markAsSeen'])->name('projects.comments.seen');
    
    Route::post('/tasks/{task}/comments', [TaskCommentController::class, 'store'])->name('tasks.comments.store');
    
    // =============================================
    // CALENDAR SECTION - PERMISSION PROTECTED ROUTES
    // =============================================
    
    Route::middleware(['can:view events'])->group(function () {
        Route::get('/calendar', [EventController::class, 'index'])->name('calendar.index');
        Route::post('/calendar', [EventController::class, 'store'])->name('calendar.store');
        Route::put('/calendar/{event}', [EventController::class, 'update'])->name('calendar.update');
        Route::delete('/calendar/{event}', [EventController::class, 'destroy'])->name('calendar.destroy');
    });
    
    // =============================================
    // CHATS SECTION - ACCESSIBLE TO ALL AUTHENTICATED USERS
    // =============================================
    
    Route::get('/chat/list', [ChatController::class, 'list']);
    Route::get('/chats', [ChatController::class, 'index'])->name('chats.index');
    Route::get('/chats/{chat}', [ChatController::class, 'show'])->name('chats.show');
    Route::post('/chats', [ChatController::class, 'store'])->name('chats.store');
    Route::post('/chats/{chat}/messages', [ChatController::class, 'store'])->name('chats.store');
    Route::get('/chats/unread-count', [ChatController::class, 'getUnreadCount'])->name('chats.unread-count');
    Route::post('/chats/{chat}/mark-read', [ChatController::class, 'markAsRead'])->name('chats.mark-read');
    
    // =============================================
    // ACTIVITIES SECTION - PERMISSION PROTECTED ROUTES
    // =============================================
    Route::patch('/activities/{activity}/status', [ActivityController::class, 'updateStatus'])->name('activities.status');

    Route::middleware(['can:delete activities'])->group(function () {
        Route::delete('/activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');
    });
    
    Route::post('/projects/{project}/activities', [ActivityController::class, 'store'])->name('projects.activities.store');
    Route::put('/activities/{activity}', [ActivityController::class, 'update'])->name('activities.update');
    
    // =============================================
    // SEARCH SECTION - ACCESSIBLE TO ALL AUTHENTICATED USERS
    // =============================================
    
    Route::get('/search', [SearchController::class, 'index'])->name('search');
    Route::get('/api/search/quick', [SearchController::class, 'quickSearch'])->name('api.search.quick');
    
    // =============================================
    // SETTINGS SECTION - PERMISSION PROTECTED ROUTES
    // =============================================

    // Main settings page - requires any settings permission
    Route::middleware(['can:view users,view roles,view activity types'])->group(function () {
        Route::get('/settings/{tab?}', [SettingsController::class, 'index'])->name('settings.index');
    });

    // User CRUD routes - handled by UserController
    Route::middleware(['can:view users'])->prefix('settings')->group(function () {
        Route::post('/users', [UserController::class, 'store'])->name('settings.users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('settings.users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('settings.users.destroy');
    });

    // Role CRUD routes - handled by RoleController
    Route::middleware(['can:view roles'])->prefix('settings')->group(function () {
        Route::post('/roles', [RoleController::class, 'store'])->name('settings.roles.store');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('settings.roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('settings.roles.destroy');
    });

    // Activity Type CRUD routes - handled by ActivityTypeController
    Route::middleware(['can:view activity types'])->prefix('settings')->group(function () {
        Route::post('/activity-types', [ActivityTypeController::class, 'store'])->name('settings.activity-types.store');
        Route::put('/activity-types/{activityType}', [ActivityTypeController::class, 'update'])->name('settings.activity-types.update');
        Route::delete('/activity-types/{activityType}', [ActivityTypeController::class, 'destroy'])->name('settings.activity-types.destroy');
    });
});

require __DIR__ . '/auth.php';