<?php

use App\Http\Controllers\Api\V1\AnnouncementQuotaController;
use App\Http\Controllers\Api\V1\MemberSubscriptionController;
use App\Http\Controllers\Api\V1\MemberSubscriptionPlanController;
use App\Http\Controllers\Api\V1\PartnerSubscriptionController;
use App\Http\Controllers\Api\V1\SubscriptionPaymentController;
use App\Http\Controllers\Api\V1\SubscriptionTierController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth')->group(function (): void {
    Route::get('subscription-tiers', [SubscriptionTierController::class, 'index']);
    Route::post('subscription-tiers', [SubscriptionTierController::class, 'store']);
    Route::get('subscription-tiers/{subscriptionTier}', [SubscriptionTierController::class, 'show']);
    Route::put('subscription-tiers/{subscriptionTier}', [SubscriptionTierController::class, 'update']);
    Route::delete('subscription-tiers/{subscriptionTier}', [SubscriptionTierController::class, 'destroy']);

    Route::get('member-subscription-plans', [MemberSubscriptionPlanController::class, 'index']);
    Route::post('member-subscription-plans', [MemberSubscriptionPlanController::class, 'store']);
    Route::get('member-subscription-plans/{memberSubscriptionPlan}', [MemberSubscriptionPlanController::class, 'show']);
    Route::put('member-subscription-plans/{memberSubscriptionPlan}', [MemberSubscriptionPlanController::class, 'update']);
    Route::delete('member-subscription-plans/{memberSubscriptionPlan}', [MemberSubscriptionPlanController::class, 'destroy']);
    Route::get('partner-subscriptions', [PartnerSubscriptionController::class, 'index']);
    Route::post('partner-subscriptions', [PartnerSubscriptionController::class, 'store']);
    Route::get('partner-subscriptions/{partnerSubscription}', [PartnerSubscriptionController::class, 'show']);
    Route::put('partner-subscriptions/{partnerSubscription}', [PartnerSubscriptionController::class, 'update']);
    Route::post('partner-subscriptions/{partnerSubscription}/approve', [PartnerSubscriptionController::class, 'approve']);
    Route::post('partner-subscriptions/{partnerSubscription}/cancel', [PartnerSubscriptionController::class, 'cancel']);

    Route::get('subscription-payments', [SubscriptionPaymentController::class, 'index']);
    Route::post('subscription-payments', [SubscriptionPaymentController::class, 'store']);
    Route::get('subscription-payments/{subscriptionPayment}', [SubscriptionPaymentController::class, 'show']);
    Route::put('subscription-payments/{subscriptionPayment}', [SubscriptionPaymentController::class, 'update']);
    Route::post('subscription-payments/{subscriptionPayment}/approve', [SubscriptionPaymentController::class, 'approve']);
    Route::post('subscription-payments/{subscriptionPayment}/reject', [SubscriptionPaymentController::class, 'reject']);

    Route::get('member-subscriptions', [MemberSubscriptionController::class, 'index']);
    Route::post('member-subscriptions', [MemberSubscriptionController::class, 'store']);
    Route::get('member-subscriptions/{memberSubscription}', [MemberSubscriptionController::class, 'show']);
    Route::put('member-subscriptions/{memberSubscription}', [MemberSubscriptionController::class, 'update']);
    Route::post('member-subscriptions/{memberSubscription}/cancel', [MemberSubscriptionController::class, 'cancel']);

    Route::get('announcement-quotas', [AnnouncementQuotaController::class, 'index']);
    Route::post('announcement-quotas', [AnnouncementQuotaController::class, 'store']);
    Route::get('announcement-quotas/{announcementQuota}', [AnnouncementQuotaController::class, 'show']);
    Route::put('announcement-quotas/{announcementQuota}', [AnnouncementQuotaController::class, 'update']);
    Route::post('announcement-quotas/{announcementQuota}/consume', [AnnouncementQuotaController::class, 'consume']);
});
