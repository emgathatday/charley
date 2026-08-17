<?php

use App\Http\Controllers\Api\V1\PartnerMemberController;
use App\Http\Controllers\Api\V1\PartnerPresentationController;
use App\Http\Controllers\Api\V1\PartnerProductController;
use App\Http\Controllers\Api\V1\PartnerProfileController;
use App\Models\PartnerProfile;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth', 'account.status:active'])->name('api.v1.')->group(function (): void {
    Route::get('partner-profiles', [PartnerProfileController::class, 'index'])
        ->can('viewAny', PartnerProfile::class)
        ->name('partner-profiles.index');
    Route::post('partner-profiles', [PartnerProfileController::class, 'store'])
        ->can('create', PartnerProfile::class)
        ->name('partner-profiles.store');
    Route::get('partner-profiles/{partnerProfile}', [PartnerProfileController::class, 'show'])
        ->name('partner-profiles.show');
    Route::put('partner-profiles/{partnerProfile}', [PartnerProfileController::class, 'update'])
        ->can('update', 'partnerProfile')
        ->name('partner-profiles.update');
    Route::delete('partner-profiles/{partnerProfile}', [PartnerProfileController::class, 'destroy'])
        ->can('delete', 'partnerProfile')
        ->name('partner-profiles.destroy');
    Route::post('partner-profiles/{partnerProfile}/approve', [PartnerProfileController::class, 'approve'])
        ->can('approve', 'partnerProfile')
        ->name('partner-profiles.approve');
    Route::post('partner-profiles/{partnerProfile}/reject', [PartnerProfileController::class, 'reject'])
        ->can('reject', 'partnerProfile')
        ->name('partner-profiles.reject');
    Route::post('partner-profiles/{partnerProfile}/suspend', [PartnerProfileController::class, 'suspend'])
        ->can('suspend', 'partnerProfile')
        ->name('partner-profiles.suspend');

    Route::get('partner-profiles/{partnerProfile}/products', [PartnerProductController::class, 'index'])
        ->can('view', 'partnerProfile')
        ->name('partner-profiles.products.index');
    Route::post('partner-profiles/{partnerProfile}/products', [PartnerProductController::class, 'store'])
        ->can('manageProducts', 'partnerProfile')
        ->name('partner-profiles.products.store');
    Route::get('partner-profiles/{partnerProfile}/products/{partnerProduct}', [PartnerProductController::class, 'show'])
        ->can('view', 'partnerProduct')
        ->name('partner-profiles.products.show');
    Route::put('partner-profiles/{partnerProfile}/products/{partnerProduct}', [PartnerProductController::class, 'update'])
        ->can('update', 'partnerProduct')
        ->name('partner-profiles.products.update');
    Route::delete('partner-profiles/{partnerProfile}/products/{partnerProduct}', [PartnerProductController::class, 'destroy'])
        ->can('delete', 'partnerProduct')
        ->name('partner-profiles.products.destroy');

    Route::get('partner-profiles/{partnerProfile}/presentations', [PartnerPresentationController::class, 'index'])
        ->can('view', 'partnerProfile')
        ->name('partner-profiles.presentations.index');
    Route::post('partner-profiles/{partnerProfile}/presentations', [PartnerPresentationController::class, 'store'])
        ->can('managePresentations', 'partnerProfile')
        ->name('partner-profiles.presentations.store');
    Route::get('partner-profiles/{partnerProfile}/presentations/{partnerPresentation}', [PartnerPresentationController::class, 'show'])
        ->can('view', 'partnerPresentation')
        ->name('partner-profiles.presentations.show');
    Route::put('partner-profiles/{partnerProfile}/presentations/{partnerPresentation}', [PartnerPresentationController::class, 'update'])
        ->can('update', 'partnerPresentation')
        ->name('partner-profiles.presentations.update');
    Route::delete('partner-profiles/{partnerProfile}/presentations/{partnerPresentation}', [PartnerPresentationController::class, 'destroy'])
        ->can('delete', 'partnerPresentation')
        ->name('partner-profiles.presentations.destroy');

    Route::get('partner-profiles/{partnerProfile}/members', [PartnerMemberController::class, 'index'])
        ->can('view', 'partnerProfile')
        ->name('partner-profiles.members.index');
    Route::post('partner-profiles/{partnerProfile}/members', [PartnerMemberController::class, 'store'])
        ->can('manageMembers', 'partnerProfile')
        ->name('partner-profiles.members.store');
    Route::get('partner-profiles/{partnerProfile}/members/{partnerMember}', [PartnerMemberController::class, 'show'])
        ->can('view', 'partnerMember')
        ->name('partner-profiles.members.show');
    Route::put('partner-profiles/{partnerProfile}/members/{partnerMember}', [PartnerMemberController::class, 'update'])
        ->can('update', 'partnerMember')
        ->name('partner-profiles.members.update');
    Route::delete('partner-profiles/{partnerProfile}/members/{partnerMember}', [PartnerMemberController::class, 'destroy'])
        ->can('delete', 'partnerMember')
        ->name('partner-profiles.members.destroy');
});
