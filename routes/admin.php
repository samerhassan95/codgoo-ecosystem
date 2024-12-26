<?php

use App\Http\Controllers\{
    AddonController,
    InvoiceController,
    MigrationController,
    MilestoneController,
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
};
use Illuminate\Support\Facades\Route;



Route::middleware('auth:admin')->group(function () {

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

    Route::apiResource('ticket-reply', TicketReplyController::class)->except(['edit', 'create']);
    Route::apiResource('skills', SkillController::class);



Route::controller(SkillController::class)->prefix('skills')->group(function () {
    Route::post('/assign/{employeeId}', 'assignSkillsToEmployee');
    Route::delete('/remove/{employeeId}/{skillId}', 'removeSkillFromEmployee');
});

});
