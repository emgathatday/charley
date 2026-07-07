<?php

use App\Http\Controllers\Admin\LibraryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin', 'account.status:active'])->prefix('dashboard')->name('admin.dashboard.')->group(function (): void {
    Route::get('library', [LibraryController::class, 'index'])->name('library.index');
    Route::get('library/categories', [LibraryController::class, 'categories'])->name('library.categories');
    Route::get('library/items', [LibraryController::class, 'items'])->name('library.items');
    Route::get('library/approvals', [LibraryController::class, 'approvals'])->name('library.approvals');
    Route::get('library/access-rules', [LibraryController::class, 'accessRules'])->name('library.access-rules');
    Route::get('library/access-logs', [LibraryController::class, 'accessLogs'])->name('library.access-logs');
    Route::get('library/upload-metadata', [LibraryController::class, 'uploadMetadata'])->name('library.upload-metadata');
});
