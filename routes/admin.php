<?php

use App\Http\Controllers\{AddonController,
    CategoryController,
    Client\ClientAuthController,
    ContractController,
    Employee\EmployeeAuthController,
    InvoiceController,
    MeetingController,
    MigrationController,
    MilestoneController,
    NotificationController,
    ProductController,
    ProductAddonController,
    ProductMediaController,
    ProjectController,
    SliderController,
    TaskController,
    TopicController,
    TicketController,
    DepartmentController,
    TicketReplyController,
    SkillController,
    GalleryController,
    ChatController};
use Illuminate\Support\Facades\Route;



Route::middleware('admin')->group(function () {

    Route::apiResource('product-media', ProductMediaController::class)->names([
        'index' => 'admin.product-media.index',
        'show' => 'admin.product-media.show',
        'store' => 'admin.product-media.store',
        'update' => 'admin.product-media.update',
        'destroy' => 'admin.product-media.destroy',
    ]);

    Route::get('specific-product-media/{productId}', [ProductMediaController::class, 'getAllMediaForProduct']);
    Route::apiResource('addons', AddonController::class);

    Route::apiResource('products', ProductController::class)->names([
        'index' => 'admin.products.index',
        'show' => 'admin.products.show',
        'store' => 'admin.products.store',
        'update' => 'admin.products.update',
        'destroy' => 'admin.products.destroy',
    ]);

    Route::apiResource('milestone', MilestoneController::class)->names([
        'index' => 'admin.milestone.index',
        'show' => 'admin.milestone.show',
        'store' => 'admin.milestone.store',
        'update' => 'admin.milestone.update',
        'destroy' => 'admin.milestone.destroy',
    ]);

    Route::apiResource('product-addons', ProductAddonController::class);

    Route::apiResource('tasks', TaskController::class)->names([
        'index' => 'admin.tasks.index',
        'show' => 'admin.tasks.show',
        'store' => 'admin.tasks.store',
        'update' => 'admin.tasks.update',
        'destroy' => 'admin.tasks.destroy',
    ]);

    Route::apiResource('sliders', SliderController::class);

    Route::apiResource('sliders', ProductMediaController::class)->names([
        'index' => 'admin.sliders.index',
        'show' => 'admin.sliders.show',
        'store' => 'admin.sliders.store',
        'update' => 'admin.sliders.update',
        'destroy' => 'admin.sliders.destroy',
    ]);
    Route::apiResource('invoices', InvoiceController::class);
    Route::post('run-migrations', [MigrationController::class, 'runMigrations']);
    Route::get('projects/{projectId}/invoices', [InvoiceController::class, 'getInvoicesForProject']);

    Route::apiResource('topic', TopicController::class)->names([
        'index' => 'admin.topic.index',
        'show' => 'admin.topic.show',
        'store' => 'admin.topic.store',
        'update' => 'admin.topic.update',
        'destroy' => 'admin.topic.destroy',
    ]);


    Route::apiResource('departments', DepartmentController::class)->names([
        'index' => 'admin.departments.index',
        'store' => 'admin.departments.store',
        'show' => 'admin.departments.show',
        'update' => 'admin.departments.update',
        'destroy' => 'admin.departments.destroy',
    ]);

    Route::apiResource('ticket-reply', TicketReplyController::class)->names([
        'index' => 'admin.ticket-reply.index',
        'store' => 'admin.ticket-reply.store',
        'show' => 'admin.ticket-reply.show',
        'update' => 'admin.ticket-reply.update',
        'destroy' => 'admin.ticket-reply.destroy',
    ]);

    Route::apiResource('skills', SkillController::class);



    Route::controller(SkillController::class)->prefix('skills')->group(function () {
        Route::post('/assign/{employeeId}', 'assignSkillsToEmployee');
        Route::delete('/remove/{employeeId}/{skillId}', 'removeSkillFromEmployee');
    });
    Route::post('galleries', [GalleryController::class, 'store']);
    Route::apiResource('category', CategoryController::class)->names([
        'index' => 'admin.category.index',
        'store' => 'admin.category.store',
        'show' => 'admin.category.show',
        'update' => 'admin.category.update',
        'destroy' => 'admin.category.destroy',
    ]);

    Route::get('meetings/with-project', [MeetingController::class, 'getMeetingsWithProject']);
    Route::get('meeting/{id}', [MeetingController::class, 'getMeetingById']);
    Route::put('meetings/{id}', [MeetingController::class, 'update']);
    Route::apiResource('projects', ProjectController::class)->names([
        'index' => 'admin.projects.index',
        'show' => 'admin.projects.show',
        'store' => 'admin.projects.store',
        'update' => 'admin.projects.update',
        'destroy' => 'admin.projects.destroy',
    ]);
    Route::get('projects/{projectId}/milestones', [MilestoneController::class, 'getMilestonesForProject'])
    ->name('admin.projects.milestones');

    Route::get('milestones/{milestone_id}/tasks', [TaskController::class, 'getTasksByMilestone']);

    Route::delete('projects/attachments/{attachmentId}', [ProjectController::class, 'deleteAttachment']);

    Route::post('send-notification', [NotificationController::class, 'sendNotification']);
    Route::get('get-clients', [ClientAuthController::class, 'getAllClients']);
    Route::post('contracts/{projectId}/upload', [ContractController::class, 'uploadContract']);

    Route::get('chats', [ChatController::class, 'getAllChats']);
    Route::post('chats/{chatId}/seen', [ChatController::class, 'markChatAsSeen']);
    Route::get('employees', [EmployeeAuthController::class, 'getAllEmployees']);
    Route::delete('products/{product}/media/{media}', [ProductController::class, 'deleteMedia']);
    Route::get('notifications', [NotificationController::class, 'getNotifications']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markNotificationAsRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllNotificationsAsRead']);
});
