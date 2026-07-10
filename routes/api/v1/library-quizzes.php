<?php

use App\Http\Controllers\Api\V1\DomainRankTierController;
use App\Http\Controllers\Api\V1\KnowledgeDomainController;
use App\Http\Controllers\Api\V1\LibraryItemHotspotController;
use App\Http\Controllers\Api\V1\QuizAttemptController;
use App\Http\Controllers\Api\V1\QuizController;
use App\Http\Controllers\Api\V1\UserDomainPointController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('library/knowledge-domains', [KnowledgeDomainController::class, 'index'])->name('library.knowledge-domains.index');
    Route::get('library/knowledge-domains/{knowledgeDomain}', [KnowledgeDomainController::class, 'show'])->name('library.knowledge-domains.show');
    Route::get('library/knowledge-domains/{knowledgeDomain}/rank-tiers', [DomainRankTierController::class, 'index'])->name('library.knowledge-domains.rank-tiers.index');

    Route::get('library/quizzes', [QuizController::class, 'index'])->name('library.quizzes.index');
    Route::get('library/quizzes/{quiz}', [QuizController::class, 'show'])->name('library.quizzes.show');

    Route::get('library/items/{libraryItem}/hotspots', [LibraryItemHotspotController::class, 'index'])->name('library.items.hotspots.index');
});

Route::prefix('v1')->name('api.v1.')->middleware('auth')->group(function (): void {
    Route::get('library/quiz-attempts', [QuizAttemptController::class, 'index'])->name('library.quiz-attempts.index');
    Route::get('library/quiz-attempts/{quizAttempt}', [QuizAttemptController::class, 'show'])->name('library.quiz-attempts.show');
    Route::post('library/quizzes/{quiz}/attempts', [QuizAttemptController::class, 'store'])->name('library.quizzes.attempts.store');

    Route::get('library/domain-points', [UserDomainPointController::class, 'index'])->name('library.domain-points.index');
    Route::get('library/domain-points/{knowledgeDomain}', [UserDomainPointController::class, 'show'])->name('library.domain-points.show');
});

Route::prefix('v1/library/admin')->name('api.v1.library.admin.')->middleware(['auth', 'role:admin', 'account.status:active'])->group(function (): void {
    Route::post('knowledge-domains', [KnowledgeDomainController::class, 'store'])->name('knowledge-domains.store');
    Route::put('knowledge-domains/{knowledgeDomain}', [KnowledgeDomainController::class, 'update'])->name('knowledge-domains.update');
    Route::post('knowledge-domains/{knowledgeDomain}/archive', [KnowledgeDomainController::class, 'archive'])->name('knowledge-domains.archive');
    Route::post('knowledge-domains/{knowledgeDomain}/rank-tiers', [DomainRankTierController::class, 'store'])->name('knowledge-domains.rank-tiers.store');
    Route::put('domain-rank-tiers/{domainRankTier}', [DomainRankTierController::class, 'update'])->name('domain-rank-tiers.update');
    Route::delete('domain-rank-tiers/{domainRankTier}', [DomainRankTierController::class, 'destroy'])->name('domain-rank-tiers.destroy');

    Route::post('quizzes', [QuizController::class, 'store'])->name('quizzes.store');
    Route::put('quizzes/{quiz}', [QuizController::class, 'update'])->name('quizzes.update');
    Route::delete('quizzes/{quiz}', [QuizController::class, 'destroy'])->name('quizzes.destroy');
    Route::post('quizzes/{quiz}/questions', [QuizController::class, 'storeQuestion'])->name('quizzes.questions.store');
    Route::put('quizzes/{quiz}/questions/{quizQuestion}', [QuizController::class, 'updateQuestion'])->name('quizzes.questions.update');
    Route::delete('quizzes/{quiz}/questions/{quizQuestion}', [QuizController::class, 'destroyQuestion'])->name('quizzes.questions.destroy');

    Route::post('items/{libraryItem}/hotspots', [LibraryItemHotspotController::class, 'store'])->name('items.hotspots.store');
    Route::put('hotspots/{libraryItemHotspot}', [LibraryItemHotspotController::class, 'update'])->name('hotspots.update');
    Route::delete('hotspots/{libraryItemHotspot}', [LibraryItemHotspotController::class, 'destroy'])->name('hotspots.destroy');
});
