<?php

use App\Http\Controllers\Admin\QaDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin', 'account.status:active'])->prefix('dashboard')->name('admin.dashboard.')->group(function (): void {
    Route::prefix('qa')->name('qa.')->group(function (): void {
        Route::get('/', [QaDashboardController::class, 'index'])->name('index');
        Route::get('/questions', [QaDashboardController::class, 'questions'])->name('questions');
        Route::get('/answers', [QaDashboardController::class, 'answers'])->name('answers');
        Route::get('/weekly-themes', [QaDashboardController::class, 'weeklyThemes'])->name('weekly-themes');
        Route::get('/reputation', [QaDashboardController::class, 'reputation'])->name('reputation');
        Route::get('/leaderboard', [QaDashboardController::class, 'leaderboard'])->name('leaderboard');
        Route::get('/flagged', [QaDashboardController::class, 'flagged'])->name('flagged');

        Route::post('/questions/{question}/{status}', [QaDashboardController::class, 'updateQuestionStatus'])
            ->whereNumber('question')
            ->whereIn('status', ['published', 'hidden', 'flagged'])
            ->name('questions.status');
        Route::post('/answers/{answer}/feature', [QaDashboardController::class, 'featureAnswer'])->whereNumber('answer')->name('answers.feature');
        Route::post('/answers/{answer}/unfeature', [QaDashboardController::class, 'unfeatureAnswer'])->whereNumber('answer')->name('answers.unfeature');
        Route::post('/weekly-themes', [QaDashboardController::class, 'storeWeeklyTheme'])->name('weekly-themes.store');
        Route::post('/weekly-themes/{weeklyTheme}/{status}', [QaDashboardController::class, 'updateWeeklyThemeStatus'])
            ->whereNumber('weeklyTheme')
            ->whereIn('status', ['active', 'archived'])
            ->name('weekly-themes.status');
        Route::post('/reputation/adjustments', [QaDashboardController::class, 'storeReputationAdjustment'])->name('reputation.adjustments.store');
        Route::post('/leaderboard/settings', [QaDashboardController::class, 'storeLeaderboardSettings'])->name('leaderboard.settings.store');
        Route::post('/leaderboard/snapshot', [QaDashboardController::class, 'snapshotLeaderboard'])->name('leaderboard.snapshot');
    });
});
