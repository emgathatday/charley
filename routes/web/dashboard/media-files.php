<?php

use App\Http\Controllers\Admin\MediaFileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin', 'account.status:active'])->prefix('dashboard')->name('admin.dashboard.')->group(function (): void {
    Route::get('media-files', [MediaFileController::class, 'index'])->name('media-files.index');
    Route::post('media-files', [MediaFileController::class, 'store'])->name('media-files.store');
    Route::get('media-files/{mediaFile}', [MediaFileController::class, 'show'])->name('media-files.show');
});
