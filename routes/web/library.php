<?php

use App\Http\Controllers\LibraryPageController;
use Illuminate\Support\Facades\Route;

Route::get('library', [LibraryPageController::class, 'index'])->name('library.index');
Route::get('library/categories/{category}', [LibraryPageController::class, 'category'])->name('library.categories.show');
Route::get('library/items/{libraryItem}', [LibraryPageController::class, 'show'])->name('library.items.show');
Route::get('library/items/{libraryItem}/preview', [LibraryPageController::class, 'preview'])->name('library.items.preview');
Route::middleware('auth')->group(function (): void {
    Route::post('library/items/{libraryItem}/view', [LibraryPageController::class, 'recordView'])->name('library.items.record-view');
    Route::post('library/items/{libraryItem}/download', [LibraryPageController::class, 'download'])->name('library.items.download');
});
