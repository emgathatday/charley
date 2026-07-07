<?php

use App\Http\Controllers\Api\V1\FeedCmsPageController;
use App\Http\Controllers\Api\V1\FeedController;
use App\Http\Controllers\Api\V1\FeedPriorityController;
use App\Http\Controllers\Api\V1\PageRevisionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('feed-cms/pages', [FeedCmsPageController::class, 'publicIndex']);
    Route::get('feed-cms/pages/{slug}', [FeedCmsPageController::class, 'publicShow']);
});

Route::prefix('v1')->middleware('auth')->group(function (): void {
    Route::get('feed-cms/feed', [FeedController::class, 'index']);
    Route::post('feed-cms/feed-cache/{userFeedCache}/seen', [FeedController::class, 'markSeen']);
    Route::get('feed-cms/feed-priorities', [FeedPriorityController::class, 'index']);
    Route::put('feed-cms/feed-priorities', [FeedPriorityController::class, 'update']);
    Route::get('feed-cms/admin/pages', [FeedCmsPageController::class, 'index']);
    Route::post('feed-cms/admin/pages', [FeedCmsPageController::class, 'store']);
    Route::get('feed-cms/admin/pages/{page}', [FeedCmsPageController::class, 'show']);
    Route::put('feed-cms/admin/pages/{page}', [FeedCmsPageController::class, 'update']);
    Route::post('feed-cms/admin/pages/{page}/publish', [FeedCmsPageController::class, 'publish']);
    Route::post('feed-cms/admin/pages/{page}/archive', [FeedCmsPageController::class, 'archive']);
    Route::get('feed-cms/admin/pages/{page}/revisions', [PageRevisionController::class, 'index']);
    Route::post('feed-cms/admin/pages/{page}/revisions/{pageRevision}/rollback', [PageRevisionController::class, 'rollback']);
});
