<?php

use App\Http\Controllers\Api\V1\ConnectionRequestController;
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
    Route::put('profile/verification-intent', [ProfileController::class, 'requestVerification']);
    Route::get('profile/unverified', [ProfileController::class, 'myUnverifiedProfile']);
    Route::put('profile/unverified', [ProfileController::class, 'upsertUnverifiedProfile']);
    Route::get('profiles/engineers/{engineerProfile}', [ProfileController::class, 'showEngineerProfile']);
    Route::get('profiles/unverified-members/{unverifiedMemberProfile}', [ProfileController::class, 'showUnverifiedProfile']);

    Route::get('expert-directory', [ExpertDirectoryController::class, 'index']);
});
Route::prefix('v1')->middleware('auth:sanctum')->name('api.v1.')->group(function (): void {
    Route::get('connection-requests', [ConnectionRequestController::class, 'index'])->name('connection-requests.index');
    Route::post('connection-requests', [ConnectionRequestController::class, 'store'])->name('connection-requests.store');
    Route::get('connection-requests/{connectionRequest}', [ConnectionRequestController::class, 'show'])->name('connection-requests.show');
    Route::post('connection-requests/{connectionRequest}/cancel', [ConnectionRequestController::class, 'cancel'])->name('connection-requests.cancel');
    Route::post('connection-requests/{connectionRequest}/approve', [ConnectionRequestController::class, 'approve'])->name('connection-requests.approve');
    Route::post('connection-requests/{connectionRequest}/reject', [ConnectionRequestController::class, 'reject'])->name('connection-requests.reject');
});
