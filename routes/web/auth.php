<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\Api\V1\AccountSecurityController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('admin.login.store');
    Route::post('login/mfa', [AdminAuthController::class, 'loginMfa'])->name('admin.login.mfa');
});

Route::post('logout', [AdminAuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::prefix('api/v1')->middleware('auth')->group(function (): void {
    Route::post('account/security/mfa/setup', [AccountSecurityController::class, 'setupMfa']);
    Route::post('account/security/mfa/confirm', [AccountSecurityController::class, 'enableMfa']);
    Route::post('account/security/mfa/disable', [AccountSecurityController::class, 'disableMfa']);
});
