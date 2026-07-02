<?php

use App\Http\Controllers\Admin\IamSecurityController;
use App\Http\Controllers\Admin\IamUserController;
use App\Http\Controllers\Admin\IamVerificationController;
use App\Http\Controllers\Admin\MediaFileController;
use App\Http\Controllers\Admin\PlantTypeController;
use App\Http\Controllers\Admin\PartnerProfileController;
use App\Http\Controllers\Admin\SubscriptionAdminController;
use App\Http\Controllers\Admin\TaxonomyController;
use App\Http\Controllers\AdminAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('admin.login.store');
});

Route::post('logout', [AdminAuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:admin', 'account.status:active'])->prefix('dashboard')->name('admin.dashboard.')->group(function (): void {
    Route::get('iam/users', [IamUserController::class, 'index'])->name('iam.users');
    Route::get('iam/verification-queue', [IamVerificationController::class, 'index'])->name('iam.verification-queue');
    Route::post('iam/verification-queue/{verificationRequest}/approve', [IamVerificationController::class, 'approve'])->name('iam.verification-queue.approve');
    Route::post('iam/verification-queue/{verificationRequest}/reject', [IamVerificationController::class, 'reject'])->name('iam.verification-queue.reject');
    Route::post('iam/verification-queue/{verificationRequest}/more-info', [IamVerificationController::class, 'requestMoreInfo'])->name('iam.verification-queue.more-info');
    Route::get('iam/user-security/{user?}', [IamSecurityController::class, 'show'])->name('iam.user-security');
    Route::put('iam/user-security/{user}', [IamSecurityController::class, 'update'])->name('iam.user-security.update');
    Route::get('media-files', [MediaFileController::class, 'index'])->name('media-files.index');
    Route::post('media-files', [MediaFileController::class, 'store'])->name('media-files.store');
    Route::get('media-files/{mediaFile}', [MediaFileController::class, 'show'])->name('media-files.show');
    Route::get('plant-types', [PlantTypeController::class, 'index'])->name('plant-types.index');
    Route::get('plant-types/create', [PlantTypeController::class, 'create'])->name('plant-types.create');
    Route::post('plant-types', [PlantTypeController::class, 'store'])->name('plant-types.store');
    Route::get('plant-types/{plantType}/edit', [PlantTypeController::class, 'edit'])->name('plant-types.edit');
    Route::put('plant-types/{plantType}', [PlantTypeController::class, 'update'])->name('plant-types.update');
    Route::delete('plant-types/{plantType}', [PlantTypeController::class, 'destroy'])->name('plant-types.destroy');
    Route::get('taxonomy', [TaxonomyController::class, 'index'])->name('taxonomy.index');

    Route::get('taxonomy/create', [TaxonomyController::class, 'create'])->name('taxonomy.create');

    Route::post('taxonomy', [TaxonomyController::class, 'store'])->name('taxonomy.store');

    Route::get('taxonomy/{tag}/edit', [TaxonomyController::class, 'edit'])->name('taxonomy.edit');

    Route::put('taxonomy/{tag}', [TaxonomyController::class, 'update'])->name('taxonomy.update');

    Route::delete('taxonomy/{tag}', [TaxonomyController::class, 'destroy'])->name('taxonomy.destroy');
    Route::get('partner-profiles', [PartnerProfileController::class, 'index'])->name('partner-profiles.index');
    Route::get('partner-profiles/create', [PartnerProfileController::class, 'create'])->name('partner-profiles.create');
    Route::post('partner-profiles', [PartnerProfileController::class, 'store'])->name('partner-profiles.store');
    Route::get('partner-profiles/{partnerProfile}', [PartnerProfileController::class, 'show'])->name('partner-profiles.show');
    Route::get('partner-profiles/{partnerProfile}/edit', [PartnerProfileController::class, 'edit'])->name('partner-profiles.edit');
    Route::put('partner-profiles/{partnerProfile}', [PartnerProfileController::class, 'update'])->name('partner-profiles.update');
    Route::delete('partner-profiles/{partnerProfile}', [PartnerProfileController::class, 'destroy'])->name('partner-profiles.destroy');
    Route::post('partner-profiles/{partnerProfile}/approve', [PartnerProfileController::class, 'approve'])->name('partner-profiles.approve');
    Route::post('partner-profiles/{partnerProfile}/reject', [PartnerProfileController::class, 'reject'])->name('partner-profiles.reject');
    Route::get('subscriptions', [SubscriptionAdminController::class, 'index'])->name('subscriptions.index');
    Route::get('subscriptions/tiers/create', [SubscriptionAdminController::class, 'createTier'])->name('subscriptions.tiers.create');
    Route::post('subscriptions/tiers', [SubscriptionAdminController::class, 'storeTier'])->name('subscriptions.tiers.store');
    Route::get('subscriptions/tiers/{subscriptionTier}/edit', [SubscriptionAdminController::class, 'editTier'])->name('subscriptions.tiers.edit');
    Route::put('subscriptions/tiers/{subscriptionTier}', [SubscriptionAdminController::class, 'updateTier'])->name('subscriptions.tiers.update');
    Route::get('subscriptions/member-plans/create', [SubscriptionAdminController::class, 'createMemberPlan'])->name('subscriptions.member-plans.create');
    Route::post('subscriptions/member-plans', [SubscriptionAdminController::class, 'storeMemberPlan'])->name('subscriptions.member-plans.store');
    Route::get('subscriptions/member-plans/{memberSubscriptionPlan}/edit', [SubscriptionAdminController::class, 'editMemberPlan'])->name('subscriptions.member-plans.edit');
    Route::put('subscriptions/member-plans/{memberSubscriptionPlan}', [SubscriptionAdminController::class, 'updateMemberPlan'])->name('subscriptions.member-plans.update');
    Route::post('subscriptions/partner-subscriptions/{partnerSubscription}/approve', [SubscriptionAdminController::class, 'approvePartnerSubscription'])->name('subscriptions.partner-subscriptions.approve');
    Route::post('subscriptions/partner-subscriptions/{partnerSubscription}/cancel', [SubscriptionAdminController::class, 'cancelPartnerSubscription'])->name('subscriptions.partner-subscriptions.cancel');
    Route::post('subscriptions/payments/{subscriptionPayment}/approve', [SubscriptionAdminController::class, 'approvePayment'])->name('subscriptions.payments.approve');
    Route::post('subscriptions/payments/{subscriptionPayment}/reject', [SubscriptionAdminController::class, 'rejectPayment'])->name('subscriptions.payments.reject');
});


