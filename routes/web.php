<?php

use App\Http\Controllers\Admin\IamSecurityController;
use App\Http\Controllers\Admin\IamUserController;
use App\Http\Controllers\Admin\IamVerificationController;
use App\Http\Controllers\Admin\MediaFileController;
use App\Http\Controllers\AdminAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('admin.login.store');
});

Route::post('logout', [AdminAuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:admin', 'account.status:active'])->prefix('dashboard')->name('admin.dashboard.')->group(function (): void {
    Route::get('iam/users', [IamUserController::class, 'index'])->name('iam.users');
    Route::get('iam/verification-queue', [IamVerificationController::class, 'index'])->name('iam.verification-queue');
    Route::post('iam/verification-queue/{verificationRequest}/approve', [IamVerificationController::class, 'approve'])->name('iam.verification-queue.approve');
    Route::post('iam/verification-queue/{verificationRequest}/reject', [IamVerificationController::class, 'reject'])->name('iam.verification-queue.reject');
    Route::post('iam/verification-queue/{verificationRequest}/more-info', [IamVerificationController::class, 'requestMoreInfo'])->name('iam.verification-queue.more-info');
    Route::get('iam/user-security/{user?}', [IamSecurityController::class, 'show'])->name('iam.user-security');
    Route::put('iam/user-security/{user}', [IamSecurityController::class, 'update'])->name('iam.user-security.update');
    Route::get('media-files', [MediaFileController::class, 'index'])->name('media-files.index');
    Route::post('media-files', [MediaFileController::class, 'store'])->name('media-files.store');
    Route::get('media-files/{mediaFile}', [MediaFileController::class, 'show'])->name('media-files.show');
});

