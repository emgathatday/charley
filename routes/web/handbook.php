<?php

use App\Http\Controllers\Admin\HandbookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin', 'account.status:active'])
    ->prefix('dashboard/handbook')
    ->name('admin.dashboard.handbook.')
    ->group(function (): void {
        Route::get('/', [HandbookController::class, 'index'])->name('index');
        Route::get('/create', [HandbookController::class, 'create'])->name('create');
        Route::post('/', [HandbookController::class, 'store'])->name('store');
        Route::get('/{handbookArticle}', [HandbookController::class, 'show'])->name('show');
        Route::get('/{handbookArticle}/edit', [HandbookController::class, 'edit'])->name('edit');
        Route::put('/{handbookArticle}', [HandbookController::class, 'update'])->name('update');
        Route::post('/{handbookArticle}/publish', [HandbookController::class, 'publish'])->name('publish');
        Route::post('/{handbookArticle}/archive', [HandbookController::class, 'archive'])->name('archive');
    });
