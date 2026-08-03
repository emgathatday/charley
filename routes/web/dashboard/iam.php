<?php

use App\Http\Controllers\Admin\IamSecurityController;
use App\Http\Controllers\Admin\IamUserController;
use App\Http\Controllers\Admin\IamVerificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin', 'account.status:active'])->prefix('dashboard')->name('admin.dashboard.')->group(function (): void {
    Route::get('iam/users', [IamUserController::class, 'index'])->name('iam.users');
    Route::get('iam/users/engineers', [IamUserController::class, 'engineers'])->name('iam.users.engineers');
    Route::get('iam/users/partners', [IamUserController::class, 'partners'])->name('iam.users.partners');
    Route::get('iam/users/create/admin', [IamUserController::class, 'createAdmin'])->name('iam.users.create-admin');
    Route::post('iam/users/create/admin', [IamUserController::class, 'storeAdmin'])->name('iam.users.store-admin');
    Route::get('iam/users/create/engineer', [IamUserController::class, 'createEngineer'])->name('iam.users.create-engineer');
    Route::post('iam/users/create/engineer', [IamUserController::class, 'storeEngineer'])->name('iam.users.store-engineer');
    Route::get('iam/users/{user}/edit/engineer', [IamUserController::class, 'editEngineer'])->name('iam.users.edit-engineer');
    Route::put('iam/users/{user}/edit/engineer', [IamUserController::class, 'updateEngineer'])->name('iam.users.update-engineer');
    Route::get('iam/users/create/partner', [IamUserController::class, 'createPartner'])->name('iam.users.create-partner');
    Route::post('iam/users/create/partner', [IamUserController::class, 'storePartner'])->name('iam.users.store-partner');
    Route::get('iam/users/{user}/edit/partner', [IamUserController::class, 'editPartner'])->name('iam.users.edit-partner');
    Route::put('iam/users/{user}/edit/partner', [IamUserController::class, 'updatePartner'])->name('iam.users.update-partner');
    Route::get('iam/my-profile', [IamUserController::class, 'adminProfile'])->name('iam.users.admin-profile');
    Route::delete('iam/my-profile/sessions/{session}', [IamUserController::class, 'revokeAdminProfileSession'])->name('iam.users.admin-profile.sessions.revoke');
    Route::delete('iam/my-profile/sessions', [IamUserController::class, 'revokeOtherAdminProfileSessions'])->name('iam.users.admin-profile.sessions.revoke-others');
    Route::get('iam/users/{user}', [IamUserController::class, 'show'])->name('iam.users.show');
    Route::get('iam/verification-queue', [IamVerificationController::class, 'index'])->name('iam.verification-queue');
    Route::get('iam/verification-queue/{verificationRequest}', [IamVerificationController::class, 'show'])->name('iam.verification-queue.show');
    Route::post('iam/verification-queue/{verificationRequest}/approve', [IamVerificationController::class, 'approve'])->name('iam.verification-queue.approve');
    Route::post('iam/verification-queue/{verificationRequest}/reject', [IamVerificationController::class, 'reject'])->name('iam.verification-queue.reject');
    Route::post('iam/verification-queue/{verificationRequest}/more-info', [IamVerificationController::class, 'requestMoreInfo'])->name('iam.verification-queue.more-info');
    Route::get('iam/account-penalty-freeze', [IamSecurityController::class, 'show'])->name('iam.account-penalty-freeze');
    Route::get('iam/account-penalty-freeze/{user}', [IamSecurityController::class, 'show'])->name('iam.account-penalty-freeze.show');
    Route::put('iam/account-penalty-freeze/{user}', [IamSecurityController::class, 'update'])->name('iam.account-penalty-freeze.update');
});
