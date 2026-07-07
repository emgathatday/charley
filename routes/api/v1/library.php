<?php

use App\Http\Controllers\Api\V1\LibraryAccessRuleController;
use App\Http\Controllers\Api\V1\LibraryController;
use App\Models\LibraryAccessRule;
use App\Models\LibraryItem;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('library/categories', [LibraryController::class, 'categories']);
    Route::get('library/items', [LibraryController::class, 'index']);
    Route::get('library/items/{libraryItem}', [LibraryController::class, 'show']);
});

Route::prefix('v1')->middleware('auth')->group(function (): void {
    Route::get('library/admin/items', [LibraryController::class, 'adminIndex'])->can('manage', LibraryItem::class);
    Route::post('library/admin/items', [LibraryController::class, 'store'])->can('manage', LibraryItem::class);
    Route::put('library/admin/items/{libraryItem}', [LibraryController::class, 'update'])->can('manage', 'libraryItem');
    Route::post('library/admin/items/{libraryItem}/approve', [LibraryController::class, 'approve'])->can('approve', 'libraryItem');
    Route::post('library/admin/items/{libraryItem}/archive', [LibraryController::class, 'archive'])->can('archive', 'libraryItem');
    Route::get('library/admin/ai-trainable', [LibraryController::class, 'aiTrainable'])->can('manage', LibraryItem::class);
    Route::get('library/access-rules', [LibraryAccessRuleController::class, 'index'])->can('manage', LibraryAccessRule::class);
    Route::post('library/access-rules', [LibraryAccessRuleController::class, 'store'])->can('manage', LibraryAccessRule::class);
    Route::put('library/access-rules/{libraryAccessRule}', [LibraryAccessRuleController::class, 'update'])->can('manage', 'libraryAccessRule');
    Route::post('library/items/{libraryItem}/access-check', [LibraryController::class, 'accessCheck'])->can('view', 'libraryItem');
    Route::post('library/items/{libraryItem}/access-logs', [LibraryController::class, 'recordAccess'])->can('view', 'libraryItem');
});
