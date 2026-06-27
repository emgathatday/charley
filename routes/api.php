<?php

use App\Http\Controllers\Api\V1\AccountSecurityController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ConnectionController;
use App\Http\Controllers\Api\V1\ExpertDirectoryController;
use App\Http\Controllers\Api\V1\MediaFileController;
use App\Http\Controllers\Api\V1\ProfileActivityController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SocialAccountController;
use App\Http\Controllers\Api\V1\VerificationRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/login-tokens', [AuthController::class, 'issueLoginToken']);
    Route::post('auth/login-tokens/consume', [AuthController::class, 'consumeLoginToken']);

    Route::middleware('auth')->group(function (): void {
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

        Route::get('profile/activity', [ProfileActivityController::class, 'activity']);
        Route::get('profile/metas', [ProfileActivityController::class, 'metas']);
        Route::put('profile/metas', [ProfileActivityController::class, 'upsertMeta']);
        Route::get('profile/engineer', [ProfileController::class, 'myEngineerProfile']);
        Route::put('profile/engineer', [ProfileController::class, 'upsertEngineerProfile']);
        Route::get('profile/unverified', [ProfileController::class, 'myUnverifiedProfile']);
        Route::put('profile/unverified', [ProfileController::class, 'upsertUnverifiedProfile']);
        Route::get('profiles/engineers/{engineerProfile}', [ProfileController::class, 'showEngineerProfile']);
        Route::get('profiles/unverified-members/{unverifiedMemberProfile}', [ProfileController::class, 'showUnverifiedProfile']);

        Route::get('connections', [ConnectionController::class, 'index']);
        Route::post('connections', [ConnectionController::class, 'store']);
        Route::post('connections/{connection}/accept', [ConnectionController::class, 'accept']);
        Route::post('connections/{connection}/decline', [ConnectionController::class, 'decline']);
        Route::post('connections/{connection}/block', [ConnectionController::class, 'block']);

        Route::get('expert-directory', [ExpertDirectoryController::class, 'index']);

        Route::get('media-files', [MediaFileController::class, 'index']);
        Route::post('media-files', [MediaFileController::class, 'store']);
        Route::get('media-files/{mediaFile}', [MediaFileController::class, 'show']);
        Route::post('media-files/{mediaFile}/attach', [MediaFileController::class, 'attach']);
        Route::post('media-files/{mediaFile}/orphan', [MediaFileController::class, 'markOrphan']);
    });
});



