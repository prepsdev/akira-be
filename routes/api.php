<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OcrTemplateController;
use App\Http\Controllers\NamingRuleController;
use App\Http\Controllers\OcrProcessController;
use App\Http\Controllers\DataOcrController;
use App\Http\Controllers\FileProxyController;
use App\Http\Controllers\FieldSuggestionController;
use App\Http\Controllers\GlobalSettingController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\EmailScheduleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AutoUploadController;
use App\Http\Controllers\ReportController;

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    Route::apiResource('roles', RoleController::class);
    Route::apiResource('users', UserController::class);

    Route::post('data-ocr/{id}/approve', [DataOcrController::class, 'approve']);
    Route::post('data-ocr/{id}/reject', [DataOcrController::class, 'reject']);
});

Route::apiResource('data-ocr', DataOcrController::class)->except(['update', 'destroy']);
Route::apiResource('ocr-templates', OcrTemplateController::class);

Route::apiResource('naming-rules', NamingRuleController::class);
Route::get('naming-rules/template/{templateId}', [NamingRuleController::class, 'getByTemplate']);

Route::post('ocr/upload', [OcrProcessController::class, 'upload']);
Route::post('ocr/upload-auto', [OcrProcessController::class, 'uploadWithAutoCategorization']);

Route::post('field-suggestions', [FieldSuggestionController::class, 'getSuggestions']);

Route::get('settings', [GlobalSettingController::class, 'index']);
Route::post('settings/toggle', [GlobalSettingController::class, 'toggle']);
Route::post('settings/update', [GlobalSettingController::class, 'update']);
Route::post('settings/upload-logo', [GlobalSettingController::class, 'uploadLogo']);

Route::post('auto-upload/scan', [AutoUploadController::class, 'triggerScan']);

Route::post('export', [ExportController::class, 'export']);
Route::post('export/count', [ExportController::class, 'getDocumentCount']);

Route::apiResource('email-schedules', EmailScheduleController::class);
Route::post('email-schedules/{id}/toggle', [EmailScheduleController::class, 'toggle']);

Route::get('reports/user-stats', [ReportController::class, 'getUserActivityStats']);
Route::get('reports/today', [ReportController::class, 'getTodayStats']);
Route::get('reports/recent', [ReportController::class, 'getRecentActivity']);
Route::post('reports/date-range', [ReportController::class, 'getActivityByDateRange']);

Route::get('files/{path}', [FileProxyController::class, 'show'])->where('path', '.*');
