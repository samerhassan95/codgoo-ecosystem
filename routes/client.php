<?php

use App\Http\Controllers\{CategoryController,
    Client\ClientAuthController,
    ContractController,
    DepartmentController,
    InvoiceController,
    MeetingController,
    MilestoneController,
    PrivacyPolicyController,
    ProductAddonController,
    ProductController,
    ProductMediaController,
    ProjectAddonController,
    ProjectController,
    SliderController,
    TaskController,
    TicketController,
    TicketReplyController,
    TopicController,
    AvailableSlotController,
    NotificationController,
    PaymentController,
    ChatController, 

};
use Illuminate\Support\Facades\Route;


Route::middleware('client')->group(function ()  {
    Route::apiResource('projects', ProjectController::class)->names([
        'index' => 'client.projects.index',
        'show' => 'client.projects.show',
        'store' => 'client.projects.store',
        'update' => 'client.projects.update',
        'destroy' => 'client.projects.destroy',
    ]);
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
    Route::get('specific-product-media/{productId}', [ProductMediaController::class, 'getAllMediaForProduct']);

    // Route::post('meetings', [MeetingController::class, 'store']);
    // Route::get('available-slots/{slotId}/free-intervals', [MeetingController::class, 'getAvailableIntervals']);

    Route::apiResource('topic', TopicController::class)->names([
        'index' => 'client.topic.index',
        'show' => 'client.topic.show',
    ]);

    Route::apiResource('departments', DepartmentController::class)->names([
        'index' => 'client.departments.index',

    ]);


    Route::apiResource('tickets', TicketController::class)->names([
        'index' => 'client.tickets.index',
        'show' => 'client.tickets.show',
        'store' => 'client.tickets.store',
        'update' => 'client.tickets.update',
        'destroy' => 'client.tickets.destroy',
    ]);
    Route::apiResource('sliders', SliderController::class)->names([
        'index' => 'client.sliders.index',
        'show' => 'client.sliders.show',
        'store' => 'client.sliders.store',
        'update' => 'client.sliders.update',
        'destroy' => 'client.sliders.destroy',
    ]);

    Route::get('project/status-count', [ProjectController::class, 'getStatusCounts']);
    Route::get('project/filter/{status}', [ProjectController::class, 'filterProjectsByStatus']);
    Route::get('project/{project_id}/details', [ProjectController::class, 'getProjectDetails']);
    Route::get('invoice/status/count', [InvoiceController::class, 'getInvoiceStatusCounts']);
    Route::get('project/{projectId}/tasks-summary', [ProjectController::class, 'getTaskSummaryForProject']);
    Route::post('project/{projectId}/attachments', [ProjectController::class, 'uploadAttachment']);
    Route::get('project/{projectId}/attachments', [ProjectController::class, 'getAllAttachments']);
    Route::get('projects/{projectId}/invoices', [InvoiceController::class, 'getInvoicesForProject']);



    Route::prefix('meetings')->group(function () {
        Route::post('/', [MeetingController::class, 'store']);
        Route::get('/{slotId}/available-intervals', [MeetingController::class, 'getAvailableIntervals']);
    });


    Route::get('/available-slots', [AvailableSlotController::class, 'getAvailableSlotsGroupedByDate']);
    Route::get('client-meetings', [MeetingController::class, 'getMeetingsForClient']);
    Route::get('meetings/filter', [MeetingController::class, 'filterMeetingsByStatus']);
    Route::get('meeting/{id}', [MeetingController::class, 'getMeetingById']);
    Route::get('client-tickets', [TicketController::class, 'getTicketsForClient']);
    Route::apiResource('ticket-reply', TicketReplyController::class)->names([
        'index' => 'client.ticket-reply.index',
        'store' => 'client.ticket-reply.store',
        'show' => 'client.ticket-reply.show',
        'update' => 'client.ticket-reply.update',
        'destroy' => 'client.ticket-reply.destroy',
    ]);
    Route::get('ticket-summary', [TicketController::class, 'getTicketsAndSummary']);

    Route::get('home-page', [ProjectController::class, 'getDashboardSummary']);

    Route::get('tickets/{ticket_id}/replies', [TicketController::class, 'getRepliesForTicket']);
    Route::apiResource('category', CategoryController::class)->names([
        'index' => 'client.category.index',
        'store' => 'client.category.store',
        'show' => 'client.category.show',
        'update' => 'client.category.update',
        'destroy' => 'client.category.destroy',
    ]);

    Route::get('privacy-policy', [PrivacyPolicyController::class, 'index']);
    Route::post('privacy-policy', [PrivacyPolicyController::class, 'store']);
    Route::get('client-invoices', [InvoiceController::class, 'getInvoicesForClient']);
    Route::get('invoice-details/{invoiceId}', [InvoiceController::class, 'getInvoiceDetails']);
    Route::delete('delete', [ClientAuthController::class, 'deleteAccount']);

    Route::post('contracts/{projectId}/upload', [ContractController::class, 'uploadContract']);
    Route::post('contracts/{contractId}/sign', [ContractController::class, 'signContract']);
    Route::get('contracts/{contractId}', [ContractController::class, 'getContractDetails']);
    Route::get('contracts', [ContractController::class, 'getClientContracts']);
    Route::get('notifications', [NotificationController::class, 'getNotifications']);
    Route::post('chats/{chatId}/seen', [ChatController::class, 'markChatAsSeen']);

    Route::post('notifications/{id}/read', [NotificationController::class, 'markNotificationAsRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllNotificationsAsRead']);

    Route::post('pay-invoice/{invoiceId}', [PaymentController::class, 'payInvoice']);
    ///Software Dashboard Routes
    Route::get('dashboard/projects', [ProjectController::class, 'getClientDashboardProjects']);
    Route::get('project/overview/{id}', [ProjectController::class, 'getProjectFullDetails']);
    Route::get('project/tasks/{id}', [ProjectController::class, 'getProjectTasks']);
    Route::get('project/invoices/{id}', [ProjectController::class, 'getProjectInvoices']);
    Route::get('project/attachments/{id}', [ProjectController::class, 'getProjectAttachments']);
    Route::delete('project/attachments/{id}', [ProjectController::class, 'deleteProjectAttachment']);

});
Route::get('topics', [TopicController::class, 'getTopicsBySection']);
