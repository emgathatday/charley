<?php

use App\Http\Controllers\Api\V1\ExpertDirectoryController;
use App\Http\Controllers\Api\V1\ProfileActivityController;
use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth')->group(function (): void {
    Route::get('profile/activity', [ProfileActivityController::class, 'activity']);
    Route::get('profile/metas', [ProfileActivityController::class, 'metas']);
    Route::put('profile/metas', [ProfileActivityController::class, 'upsertMeta']);
    Route::get('profile/engineer', [ProfileController::class, 'myEngineerProfile']);
    Route::put('profile/engineer', [ProfileController::class, 'upsertEngineerProfile']);
    Route::get('profile/unverified', [ProfileController::class, 'myUnverifiedProfile']);
    Route::put('profile/unverified', [ProfileController::class, 'upsertUnverifiedProfile']);
    Route::get('profiles/engineers/{engineerProfile}', [ProfileController::class, 'showEngineerProfile']);
    Route::get('profiles/unverified-members/{unverifiedMemberProfile}', [ProfileController::class, 'showUnverifiedProfile']);

    Route::get('expert-directory', [ExpertDirectoryController::class, 'index']);
});
