<?php

use App\Http\Controllers\Api\V1\Admin\Qa\AnswerModerationController;
use App\Http\Controllers\Api\V1\Admin\Qa\LeaderboardReportController;
use App\Http\Controllers\Api\V1\Admin\Qa\QuestionModerationController;
use App\Http\Controllers\Api\V1\Admin\Qa\ReputationAdjustmentController;
use App\Http\Controllers\Api\V1\Admin\Qa\WeeklyThemeManagementController;
use App\Models\Answer;
use App\Models\Question;
use App\Models\UserReputation;
use App\Models\WeeklyTheme;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('v1/admin/qa')
    ->name('api.v1.admin.qa.')
    ->group(function (): void {
        Route::get('/questions', [QuestionModerationController::class, 'index'])->middleware('can:moderate,'.Question::class)->name('questions.index');
        Route::get('/questions/{question}', [QuestionModerationController::class, 'show'])->middleware('can:moderate,'.Question::class)->name('questions.show');
        Route::post('/questions/{question}/publish', [QuestionModerationController::class, 'publish'])->middleware('can:publish,question')->name('questions.publish');
        Route::post('/questions/{question}/hide', [QuestionModerationController::class, 'hide'])->middleware('can:hide,question')->name('questions.hide');
        Route::post('/questions/{question}/flag', [QuestionModerationController::class, 'flag'])->middleware('can:flag,question')->name('questions.flag');

        Route::get('/answers', [AnswerModerationController::class, 'index'])->middleware('can:moderate,'.Answer::class)->name('answers.index');
        Route::post('/answers/{answer}/feature', [AnswerModerationController::class, 'feature'])->middleware('can:feature,answer')->name('answers.feature');
        Route::post('/answers/{answer}/unfeature', [AnswerModerationController::class, 'unfeature'])->middleware('can:unfeature,answer')->name('answers.unfeature');
        Route::post('/questions/{question}/answers/reorder', [AnswerModerationController::class, 'reorder'])->middleware('can:reorder,'.Answer::class)->name('answers.reorder');

        Route::apiResource('weekly-themes', WeeklyThemeManagementController::class)->only(['index', 'show', 'store', 'update'])->middleware('can:manage,'.WeeklyTheme::class);
        Route::post('/weekly-themes/{weeklyTheme}/activate', [WeeklyThemeManagementController::class, 'activate'])->middleware('can:update,weeklyTheme')->name('weekly-themes.activate');
        Route::post('/weekly-themes/{weeklyTheme}/archive', [WeeklyThemeManagementController::class, 'archive'])->middleware('can:update,weeklyTheme')->name('weekly-themes.archive');

        Route::post('/reputation/adjustments', [ReputationAdjustmentController::class, 'store'])->middleware('can:adjust,'.UserReputation::class)->name('reputation.adjustments.store');
        Route::get('/leaderboard/monthly/{yearMonth?}', [LeaderboardReportController::class, 'monthly'])->middleware('can:manageLeaderboard,'.UserReputation::class)->name('leaderboard.monthly');
        Route::post('/leaderboard/monthly/{yearMonth}/snapshots', [LeaderboardReportController::class, 'snapshot'])->middleware('can:manageLeaderboard,'.UserReputation::class)->name('leaderboard.monthly.snapshot');
    });
