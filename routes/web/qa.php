<?php

use App\Http\Controllers\Admin\QaDashboardController;
use App\Http\Controllers\Admin\QaModerationController;
use App\Http\Controllers\Qa\CommunityQaController;
use Illuminate\Support\Facades\Route;

Route::prefix('qa')->name('qa.community.')->group(function (): void {
    Route::get('/', [CommunityQaController::class, 'index'])->name('index');
    Route::get('/ask', [CommunityQaController::class, 'ask'])->name('ask');
    Route::get('/questions/{slug}', [CommunityQaController::class, 'show'])->name('show');
});

Route::middleware(['auth', 'role:admin', 'account.status:active'])->prefix('dashboard/qa')->name('admin.dashboard.qa.')->group(function (): void {
    Route::get('/questions/{question}', [QaDashboardController::class, 'questionDetail'])->whereNumber('question')->name('questions.show');
    Route::post('/questions/{question}/demo-status', [QaDashboardController::class, 'storeQuestionDetailStatus'])->whereNumber('question')->name('questions.demo-status');
    Route::get('/leaderboard-report', [QaDashboardController::class, 'leaderboardReport'])->name('leaderboard-report');
    Route::get('/moderation-rules', [QaModerationController::class, 'rules'])->name('moderation-rules');
    Route::post('/moderation-rules', [QaModerationController::class, 'storeRule'])->name('moderation-rules.store');
    Route::post('/moderation-rules/{rule}', [QaModerationController::class, 'updateRule'])->whereNumber('rule')->name('moderation-rules.update');
    Route::post('/moderation-rules/{rule}/toggle', [QaModerationController::class, 'toggleRule'])->whereNumber('rule')->name('moderation-rules.toggle');
    Route::get('/warnings', [QaModerationController::class, 'warnings'])->name('warnings');
    Route::post('/warnings/{warning}/{status}', [QaModerationController::class, 'reviewWarning'])->whereNumber('warning')->whereIn('status', ['safe', 'dismissed', 'confirmed'])->name('warnings.review');
    Route::post('/weekly-themes/{weeklyTheme}/questions/assign', [QaDashboardController::class, 'assignWeeklyThemeQuestion'])->whereNumber('weeklyTheme')->name('weekly-themes.assign-question');
    Route::post('/weekly-themes/{weeklyTheme}/questions/{question}/remove', [QaDashboardController::class, 'removeWeeklyThemeQuestion'])->whereNumber('weeklyTheme')->whereNumber('question')->name('weekly-themes.remove-question');
});
