<?php

use App\Http\Controllers\Api\V1\MediaFileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth')->group(function (): void {
    Route::get('media-files', [MediaFileController::class, 'index']);
    Route::post('media-files', [MediaFileController::class, 'store']);
    Route::get('media-files/{mediaFile}', [MediaFileController::class, 'show']);
    Route::post('media-files/{mediaFile}/attach', [MediaFileController::class, 'attach']);
    Route::post('media-files/{mediaFile}/orphan', [MediaFileController::class, 'markOrphan']);
});
