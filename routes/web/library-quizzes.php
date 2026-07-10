<?php

use App\Http\Controllers\Admin\KnowledgeDomainController;
use App\Http\Controllers\Admin\LibraryItemHotspotController;
use App\Http\Controllers\Admin\LibraryQuizController;
use App\Http\Controllers\LibraryQuizPageController;
use Illuminate\Support\Facades\Route;

Route::get('library/quizzes', [LibraryQuizPageController::class, 'index'])->name('library.quizzes.index');
Route::get('library/quizzes/{quiz}', [LibraryQuizPageController::class, 'show'])->name('library.quizzes.show');
Route::get('library/items/{libraryItem}/hotspots', [LibraryQuizPageController::class, 'hotspots'])->name('library.hotspots.show');

Route::middleware('auth')->group(function (): void {
    Route::post('library/quizzes/{quiz}/submit', [LibraryQuizPageController::class, 'submit'])->name('library.quizzes.submit');
    Route::get('library/quiz-attempts/{quizAttempt}', [LibraryQuizPageController::class, 'result'])->name('library.quizzes.result');
    Route::get('library/domain-ranks', [LibraryQuizPageController::class, 'ranks'])->name('library.domain-ranks.index');
});

Route::middleware(['auth', 'role:admin', 'account.status:active'])->prefix('dashboard')->name('admin.dashboard.')->group(function (): void {
    Route::get('library/knowledge-domains', [KnowledgeDomainController::class, 'index'])->name('library.knowledge-domains.index');
    Route::post('library/knowledge-domains', [KnowledgeDomainController::class, 'store'])->name('library.knowledge-domains.store');
    Route::put('library/knowledge-domains/{knowledgeDomain}', [KnowledgeDomainController::class, 'update'])->name('library.knowledge-domains.update');
    Route::post('library/knowledge-domains/{knowledgeDomain}/archive', [KnowledgeDomainController::class, 'archive'])->name('library.knowledge-domains.archive');
    Route::post('library/knowledge-domains/{knowledgeDomain}/rank-tiers', [KnowledgeDomainController::class, 'storeRankTier'])->name('library.knowledge-domains.rank-tiers.store');
    Route::put('library/domain-rank-tiers/{domainRankTier}', [KnowledgeDomainController::class, 'updateRankTier'])->name('library.domain-rank-tiers.update');
    Route::delete('library/domain-rank-tiers/{domainRankTier}', [KnowledgeDomainController::class, 'destroyRankTier'])->name('library.domain-rank-tiers.destroy');

    // Backward-compatible Library menu route; served by Knowledge Domain v2, not the old expertise-rank scope.
    Route::get('library/expertise-ranks', [KnowledgeDomainController::class, 'index'])->name('library.expertise-ranks.index');

    Route::get('library/quizzes', [LibraryQuizController::class, 'index'])->name('library.quizzes.index');
    Route::post('library/quizzes', [LibraryQuizController::class, 'store'])->name('library.quizzes.store');
    Route::get('library/quizzes/{quiz}', [LibraryQuizController::class, 'show'])->name('library.quizzes.show');
    Route::put('library/quizzes/{quiz}', [LibraryQuizController::class, 'update'])->name('library.quizzes.update');
    Route::post('library/quizzes/{quiz}/archive', [LibraryQuizController::class, 'archive'])->name('library.quizzes.archive');
    Route::post('library/quizzes/{quiz}/questions', [LibraryQuizController::class, 'storeQuestion'])->name('library.quizzes.questions.store');
    Route::put('library/quiz-questions/{quizQuestion}', [LibraryQuizController::class, 'updateQuestion'])->name('library.quiz-questions.update');
    Route::delete('library/quiz-questions/{quizQuestion}', [LibraryQuizController::class, 'destroyQuestion'])->name('library.quiz-questions.destroy');

    Route::get('library/hotspots', [LibraryItemHotspotController::class, 'index'])->name('library.hotspots.index');
    Route::post('library/hotspots', [LibraryItemHotspotController::class, 'store'])->name('library.hotspots.store');
    Route::put('library/hotspots/{libraryItemHotspot}', [LibraryItemHotspotController::class, 'update'])->name('library.hotspots.update');
    Route::delete('library/hotspots/{libraryItemHotspot}', [LibraryItemHotspotController::class, 'destroy'])->name('library.hotspots.destroy');
});
