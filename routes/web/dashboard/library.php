<?php

use App\Http\Controllers\Admin\KnowledgeDomainPageController;
use App\Http\Controllers\Admin\LibraryItemPageController;
use App\Http\Controllers\Admin\RankTierPageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin', 'account.status:active'])->prefix('dashboard')->name('admin.dashboard.')->group(function (): void {
    Route::get('library/items', [LibraryItemPageController::class, 'index'])->name('library.items.index');
    Route::get('library/items/create', [LibraryItemPageController::class, 'create'])->name('library.items.create');
    Route::get('library/items/{libraryItem}', [LibraryItemPageController::class, 'show'])->name('library.items.show');
    Route::get('library/items/{libraryItem}/edit', [LibraryItemPageController::class, 'edit'])->name('library.items.edit');

    Route::get('library/knowledge-domains', [KnowledgeDomainPageController::class, 'index'])->name('library.knowledge-domains.index');
    Route::get('library/knowledge-domains/create', [KnowledgeDomainPageController::class, 'create'])->name('library.knowledge-domains.create');
    Route::post('library/knowledge-domains', [KnowledgeDomainPageController::class, 'store'])->name('library.knowledge-domains.store');
    Route::get('library/knowledge-domains/{knowledgeDomain}/edit', [KnowledgeDomainPageController::class, 'edit'])->name('library.knowledge-domains.edit');
    Route::put('library/knowledge-domains/{knowledgeDomain}', [KnowledgeDomainPageController::class, 'update'])->name('library.knowledge-domains.update');
    Route::get('library/knowledge-domains/{knowledgeDomain}/questions/create', [KnowledgeDomainPageController::class, 'createQuestion'])->name('library.knowledge-domains.questions.create');
    Route::post('library/knowledge-domains/{knowledgeDomain}/questions', [KnowledgeDomainPageController::class, 'storeQuestion'])->name('library.knowledge-domains.questions.store');
    Route::get('library/knowledge-domains/{knowledgeDomain}/questions/{quizQuestion}/edit', [KnowledgeDomainPageController::class, 'editQuestion'])->name('library.knowledge-domains.questions.edit');
    Route::put('library/knowledge-domains/{knowledgeDomain}/questions/{quizQuestion}', [KnowledgeDomainPageController::class, 'updateQuestion'])->name('library.knowledge-domains.questions.update');
    Route::post('library/knowledge-domains/{knowledgeDomain}/questions/{quizQuestion}/clone', [KnowledgeDomainPageController::class, 'cloneQuestion'])->name('library.knowledge-domains.questions.clone');
    Route::delete('library/knowledge-domains/{knowledgeDomain}/questions/{quizQuestion}', [KnowledgeDomainPageController::class, 'destroyQuestion'])->name('library.knowledge-domains.questions.destroy');

    Route::get('library/rank-tiers', [RankTierPageController::class, 'index'])->name('library.rank-tiers.index');
    Route::get('library/rank-tiers/create', [RankTierPageController::class, 'create'])->name('library.rank-tiers.create');
    Route::get('library/rank-tiers/{rankTier}/edit', [RankTierPageController::class, 'edit'])->name('library.rank-tiers.edit');
    Route::put('library/rank-tiers/{rankTier}', [RankTierPageController::class, 'update'])->name('library.rank-tiers.update');
    Route::post('library/rank-tiers/{rankTier}/clone', [RankTierPageController::class, 'clone'])->name('library.rank-tiers.clone');
    Route::patch('library/rank-tiers/{rankTier}/status', [RankTierPageController::class, 'updateStatus'])->name('library.rank-tiers.status');
    Route::delete('library/rank-tiers/{rankTier}', [RankTierPageController::class, 'destroy'])->name('library.rank-tiers.destroy');
});
