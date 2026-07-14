<?php

use App\Http\Controllers\LibraryWorkspaceController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('feed', [PageController::class, 'feed'])->name('feed.index');
Route::get('library', [LibraryWorkspaceController::class, 'index'])->name('library.index');
Route::get('pages', [PageController::class, 'index'])->name('pages.index');
Route::get('pages/{slug}', [PageController::class, 'show'])->name('pages.show');
