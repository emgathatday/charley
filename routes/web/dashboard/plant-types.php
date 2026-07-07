<?php

use App\Http\Controllers\Admin\PlantTypeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin', 'account.status:active'])->prefix('dashboard')->name('admin.dashboard.')->group(function (): void {
    Route::get('plant-types', [PlantTypeController::class, 'index'])->name('plant-types.index');
    Route::get('plant-types/create', [PlantTypeController::class, 'create'])->name('plant-types.create');
    Route::post('plant-types', [PlantTypeController::class, 'store'])->name('plant-types.store');
    Route::get('plant-types/{plantType}/edit', [PlantTypeController::class, 'edit'])->name('plant-types.edit');
    Route::put('plant-types/{plantType}', [PlantTypeController::class, 'update'])->name('plant-types.update');
    Route::delete('plant-types/{plantType}', [PlantTypeController::class, 'destroy'])->name('plant-types.destroy');
});
