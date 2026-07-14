<?php

use App\Http\Controllers\Api\V1\HandbookArticleController;
use App\Http\Controllers\Api\V1\HandbookCategoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('handbook/categories', [HandbookCategoryController::class, 'index'])->name('handbook.categories.index');
    Route::get('handbook/categories/tree', [HandbookCategoryController::class, 'tree'])->name('handbook.categories.tree');
    Route::get('handbook/categories/hotspots', [HandbookCategoryController::class, 'hotspots'])->name('handbook.categories.hotspots');
    Route::get('handbook/categories/{handbookCategory}', [HandbookCategoryController::class, 'show'])
        ->can('view', 'handbookCategory')
        ->name('handbook.categories.show');

    Route::get('handbook/articles', [HandbookArticleController::class, 'index'])->name('handbook.articles.index');
    Route::get('handbook/articles/{handbookArticle}', [HandbookArticleController::class, 'show'])
        ->can('view', 'handbookArticle')
        ->name('handbook.articles.show');
    Route::get('handbook/articles/{handbookArticle}/metadata', [HandbookArticleController::class, 'metadata'])
        ->can('view', 'handbookArticle')
        ->name('handbook.articles.metadata');
    Route::get('handbook/articles/{handbookArticle}/related-items', [HandbookArticleController::class, 'relatedItems'])
        ->can('view', 'handbookArticle')
        ->name('handbook.articles.related-items');

    Route::middleware('auth')->group(function (): void {
        Route::post('handbook/articles/{handbookArticle}/publish', [HandbookArticleController::class, 'publish'])
            ->can('publish', 'handbookArticle')
            ->name('handbook.articles.publish');
        Route::post('handbook/articles/{handbookArticle}/related-items', [HandbookArticleController::class, 'linkRelatedItem'])
            ->can('linkRelatedItem', 'handbookArticle')
            ->name('handbook.articles.related-items.store');
    });
});