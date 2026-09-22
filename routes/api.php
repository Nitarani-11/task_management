<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskAssignmentController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Task Management & Dynamic Assignment Engine
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Public Auth Routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Direct legacy endpoint aliases as requested in PDF spec
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Protected Routes (Sanctum Auth)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);

        // User Assigned Tasks (Story 2) - Low latency endpoint
        Route::get('/my-eligible-tasks', [TaskAssignmentController::class, 'getMyEligibleTasks']);
        Route::get('/tasks/my-eligible-tasks', [TaskAssignmentController::class, 'getMyEligibleTasks']);

        // Task Management (Story 1, Story 4)
        Route::get('/tasks', [TaskController::class, 'index']);
        Route::post('/tasks', [TaskController::class, 'store']);
        Route::get('/tasks/{id}', [TaskController::class, 'show']);
        Route::put('/tasks/{id}', [TaskController::class, 'update']);
        Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);

        // Assignment Engine APIs
        Route::get('/tasks/{id}/eligible-users', [TaskAssignmentController::class, 'getEligibleUsers']);
        Route::post('/tasks/recompute-eligibility', [TaskAssignmentController::class, 'recomputeEligibility']);
    });
});
