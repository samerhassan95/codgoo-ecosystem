<?php

use App\Http\Controllers\{DocumentTypeController,
    EarlyLeaveRequestController,
    Employee\EmployeeAuthController,
    EmployeeDocumentController,
    EmployeeMeetingController,
    ExtendTaskTimeRequestController,
    HolidayRequestController,
    MoneyRequestController,
    OvertimeRequestController,
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
    AttendanceController,
    TaskController};
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
    Route::apiResource('tasks', TaskController::class);
    Route::apiResource('requested-apis', RequestedApiController::class);
    Route::apiResource('implemented-apis', ImplementedApiController::class);
    Route::apiResource('implemented-api-reviews', ImplementedApiReviewController::class);
    Route::apiResource('achievements', AchievementController::class);
    Route::apiResource('attendances', AttendanceController::class);
    Route::get('employee-tasks', [TaskController::class, 'employeeTasks']);
    Route::get('ui-task-details/{id}', [TaskController::class, 'showTaskWithScreensforUI']);
    Route::get('front-task-details/{id}', [TaskController::class, 'showTaskWithScreensfront']);
    Route::post('employee-meetings', [EmployeeMeetingController::class, 'store']);
    Route::prefix('employee-documents')->group(function () {
    Route::get('/{employeeId}', [EmployeeDocumentController::class, 'index']);
    Route::post('/', [EmployeeDocumentController::class, 'store']);
    Route::get('/show/{id}', [EmployeeDocumentController::class, 'show']);
    Route::delete('/{id}', [EmployeeDocumentController::class, 'destroy']);
    });
    Route::get('document-types', [DocumentTypeController::class, 'index']);
    Route::get('holiday-request/my-requests', [HolidayRequestController::class, 'myRequests']);
    Route::get('remote-work-request/my-requests', [RemoteWorkRequestController::class, 'myRequests']);
    Route::get('early-leave-request/my-requests', [EarlyLeaveRequestController::class, 'myRequests']);
    Route::get('paper-request/my-requests', [PaperRequestController::class, 'myRequests']);
    Route::get('money-request/my-requests', [MoneyRequestController::class, 'myRequests']);
    Route::get('overtime-request/my-requests', [OvertimeRequestController::class, 'myRequests']);
    Route::apiResource('overtime-requests', OvertimeRequestController::class);
    Route::get('screen-details/{id}', [ScreenController::class, 'showWithRequestedApis']);
    Route::get('back-task-details/{id}', [TaskController::class, 'showTaskWithScreensback']);
    Route::post('implemented-apis/bulk-store', [ImplementedApiController::class, 'bulkStore']);
    Route::get('home-overview', [TaskController::class, 'homeOverview']);
});
