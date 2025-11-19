<?php

use App\Http\Controllers\Api\ProjectTaskController;

Route::middleware('auth:sanctum')->get('/projects/{project}/tasks', [ProjectTaskController::class, 'index']);
