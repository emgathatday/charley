<?php

use App\Http\Controllers\Api\V1\ConnectionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->name('api.v1.')->group(function (): void {
    Route::get('connections', [ConnectionController::class, 'index'])->name('connections.index');
    Route::post('connections', [ConnectionController::class, 'store'])->name('connections.store');
    Route::get('connections/{connection}', [ConnectionController::class, 'show'])->name('connections.show');
    Route::get('connections/{connection}/messaging-eligibility', [ConnectionController::class, 'messagingEligibility'])->name('connections.messaging-eligibility');
    Route::post('connections/{connection}/accept', [ConnectionController::class, 'accept'])->name('connections.accept');
    Route::post('connections/{connection}/decline', [ConnectionController::class, 'decline'])->name('connections.decline');
    Route::post('connections/{connection}/block', [ConnectionController::class, 'block'])->name('connections.block');
});
