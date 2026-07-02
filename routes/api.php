<?php

use App\Http\Controllers\Api\V1\AccountSecurityController;
use App\Http\Controllers\Api\V1\AccountPenaltyController;
use App\Http\Controllers\Api\V1\AdminIntegrationController;
use App\Http\Controllers\Api\V1\ContentApprovalQueueController;
use App\Http\Controllers\Api\V1\AnnouncementQuotaController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ConnectionController;
use App\Http\Controllers\Api\V1\ExpertDirectoryController;
use App\Http\Controllers\Api\V1\PageRevisionController;
use App\Http\Controllers\Api\V1\FeedPriorityController;
use App\Http\Controllers\Api\V1\FeedController;
use App\Http\Controllers\Api\V1\FeedCmsPageController;
use App\Http\Controllers\Api\V1\MediaFileController;
use App\Http\Controllers\Api\V1\MemberSubscriptionController;
use App\Http\Controllers\Api\V1\MemberSubscriptionPlanController;
use App\Http\Controllers\Api\V1\PlantTypeController;
use App\Http\Controllers\Api\V1\PlatformSettingController;
use App\Http\Controllers\Api\V1\PartnerProfileController;
use App\Http\Controllers\Api\V1\PartnerProductController;
use App\Http\Controllers\Api\V1\PartnerPresentationController;
use App\Http\Controllers\Api\V1\PartnerMemberController;
use App\Http\Controllers\Api\V1\PartnerSubscriptionController;
use App\Http\Controllers\Api\V1\ProfileActivityController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SocialAccountController;
use App\Http\Controllers\Api\V1\SupportTicketController;
use App\Http\Controllers\Api\V1\SubscriptionPaymentController;
use App\Http\Controllers\Api\V1\SubscriptionTierController;
use App\Http\Controllers\Api\V1\TaxonomyController;
use App\Http\Controllers\Api\V1\VerificationRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/login-tokens', [AuthController::class, 'issueLoginToken']);
    Route::post('auth/login-tokens/consume', [AuthController::class, 'consumeLoginToken']);

    Route::get('feed-cms/pages', [FeedCmsPageController::class, 'publicIndex']);
    Route::get('feed-cms/pages/{slug}', [FeedCmsPageController::class, 'publicShow']);
    Route::middleware('auth')->group(function (): void {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::post('account/security/failed-login', [AccountSecurityController::class, 'recordFailedLogin']);
        Route::post('account/security/mfa', [AccountSecurityController::class, 'enableMfa']);
        Route::post('account/security/freeze', [AccountSecurityController::class, 'freeze']);

        Route::post('social-accounts', [SocialAccountController::class, 'store']);
        Route::delete('social-accounts/{socialAccount}', [SocialAccountController::class, 'destroy']);

        Route::get('verification-requests', [VerificationRequestController::class, 'index']);
        Route::post('verification-requests', [VerificationRequestController::class, 'store']);
        Route::post('verification-requests/{verificationRequest}/approve', [VerificationRequestController::class, 'approve']);
        Route::post('verification-requests/{verificationRequest}/reject', [VerificationRequestController::class, 'reject']);

        Route::get('profile/activity', [ProfileActivityController::class, 'activity']);
        Route::get('profile/metas', [ProfileActivityController::class, 'metas']);
        Route::put('profile/metas', [ProfileActivityController::class, 'upsertMeta']);
        Route::get('profile/engineer', [ProfileController::class, 'myEngineerProfile']);
        Route::put('profile/engineer', [ProfileController::class, 'upsertEngineerProfile']);
        Route::get('profile/unverified', [ProfileController::class, 'myUnverifiedProfile']);
        Route::put('profile/unverified', [ProfileController::class, 'upsertUnverifiedProfile']);
        Route::get('profiles/engineers/{engineerProfile}', [ProfileController::class, 'showEngineerProfile']);
        Route::get('profiles/unverified-members/{unverifiedMemberProfile}', [ProfileController::class, 'showUnverifiedProfile']);

        Route::get('connections', [ConnectionController::class, 'index']);
        Route::post('connections', [ConnectionController::class, 'store']);
        Route::post('connections/{connection}/accept', [ConnectionController::class, 'accept']);
        Route::post('connections/{connection}/decline', [ConnectionController::class, 'decline']);
        Route::post('connections/{connection}/block', [ConnectionController::class, 'block']);

        Route::get('expert-directory', [ExpertDirectoryController::class, 'index']);

        Route::get('feed-cms/feed', [FeedController::class, 'index']);
        Route::post('feed-cms/feed-cache/{userFeedCache}/seen', [FeedController::class, 'markSeen']);
        Route::get('feed-cms/feed-priorities', [FeedPriorityController::class, 'index']);
        Route::put('feed-cms/feed-priorities', [FeedPriorityController::class, 'update']);
        Route::get('feed-cms/admin/pages', [FeedCmsPageController::class, 'index']);
        Route::post('feed-cms/admin/pages', [FeedCmsPageController::class, 'store']);
        Route::get('feed-cms/admin/pages/{page}', [FeedCmsPageController::class, 'show']);
        Route::put('feed-cms/admin/pages/{page}', [FeedCmsPageController::class, 'update']);
        Route::post('feed-cms/admin/pages/{page}/publish', [FeedCmsPageController::class, 'publish']);
        Route::post('feed-cms/admin/pages/{page}/archive', [FeedCmsPageController::class, 'archive']);
        Route::get('feed-cms/admin/pages/{page}/revisions', [PageRevisionController::class, 'index']);
        Route::post('feed-cms/admin/pages/{page}/revisions/{pageRevision}/rollback', [PageRevisionController::class, 'rollback']);
        Route::get('media-files', [MediaFileController::class, 'index']);
        Route::post('media-files', [MediaFileController::class, 'store']);
        Route::get('media-files/{mediaFile}', [MediaFileController::class, 'show']);
        Route::post('media-files/{mediaFile}/attach', [MediaFileController::class, 'attach']);
        Route::post('media-files/{mediaFile}/orphan', [MediaFileController::class, 'markOrphan']);

        Route::get('plant-types', [PlantTypeController::class, 'index']);
        Route::post('plant-types', [PlantTypeController::class, 'store']);
        Route::get('plant-types/{plantType}', [PlantTypeController::class, 'show']);
        Route::put('plant-types/{plantType}', [PlantTypeController::class, 'update']);
        Route::delete('plant-types/{plantType}', [PlantTypeController::class, 'destroy']);

        Route::get('taxonomy/tags', [TaxonomyController::class, 'index']);
        Route::get('taxonomy/tags/search', [TaxonomyController::class, 'search']);
        Route::post('taxonomy/tags', [TaxonomyController::class, 'store']);
        Route::get('taxonomy/tags/{tag}', [TaxonomyController::class, 'show']);
        Route::put('taxonomy/tags/{tag}', [TaxonomyController::class, 'update']);
        Route::delete('taxonomy/tags/{tag}', [TaxonomyController::class, 'destroy']);
        Route::post('taxonomy/tags/sync', [TaxonomyController::class, 'sync']);
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
        Route::get('account-penalties', [AccountPenaltyController::class, 'index']);
        Route::post('account-penalties', [AccountPenaltyController::class, 'store']);
        Route::get('account-penalties/{accountPenalty}', [AccountPenaltyController::class, 'show']);
        Route::post('account-penalties/{accountPenalty}/end', [AccountPenaltyController::class, 'end']);

        Route::get('support-tickets', [SupportTicketController::class, 'index']);
        Route::post('support-tickets', [SupportTicketController::class, 'store']);
        Route::get('support-tickets/{supportTicket}', [SupportTicketController::class, 'show']);
        Route::post('support-tickets/{supportTicket}/assign', [SupportTicketController::class, 'assign']);
        Route::post('support-tickets/{supportTicket}/replies', [SupportTicketController::class, 'reply']);
        Route::post('support-tickets/{supportTicket}/resolve', [SupportTicketController::class, 'resolve']);

        Route::get('platform-settings', [PlatformSettingController::class, 'index']);
        Route::post('platform-settings', [PlatformSettingController::class, 'store']);
        Route::get('platform-settings/{platformSetting}', [PlatformSettingController::class, 'show']);
        Route::put('platform-settings/{platformSetting}', [PlatformSettingController::class, 'update']);

        Route::get('admin-integrations', [AdminIntegrationController::class, 'index']);
        Route::post('admin-integrations', [AdminIntegrationController::class, 'store']);
        Route::get('admin-integrations/{adminIntegration}', [AdminIntegrationController::class, 'show']);
        Route::delete('admin-integrations/{adminIntegration}', [AdminIntegrationController::class, 'destroy']);

        Route::get('content-approvals', [ContentApprovalQueueController::class, 'index']);
        Route::get('content-approvals/{contentApprovalQueue}', [ContentApprovalQueueController::class, 'show']);
        Route::post('content-approvals/{contentApprovalQueue}/assign', [ContentApprovalQueueController::class, 'assign']);
        Route::post('content-approvals/{contentApprovalQueue}/approve', [ContentApprovalQueueController::class, 'approve']);
        Route::post('content-approvals/{contentApprovalQueue}/reject', [ContentApprovalQueueController::class, 'reject']);

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
});








