<?php

use App\Http\Controllers\Api\V1\TaxonomyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth')->group(function (): void {
    Route::get('taxonomy/tags', [TaxonomyController::class, 'index']);
    Route::get('taxonomy/tags/search', [TaxonomyController::class, 'search']);
    Route::post('taxonomy/tags', [TaxonomyController::class, 'store']);
    Route::get('taxonomy/tags/{tag}', [TaxonomyController::class, 'show']);
    Route::put('taxonomy/tags/{tag}', [TaxonomyController::class, 'update']);
    Route::delete('taxonomy/tags/{tag}', [TaxonomyController::class, 'destroy']);
    Route::post('taxonomy/tags/sync', [TaxonomyController::class, 'sync']);
});
