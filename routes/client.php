<?php

use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Client\ClientAuthController;
use App\Http\Controllers\ProjectAddonController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:client')->group(function () {
    Route::apiResource('projects', ProjectController::class);

    // Client specific route name for products
    Route::apiResource('products', ProductController::class)->only(['index', 'show'])->names([
        'index' => 'client.products.index',
        'show' => 'client.products.show',
    ]);

    Route::apiResource('milestone', MilestoneController::class)->only(['index', 'show'])->names([
        'index' => 'client.milestone.index',
        'show' => 'client.milestone.show',
    ]);
    Route::get('projects/{projectId}/milestones', [MilestoneController::class, 'getMilestonesForProject'])
        ->name('client.projects.milestones');

    // Profile routes
    Route::get('profile', [ClientAuthController::class, 'getProfile']);
    Route::post('update-profile', [ClientAuthController::class, 'updateProfile']);

    Route::apiResource('project-addons', ProjectAddonController::class);

    Route::apiResource('tasks', TaskController::class)->names([
        'index' => 'client.tasks.index',
        'show' => 'client.tasks.show',
        'store' => 'client.tasks.store',
        'update' => 'client.tasks.update',
        'destroy' => 'client.tasks.destroy'
    ]);


    Route::get('milestones/{milestone_id}/tasks', [TaskController::class, 'getTasksByMilestone']);
    Route::get('projects/{project_id}/tasks', [TaskController::class, 'getTasksByProject']);
    Route::post('change-password', [ClientAuthController::class, 'changePassword']);
    Route::post('verify-change-phone', [ClientAuthController::class, 'verifyChangePhone']);
    Route::post('change-phone-request', [ClientAuthController::class, 'changePhoneRequest']);

});
