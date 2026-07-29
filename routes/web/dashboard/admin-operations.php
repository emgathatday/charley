<?php

use App\Http\Controllers\Admin\AdminOperationsController;
use App\Http\Controllers\Admin\PlatformSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin', 'account.status:active'])->prefix('dashboard')->name('admin.dashboard.')->group(function (): void {
    Route::get('admin-operations', [AdminOperationsController::class, 'index'])->name('admin-operations.index');
    Route::get('admin-operations/support-tickets/create', [AdminOperationsController::class, 'createTicket'])->name('admin-operations.support-tickets.create');
    Route::post('admin-operations/support-tickets', [AdminOperationsController::class, 'storeTicket'])->name('admin-operations.support-tickets.store');
    Route::post('admin-operations/support-tickets/{supportTicket}/replies', [AdminOperationsController::class, 'replyTicket'])->name('admin-operations.support-tickets.replies.store');
    Route::post('admin-operations/support-tickets/{supportTicket}/resolve', [AdminOperationsController::class, 'resolveTicket'])->name('admin-operations.support-tickets.resolve');
    Route::get('platform-settings', [PlatformSettingsController::class, 'index'])->name('platform-settings.index');
    Route::post('platform-settings', [PlatformSettingsController::class, 'store'])->name('platform-settings.store');
    Route::get('admin-operations/platform-settings/edit/{platformSetting?}', fn () => redirect()->route('admin.dashboard.platform-settings.index'))->name('admin-operations.platform-settings.edit');
    Route::post('admin-operations/platform-settings', [AdminOperationsController::class, 'storeSetting'])->name('admin-operations.platform-settings.store');
    Route::get('admin-operations/admin-integrations/create', [AdminOperationsController::class, 'createIntegration'])->name('admin-operations.admin-integrations.create');
    Route::post('admin-operations/admin-integrations', [AdminOperationsController::class, 'storeIntegration'])->name('admin-operations.admin-integrations.store');
    Route::post('admin-operations/content-approvals/{contentApprovalQueue}/assign', [AdminOperationsController::class, 'assignContent'])->name('admin-operations.content-approvals.assign');
    Route::post('admin-operations/content-approvals/{contentApprovalQueue}/approve', [AdminOperationsController::class, 'approveContent'])->name('admin-operations.content-approvals.approve');
    Route::post('admin-operations/content-approvals/{contentApprovalQueue}/reject', [AdminOperationsController::class, 'rejectContent'])->name('admin-operations.content-approvals.reject');
});
