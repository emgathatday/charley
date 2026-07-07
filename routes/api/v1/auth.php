<?php

use App\Http\Controllers\Api\V1\AccountSecurityController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\SocialAccountController;
use App\Http\Controllers\Api\V1\VerificationRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/login-tokens', [AuthController::class, 'issueLoginToken']);
    Route::post('auth/login-tokens/consume', [AuthController::class, 'consumeLoginToken']);
});

Route::prefix('v1')->middleware('auth')->group(function (): void {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::post('account/security/failed-login', [AccountSecurityController::class, 'recordFailedLogin']);
    Route::post('account/security/mfa', [AccountSecurityController::class, 'enableMfa']);
    Route::post('account/security/freeze', [AccountSecurityController::class, 'freeze']);

    Route::post('social-accounts', [SocialAccountController::class, 'store']);
    Route::delete('social-accounts/{socialAccount}', [SocialAccountController::class, 'destroy']);

    Route::get('verification-requests', [VerificationRequestController::class, 'index']);
    Route::post('verification-requests', [VerificationRequestController::class, 'store']);
    Route::post('verification-requests/{verificationRequest}/approve', [VerificationRequestController::class, 'approve']);
    Route::post('verification-requests/{verificationRequest}/reject', [VerificationRequestController::class, 'reject']);
});
