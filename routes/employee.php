<?php

use App\Http\Controllers\{
    EarlyLeaveRequestController,
    Employee\EmployeeAuthController,
    ExtendTaskTimeRequestController,
    HolidayRequestController,
    MoneyRequestController,
    PaperRequestController,
    RemoteWorkRequestController,
    TaskAssignmentController,
    ProjectGeneralNoteController,
    AddressController,
    ScreenReviewController,
    ScreenController,
    RequestedApiController,
    ImplementedApiController,
    ImplementedApiReviewController,
    AchievementController,
    AttendanceController
};
use Illuminate\Support\Facades\Route;

Route::middleware('auth:employee')->group(function () {


    Route::get('profile', [EmployeeAuthController::class, 'getProfile']);

    Route::post('update-profile', [EmployeeAuthController::class, 'updateProfile']);

    Route::post('change-password', [EmployeeAuthController::class, 'changePassword']);
    Route::post('verify-change-phone', [EmployeeAuthController::class, 'verifyChangePhone']);
    Route::post('change-phone-request', [EmployeeAuthController::class, 'changePhoneRequest']);
    Route::apiResource('holiday-requests', HolidayRequestController::class);
    Route::apiResource('remote-work-requests', RemoteWorkRequestController::class);
    Route::apiResource('early-leave-requests', EarlyLeaveRequestController::class);
    Route::apiResource('paper-requests', PaperRequestController::class);
    Route::apiResource('money-requests', MoneyRequestController::class);
    Route::apiResource('extend-task-time-requests', ExtendTaskTimeRequestController::class);
    Route::apiResource('task-assignments', TaskAssignmentController::class);
    Route::apiResource('project-general-notes', ProjectGeneralNoteController::class);
    Route::apiResource('addresses', AddressController::class);
    Route::apiResource('screen-reviews', ScreenReviewController::class);
    Route::apiResource('screens', ScreenController::class);
    Route::apiResource('requested-apis', RequestedApiController::class);
    Route::apiResource('implemented-apis', ImplementedApiController::class);
    Route::apiResource('implemented-api-reviews', ImplementedApiReviewController::class);
    Route::apiResource('achievements', AchievementController::class);
    Route::apiResource('attendances', AttendanceController::class);


});
