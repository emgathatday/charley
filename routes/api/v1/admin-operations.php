<?php

use App\Http\Controllers\Api\V1\AccountPenaltyController;
use App\Http\Controllers\Api\V1\AdminIntegrationController;
use App\Http\Controllers\Api\V1\ContentApprovalQueueController;
use App\Http\Controllers\Api\V1\PlatformSettingController;
use App\Http\Controllers\Api\V1\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth')->group(function (): void {
    Route::get('account-penalties', [AccountPenaltyController::class, 'index']);
    Route::post('account-penalties', [AccountPenaltyController::class, 'store']);
    Route::get('account-penalties/{accountPenalty}', [AccountPenaltyController::class, 'show']);
    Route::post('account-penalties/{accountPenalty}/end', [AccountPenaltyController::class, 'end']);

    Route::get('support-tickets', [SupportTicketController::class, 'index']);
    Route::post('support-tickets', [SupportTicketController::class, 'store']);
    Route::get('support-tickets/{supportTicket}', [SupportTicketController::class, 'show']);
    Route::post('support-tickets/{supportTicket}/assign', [SupportTicketController::class, 'assign']);
    Route::post('support-tickets/{supportTicket}/replies', [SupportTicketController::class, 'reply']);
    Route::post('support-tickets/{supportTicket}/resolve', [SupportTicketController::class, 'resolve']);

    Route::get('platform-settings', [PlatformSettingController::class, 'index']);
    Route::post('platform-settings', [PlatformSettingController::class, 'store']);
    Route::get('platform-settings/{platformSetting}', [PlatformSettingController::class, 'show']);
    Route::put('platform-settings/{platformSetting}', [PlatformSettingController::class, 'update']);

    Route::get('admin-integrations', [AdminIntegrationController::class, 'index']);
    Route::post('admin-integrations', [AdminIntegrationController::class, 'store']);
    Route::get('admin-integrations/{adminIntegration}', [AdminIntegrationController::class, 'show']);
    Route::delete('admin-integrations/{adminIntegration}', [AdminIntegrationController::class, 'destroy']);

    Route::get('content-approvals', [ContentApprovalQueueController::class, 'index']);
    Route::get('content-approvals/{contentApprovalQueue}', [ContentApprovalQueueController::class, 'show']);
    Route::post('content-approvals/{contentApprovalQueue}/assign', [ContentApprovalQueueController::class, 'assign']);
    Route::post('content-approvals/{contentApprovalQueue}/approve', [ContentApprovalQueueController::class, 'approve']);
    Route::post('content-approvals/{contentApprovalQueue}/reject', [ContentApprovalQueueController::class, 'reject']);
});
