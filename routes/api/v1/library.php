<?php

use App\Http\Controllers\Api\V1\DomainQuizQuestionController;
use App\Http\Controllers\Api\V1\ExpertiseRankController;
use App\Http\Controllers\Api\V1\KnowledgeDomainController;
use App\Http\Controllers\Api\V1\LibraryAccessLogController;
use App\Http\Controllers\Api\V1\LibraryAccessRuleController;
use App\Http\Controllers\Api\V1\LibraryCategoryController;
use App\Http\Controllers\Api\V1\LibraryItemController;
use App\Http\Controllers\Api\V1\QuizAttemptController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('library/items', [LibraryItemController::class, 'index'])->name('library.items.index');
    Route::get('library/items/{libraryItem}', [LibraryItemController::class, 'show'])->can('view', 'libraryItem')->name('library.items.show');

    Route::middleware('auth')->group(function (): void {
        Route::post('library/items', [LibraryItemController::class, 'store'])->name('library.items.store');
        Route::put('library/items/{libraryItem}', [LibraryItemController::class, 'update'])->name('library.items.update');
        Route::delete('library/items/{libraryItem}', [LibraryItemController::class, 'destroy'])->name('library.items.destroy');
        Route::post('library/items/{libraryItem}/approve', [LibraryItemController::class, 'approve'])->name('library.items.approve');
        Route::post('library/items/{libraryItem}/archive', [LibraryItemController::class, 'archive'])->name('library.items.archive');
        Route::post('library/items/{libraryItem}/view', [LibraryItemController::class, 'view'])->name('library.items.view');
        Route::post('library/items/{libraryItem}/download', [LibraryItemController::class, 'download'])->name('library.items.download');

        Route::apiResource('library/categories', LibraryCategoryController::class)->parameters(['categories' => 'libraryCategory']);
        Route::get('library/access-rules', [LibraryAccessRuleController::class, 'index'])->name('library.access-rules.index');
        Route::post('library/access-rules', [LibraryAccessRuleController::class, 'store'])->name('library.access-rules.store');
        Route::put('library/access-rules/{libraryAccessRule}', [LibraryAccessRuleController::class, 'update'])->name('library.access-rules.update');
        Route::get('library/access-logs', [LibraryAccessLogController::class, 'index'])->name('library.access-logs.index');

        Route::apiResource('library/knowledge-domains', KnowledgeDomainController::class)->parameters(['knowledge-domains' => 'knowledgeDomain']);
        Route::get('library/knowledge-domains/{knowledgeDomain}/quiz-questions', [DomainQuizQuestionController::class, 'index'])->name('library.knowledge-domains.quiz-questions.index');
        Route::post('library/knowledge-domains/{knowledgeDomain}/quiz-questions', [DomainQuizQuestionController::class, 'store'])->name('library.knowledge-domains.quiz-questions.store');
        Route::get('library/knowledge-domains/{knowledgeDomain}/quiz-questions/{quizQuestion}', [DomainQuizQuestionController::class, 'show'])->name('library.knowledge-domains.quiz-questions.show');
        Route::put('library/knowledge-domains/{knowledgeDomain}/quiz-questions/{quizQuestion}', [DomainQuizQuestionController::class, 'update'])->name('library.knowledge-domains.quiz-questions.update');
        Route::delete('library/knowledge-domains/{knowledgeDomain}/quiz-questions/{quizQuestion}', [DomainQuizQuestionController::class, 'destroy'])->name('library.knowledge-domains.quiz-questions.destroy');
        Route::post('library/knowledge-domains/{knowledgeDomain}/quiz-attempts', [QuizAttemptController::class, 'store'])->name('library.quiz-attempts.store');
        Route::post('library/quiz-attempts/{quizAttempt}/submit', [QuizAttemptController::class, 'submit'])->name('library.quiz-attempts.submit');

        Route::get('library/expertise-rank-tiers', [ExpertiseRankController::class, 'tiers'])->name('library.rank-tiers.index');
        Route::get('library/users/{user}/expertise-rank', [ExpertiseRankController::class, 'current'])->name('library.user-ranks.current');
        Route::post('library/users/{user}/expertise-rank/manual', [ExpertiseRankController::class, 'setManual'])->name('library.user-ranks.manual');
        Route::post('library/users/{user}/expertise-rank/evaluate', [ExpertiseRankController::class, 'evaluate'])->name('library.user-ranks.evaluate');
        Route::get('library/rank-promotion-logs', [ExpertiseRankController::class, 'logs'])->name('library.rank-promotion-logs.index');
    });
});
