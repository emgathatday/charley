<?php

use App\Http\Controllers\Admin\PartnerProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin', 'account.status:active'])->prefix('dashboard')->name('admin.dashboard.')->group(function (): void {
    Route::get('partner-profiles', [PartnerProfileController::class, 'index'])->name('partner-profiles.index');
    Route::get('partner-profiles/create', [PartnerProfileController::class, 'create'])->name('partner-profiles.create');
    Route::post('partner-profiles', [PartnerProfileController::class, 'store'])->name('partner-profiles.store');
    Route::get('partner-profiles/{partnerProfile}', [PartnerProfileController::class, 'show'])->name('partner-profiles.show');
    Route::get('partner-profiles/{partnerProfile}/edit', [PartnerProfileController::class, 'edit'])->name('partner-profiles.edit');
    Route::put('partner-profiles/{partnerProfile}', [PartnerProfileController::class, 'update'])->name('partner-profiles.update');
    Route::delete('partner-profiles/{partnerProfile}', [PartnerProfileController::class, 'destroy'])->name('partner-profiles.destroy');
    Route::post('partner-profiles/{partnerProfile}/approve', [PartnerProfileController::class, 'approve'])->name('partner-profiles.approve');
    Route::post('partner-profiles/{partnerProfile}/reject', [PartnerProfileController::class, 'reject'])->name('partner-profiles.reject');
});
