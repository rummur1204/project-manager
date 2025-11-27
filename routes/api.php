<?php

use App\Http\Controllers\Api\EventController;

Route::get('/projects/{projectId}/tasks', [EventController::class, 'getProjectTasks']);