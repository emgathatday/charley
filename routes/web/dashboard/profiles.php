<?php

use App\Http\Controllers\Admin\ConnectionRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin', 'account.status:active'])->prefix('dashboard')->name('admin.dashboard.')->group(function (): void {
    Route::get('profiles/connection-requests', [ConnectionRequestController::class, 'index'])->name('profiles.connection-requests.index');
    Route::post('profiles/connection-requests/{connectionRequest}/approve', [ConnectionRequestController::class, 'approve'])->name('profiles.connection-requests.approve');
    Route::post('profiles/connection-requests/{connectionRequest}/reject', [ConnectionRequestController::class, 'reject'])->name('profiles.connection-requests.reject');
});
