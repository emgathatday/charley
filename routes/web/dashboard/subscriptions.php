<?php

use App\Http\Controllers\Admin\SubscriptionAdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin', 'account.status:active'])->prefix('dashboard')->name('admin.dashboard.')->group(function (): void {
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
