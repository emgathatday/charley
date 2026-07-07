<?php

use App\Http\Controllers\Api\V1\ConnectionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth')->group(function (): void {
    Route::get('connections', [ConnectionController::class, 'index']);
    Route::post('connections', [ConnectionController::class, 'store']);
    Route::post('connections/{connection}/accept', [ConnectionController::class, 'accept']);
    Route::post('connections/{connection}/decline', [ConnectionController::class, 'decline']);
    Route::post('connections/{connection}/block', [ConnectionController::class, 'block']);
});
