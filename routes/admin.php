<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MigrationController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductMediaController;
use App\Http\Controllers\AddonController;
use App\Http\Controllers\ProductAddonController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SliderController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;



Route::middleware('auth:admin')->group(function () {
    
    Route::apiResource('product-media', ProductMediaController::class)->names([
        'index' => 'admin.product-media.index',
        'show' => 'admin.product-media.show',
        'store' => 'admin.product-media.store',
        'update' => 'admin.product-media.update',
        'destroy' => 'admin.product-media.destroy'
    ]);
    
    Route::get('specific-product-media/{productId}', [ProductMediaController::class, 'getAllMediaForProduct']);
    Route::apiResource('addons', AddonController::class);
    // Route::post('update-addons/{id}', [AddonController::class, 'updateaddons']);

    // Admin specific route name for products
    Route::apiResource('products', ProductController::class)->names([
        'index' => 'admin.products.index',
        'show' => 'admin.products.show',
        'store' => 'admin.products.store',
        'update' => 'admin.products.update',
        'destroy' => 'admin.products.destroy'
    ]);

     Route::apiResource('milestone', MilestoneController::class)->names([
        'index' => 'admin.milestone.index',
        'show' => 'admin.milestone.show',
        'store' => 'admin.milestone.store',
        'update' => 'admin.milestone.update',
        'destroy' => 'admin.milestone.destroy'
    ]);

    Route::apiResource('product-addons', ProductAddonController::class);
    Route::get('projects/{projectId}/milestones', [MilestoneController::class, 'getMilestonesForProject'])
        ->name('admin.projects.milestones');


        Route::apiResource('tasks', TaskController::class)->names([
            'index' => 'admin.tasks.index',
            'show' => 'admin.tasks.show',
            'store' => 'admin.tasks.store',
            'update' => 'admin.tasks.update',
            'destroy' => 'admin.tasks.destroy'
        ]);
        Route::apiResource('sliders', SliderController::class);
        Route::apiResource('invoices', InvoiceController::class);
        Route::post('run-migrations', [MigrationController::class, 'runMigrations']);

});
