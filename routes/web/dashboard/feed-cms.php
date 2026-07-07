<?php

use App\Http\Controllers\Admin\FeedCmsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin', 'account.status:active'])->prefix('dashboard')->name('admin.dashboard.')->group(function (): void {
    Route::get('feed-cms', [FeedCmsController::class, 'index'])->name('feed-cms.index');
    Route::get('feed-cms/pages/create', [FeedCmsController::class, 'create'])->name('feed-cms.pages.create');
    Route::post('feed-cms/pages', [FeedCmsController::class, 'store'])->name('feed-cms.pages.store');
    Route::get('feed-cms/pages/{page}/edit', [FeedCmsController::class, 'edit'])->name('feed-cms.pages.edit');
    Route::put('feed-cms/pages/{page}', [FeedCmsController::class, 'update'])->name('feed-cms.pages.update');
    Route::post('feed-cms/pages/{page}/publish', [FeedCmsController::class, 'publish'])->name('feed-cms.pages.publish');
    Route::post('feed-cms/pages/{page}/archive', [FeedCmsController::class, 'archive'])->name('feed-cms.pages.archive');
    Route::post('feed-cms/pages/{page}/revisions/{pageRevision}/rollback', [FeedCmsController::class, 'rollback'])->name('feed-cms.pages.revisions.rollback');
    Route::put('feed-cms/priorities/{contentType}', [FeedCmsController::class, 'updatePriority'])->name('feed-cms.priorities.update');
});
