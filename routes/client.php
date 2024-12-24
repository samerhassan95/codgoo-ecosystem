<?php

use App\Http\Controllers\{Client\ClientAuthController,
    DepartmentController,
    MeetingController,
    MilestoneController,
    ProductAddonController,
    ProductController,
    ProductMediaController,
    ProjectAddonController,
    ProjectController,
    TaskController,
    TopicController};
use Illuminate\Support\Facades\Route;

Route::middleware('auth:client')->group(function () {
    Route::apiResource('projects', ProjectController::class);

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

    Route::get('profile', [ClientAuthController::class, 'getProfile']);
    Route::post('update-profile', [ClientAuthController::class, 'updateProfile']);

    Route::apiResource('project-addons', ProjectAddonController::class);

    Route::apiResource('tasks', TaskController::class)->names([
        'index' => 'client.tasks.index',
        'show' => 'client.tasks.show',
        'store' => 'client.tasks.store',
        'update' => 'client.tasks.update',
        'destroy' => 'client.tasks.destroy',
    ]);

    Route::get('milestones/{milestone_id}/tasks', [TaskController::class, 'getTasksByMilestone']);
    Route::get('projects/{project_id}/tasks', [TaskController::class, 'getTasksByProject']);
    Route::post('change-password', [ClientAuthController::class, 'changePassword']);
    Route::post('verify-change-phone', [ClientAuthController::class, 'verifyChangePhone']);
    Route::post('change-phone-request', [ClientAuthController::class, 'changePhoneRequest']);

    Route::get('products/{productId}/addons', [ProductAddonController::class, 'getAddonsByProject']);
    Route::apiResource('product-media', ProductMediaController::class)->names([
        'index' => 'client.product-media.index',
        'show' => 'client.product-media.show',
        'store' => 'client.product-media.store',
        'update' => 'client.product-media.update',
        'destroy' => 'client.product-media.destroy',
    ]);

    Route::post('meetings', [MeetingController::class, 'store']);
    Route::get('available-slots/{slotId}/free-intervals', [MeetingController::class, 'getAvailableIntervals']);

    Route::apiResource('topic', TopicController::class)->names([
        'index' => 'client.topic.index',
        'show' => 'client.topic.show',
    ]);

    Route::apiResource('departments', DepartmentController::class)->names([
        'index' => 'client.departments.index',

    ]);


    Route::apiResource('tickets', TicketController::class)->except(['edit', 'create']);

});
