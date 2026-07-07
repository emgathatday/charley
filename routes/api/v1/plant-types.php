<?php

use App\Http\Controllers\Api\V1\PlantTypeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth')->group(function (): void {
    Route::get('plant-types', [PlantTypeController::class, 'index']);
    Route::post('plant-types', [PlantTypeController::class, 'store']);
    Route::get('plant-types/{plantType}', [PlantTypeController::class, 'show']);
    Route::put('plant-types/{plantType}', [PlantTypeController::class, 'update']);
    Route::delete('plant-types/{plantType}', [PlantTypeController::class, 'destroy']);
});
