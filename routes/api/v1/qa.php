<?php

use App\Http\Controllers\Api\V1\Qa\AnswerController as QaAnswerController;
use App\Http\Controllers\Api\V1\Qa\FilterController as QaFilterController;
use App\Http\Controllers\Api\V1\Qa\LeaderboardController as QaLeaderboardController;
use App\Http\Controllers\Api\V1\Qa\QuestionController as QaQuestionController;
use App\Http\Controllers\Api\V1\Qa\WeeklyThemeController as QaWeeklyThemeController;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::prefix('qa')->name('qa.')->group(function (): void {
        Route::get('/questions', [QaQuestionController::class, 'index'])->name('questions.index');
        Route::get('/questions/{question}', [QaQuestionController::class, 'show'])->name('questions.show');
        Route::get('/weekly-themes', [QaWeeklyThemeController::class, 'index'])->name('weekly-themes.index');
        Route::get('/plant-types', [QaFilterController::class, 'plantTypes'])->name('plant-types.index');
        Route::get('/knowledge-domains', [QaFilterController::class, 'knowledgeDomains'])->name('knowledge-domains.index');
        Route::get('/domain-links', [QaFilterController::class, 'domainLinks'])->name('domain-links.index');
        Route::get('/leaderboard/monthly/{yearMonth?}', [QaLeaderboardController::class, 'monthly'])->name('leaderboard.monthly');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/questions', [QaQuestionController::class, 'store'])->middleware('can:create,'.Question::class)->name('questions.store');
            Route::post('/questions/{question}/answers', [QaAnswerController::class, 'store'])->middleware('can:create,'.Answer::class)->name('answers.store');
        });
    });
});
