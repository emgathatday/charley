<?php

use App\Http\Controllers\Admin\TaxonomyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin', 'account.status:active'])->prefix('dashboard')->name('admin.dashboard.')->group(function (): void {
    Route::get('taxonomy', [TaxonomyController::class, 'index'])->name('taxonomy.index');
    Route::get('taxonomy/create', [TaxonomyController::class, 'create'])->name('taxonomy.create');
    Route::post('taxonomy', [TaxonomyController::class, 'store'])->name('taxonomy.store');
    Route::get('taxonomy/{tag}/edit', [TaxonomyController::class, 'edit'])->name('taxonomy.edit');
    Route::put('taxonomy/{tag}', [TaxonomyController::class, 'update'])->name('taxonomy.update');
    Route::delete('taxonomy/{tag}', [TaxonomyController::class, 'destroy'])->name('taxonomy.destroy');
});
