<?php

use App\Http\Controllers\Admin\IamSecurityController;
use App\Http\Controllers\Admin\IamUserController;
use App\Http\Controllers\Admin\IamVerificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin', 'account.status:active'])->prefix('dashboard')->name('admin.dashboard.')->group(function (): void {
    Route::get('iam/users', [IamUserController::class, 'index'])->name('iam.users');
    Route::get('iam/verification-queue', [IamVerificationController::class, 'index'])->name('iam.verification-queue');
    Route::post('iam/verification-queue/{verificationRequest}/approve', [IamVerificationController::class, 'approve'])->name('iam.verification-queue.approve');
    Route::post('iam/verification-queue/{verificationRequest}/reject', [IamVerificationController::class, 'reject'])->name('iam.verification-queue.reject');
    Route::post('iam/verification-queue/{verificationRequest}/more-info', [IamVerificationController::class, 'requestMoreInfo'])->name('iam.verification-queue.more-info');
    Route::get('iam/user-security/{user?}', [IamSecurityController::class, 'show'])->name('iam.user-security');
    Route::put('iam/user-security/{user}', [IamSecurityController::class, 'update'])->name('iam.user-security.update');
});
