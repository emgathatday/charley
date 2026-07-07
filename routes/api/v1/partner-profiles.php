<?php

use App\Http\Controllers\Api\V1\PartnerMemberController;
use App\Http\Controllers\Api\V1\PartnerPresentationController;
use App\Http\Controllers\Api\V1\PartnerProductController;
use App\Http\Controllers\Api\V1\PartnerProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth')->group(function (): void {
    Route::get('partner-profiles', [PartnerProfileController::class, 'index']);
    Route::post('partner-profiles', [PartnerProfileController::class, 'store']);
    Route::get('partner-profiles/{partnerProfile}', [PartnerProfileController::class, 'show']);
    Route::put('partner-profiles/{partnerProfile}', [PartnerProfileController::class, 'update']);
    Route::delete('partner-profiles/{partnerProfile}', [PartnerProfileController::class, 'destroy']);
    Route::post('partner-profiles/{partnerProfile}/approve', [PartnerProfileController::class, 'approve']);
    Route::post('partner-profiles/{partnerProfile}/reject', [PartnerProfileController::class, 'reject']);
    Route::post('partner-profiles/{partnerProfile}/suspend', [PartnerProfileController::class, 'suspend']);

    Route::get('partner-profiles/{partnerProfile}/products', [PartnerProductController::class, 'index']);
    Route::post('partner-profiles/{partnerProfile}/products', [PartnerProductController::class, 'store']);
    Route::get('partner-profiles/{partnerProfile}/products/{partnerProduct}', [PartnerProductController::class, 'show']);
    Route::put('partner-profiles/{partnerProfile}/products/{partnerProduct}', [PartnerProductController::class, 'update']);
    Route::delete('partner-profiles/{partnerProfile}/products/{partnerProduct}', [PartnerProductController::class, 'destroy']);
    Route::get('partner-profiles/{partnerProfile}/presentations', [PartnerPresentationController::class, 'index']);
    Route::post('partner-profiles/{partnerProfile}/presentations', [PartnerPresentationController::class, 'store']);
    Route::get('partner-profiles/{partnerProfile}/presentations/{partnerPresentation}', [PartnerPresentationController::class, 'show']);
    Route::put('partner-profiles/{partnerProfile}/presentations/{partnerPresentation}', [PartnerPresentationController::class, 'update']);
    Route::delete('partner-profiles/{partnerProfile}/presentations/{partnerPresentation}', [PartnerPresentationController::class, 'destroy']);
    Route::get('partner-profiles/{partnerProfile}/members', [PartnerMemberController::class, 'index']);
    Route::post('partner-profiles/{partnerProfile}/members', [PartnerMemberController::class, 'store']);
    Route::get('partner-profiles/{partnerProfile}/members/{partnerMember}', [PartnerMemberController::class, 'show']);
    Route::put('partner-profiles/{partnerProfile}/members/{partnerMember}', [PartnerMemberController::class, 'update']);
    Route::delete('partner-profiles/{partnerProfile}/members/{partnerMember}', [PartnerMemberController::class, 'destroy']);
});
