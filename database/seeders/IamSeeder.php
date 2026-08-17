<?php

namespace Database\Seeders;

use App\Models\LoginToken;
use App\Models\PartnerProfile;
use App\Models\PartnerSubscription;
use App\Models\SubscriptionTier;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\UserActivityFeed;
use App\Models\UserMeta;
use App\Models\VerificationReminderSchedule;
use App\Models\VerificationRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class IamSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.test'],
            [
                'username' => 'admin',
                'first_name' => 'System',
                'last_name' => 'Admin',
                'password' => Hash::make(Str::password(32)),
                'role' => 'admin',
                'is_verified' => true,
                'verified_at' => now(),
                'verification_expires_at' => null,
                'status' => 'active',
                'last_login_at' => null,
                'login_attempts' => 0,
                'locked_until' => null,
                'mfa_enabled' => false,
                'mfa_secret' => null,
                'mfa_recovery_codes' => null,
                'self_frozen_at' => null,
            ]
        );

        $member = User::firstOrCreate(
            ['email' => 'member@example.test'],
            [
                'username' => 'sample-member',
                'first_name' => 'Sample',
                'last_name' => 'Member',
                'password' => Hash::make(Str::password(32)),
                'role' => 'unverified_member',
                'is_verified' => false,
                'verified_at' => null,
                'verification_expires_at' => null,
                'status' => 'active',
                'last_login_at' => null,
                'login_attempts' => 0,
                'locked_until' => null,
                'mfa_enabled' => false,
                'mfa_secret' => null,
                'mfa_recovery_codes' => null,
                'self_frozen_at' => null,
            ]
        );

        $professional = User::firstOrCreate(
            ['email' => 'professional@example.test'],
            [
                'username' => 'sample-professional',
                'first_name' => 'Sample',
                'last_name' => 'Professional',
                'password' => Hash::make(Str::password(32)),
                'role' => 'professional',
                'is_verified' => true,
                'verified_at' => now()->subMonth(),
                'verification_expires_at' => now()->addMonths(11),
                'status' => 'active',
                'last_login_at' => now()->subDays(3),
                'login_attempts' => 0,
                'locked_until' => null,
                'mfa_enabled' => true,
                'mfa_secret' => Hash::make(Str::random(40)),
                'mfa_recovery_codes' => [
                    hash('sha256', Str::random(32)),
                    hash('sha256', Str::random(32)),
                ],
                'self_frozen_at' => null,
            ]
        );

        $partner = User::updateOrCreate(
            ['email' => 'partner-verification@example.test'],
            [
                'username' => 'sample-partner-queue',
                'first_name' => 'Demo',
                'last_name' => 'Partner',
                'password' => Hash::make(Str::password(32)),
                'role' => 'partner',
                'is_verified' => false,
                'verified_at' => null,
                'verification_expires_at' => null,
                'status' => 'active',
                'last_login_at' => now()->subDay(),
                'login_attempts' => 0,
                'locked_until' => null,
                'mfa_enabled' => false,
                'mfa_secret' => null,
                'mfa_recovery_codes' => null,
                'self_frozen_at' => null,
            ]
        );

        $partnerSecondary = User::updateOrCreate(
            ['email' => 'partner-verification-secondary@example.test'],
            [
                'username' => 'sample-partner-queue-2',
                'first_name' => 'Second',
                'last_name' => 'Partner',
                'password' => Hash::make(Str::password(32)),
                'role' => 'partner',
                'is_verified' => false,
                'verified_at' => null,
                'verification_expires_at' => null,
                'status' => 'active',
                'last_login_at' => now()->subHours(8),
                'login_attempts' => 0,
                'locked_until' => null,
                'mfa_enabled' => false,
                'mfa_secret' => null,
                'mfa_recovery_codes' => null,
                'self_frozen_at' => null,
            ]
        );        LoginToken::firstOrCreate(
            [
                'user_id' => $member->id,
                'type' => 'email_verify',
                'is_used' => false,
            ],
            [
                'token' => hash('sha256', Str::random(64)),
                'expires_at' => now()->addMinutes(30),
                'created_at' => now(),
            ]
        );

        SocialAccount::firstOrCreate(
            [
                'user_id' => $professional->id,
                'provider_name' => 'linkedin',
            ],
            [
                'provider_id' => Str::uuid()->toString(),
                'is_active' => true,
            ]
        );

        $partnerTier = SubscriptionTier::query()->where('code', 'gold')->first();
        $partnerSubscription = null;

        if ($partnerTier) {
            $partnerSubscription = PartnerSubscription::firstOrCreate(
                [
                    'user_id' => $partner->id,
                    'tier_id' => $partnerTier->id,
                    'starts_at' => now()->startOfMonth(),
                ],
                [
                    'status' => 'pending_approval',
                    'auto_renew' => false,
                    'approved_by' => null,
                    'approved_at' => null,
                    'ends_at' => now()->startOfMonth()->addMonth(),
                ]
            );
        }

        PartnerProfile::updateOrCreate(
            ['user_id' => $partner->id],
            [
                'company_name' => 'Charley Demo Partner Co.',
                'logo_media_id' => null,
                'overview' => 'Demo partner verification profile for the IAM verification queue.',
                'partner_tier' => 'gold',
                'company_type' => 'Technology vendor',
                'active_partner_subscription_id' => $partnerSubscription?->id,
                'keywords' => ['technology', 'vendor', 'verification'],
                'references' => [
                    ['project' => 'Demo refinery reliability program', 'year' => now()->year - 1],
                ],
                'contact_email' => 'partner-verification@example.test',
                'phone' => '+1 555 010 1099',
                'address' => '100 Demo Partner Avenue',
                'country' => 'United States',
                'website' => 'https://partner-verification.example.test',
                'founded_year' => 2018,
                'social_links' => ['linkedin' => 'https://linkedin.com/company/charley-demo-partner'],
                'layout_template' => 'layout_1',
                'feed_highlight_enabled' => true,
                'subscription_status' => $partnerSubscription ? 'inactive' : 'inactive',
                'subscription_expires_at' => null,
                'approval_status' => 'pending',
                'verified_at' => null,
            ]
        );

        $partnerSecondaryTier = SubscriptionTier::query()->where('code', 'diamond')->first() ?: $partnerTier;
        $partnerSecondarySubscription = null;

        if ($partnerSecondaryTier) {
            $partnerSecondarySubscription = PartnerSubscription::firstOrCreate(
                [
                    'user_id' => $partnerSecondary->id,
                    'tier_id' => $partnerSecondaryTier->id,
                    'starts_at' => now()->startOfMonth(),
                ],
                [
                    'status' => 'pending_approval',
                    'auto_renew' => false,
                    'approved_by' => null,
                    'approved_at' => null,
                    'ends_at' => now()->startOfMonth()->addMonth(),
                ]
            );
        }

        PartnerProfile::updateOrCreate(
            ['user_id' => $partnerSecondary->id],
            [
                'company_name' => 'Charley Secondary Partner Ltd.',
                'logo_media_id' => null,
                'overview' => 'Second demo partner verification profile for checking partner detail dispatch.',
                'partner_tier' => $partnerSecondaryTier?->code === 'diamond' ? 'diamond' : 'gold',
                'company_type' => 'Licensor',
                'active_partner_subscription_id' => $partnerSecondarySubscription?->id,
                'keywords' => ['licensor', 'process technology', 'partner verification'],
                'references' => [
                    ['project' => 'Demo ammonia revamp study', 'year' => now()->year - 2],
                ],
                'contact_email' => 'partner-verification-secondary@example.test',
                'phone' => '+1 555 010 2099',
                'address' => '200 Demo Licensor Road',
                'country' => 'United States',
                'website' => 'https://partner-verification-secondary.example.test',
                'founded_year' => 2012,
                'social_links' => ['linkedin' => 'https://linkedin.com/company/charley-secondary-partner'],
                'layout_template' => 'layout_2',
                'feed_highlight_enabled' => true,
                'subscription_status' => 'inactive',
                'subscription_expires_at' => null,
                'approval_status' => 'pending',
                'verified_at' => null,
            ]
        );        VerificationRequest::firstOrCreate(
            [
                'user_id' => $member->id,
                'submission_type' => 'initial',
            ],
            [
                'verification_method' => 'work_email',
                'document_media_ids' => null,
                'notes' => 'Sample pending verification request.',
                'status' => 'pending',
                'admin_notes' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]
        );


        VerificationRequest::updateOrCreate(
            [
                'user_id' => $partner->id,
                'submission_type' => 'initial',
            ],
            [
                'verification_method' => 'company_letter',
                'document_media_ids' => null,
                'notes' => 'Sample pending partner verification request.',
                'status' => 'pending',
                'admin_notes' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]
        );

        VerificationRequest::updateOrCreate(
            [
                'user_id' => $partnerSecondary->id,
                'submission_type' => 'initial',
            ],
            [
                'verification_method' => 'justification_letter',
                'document_media_ids' => null,
                'notes' => 'Second sample pending partner verification request.',
                'status' => 'pending',
                'admin_notes' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]
        );        VerificationRequest::firstOrCreate(
            [
                'user_id' => $professional->id,
                'submission_type' => 'renewal',
            ],
            [
                'verification_method' => 'linkedin',
                'document_media_ids' => null,
                'notes' => 'Sample approved renewal request.',
                'status' => 'approved',
                'admin_notes' => 'Approved sample verification.',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now()->subMonth(),
            ]
        );

        VerificationReminderSchedule::firstOrCreate(
            [
                'user_id' => $professional->id,
                'reminder_type' => '30_days_before',
            ],
            [
                'scheduled_at' => now()->addMonths(10),
                'sent_at' => null,
                'status' => 'pending',
            ]
        );

        UserActivityFeed::firstOrCreate(
            [
                'user_id' => $professional->id,
                'activity_type' => 'contribution_approved',
            ],
            [
                'subject_type' => null,
                'subject_id' => null,
                'is_public' => true,
                'created_at' => now()->subDays(7),
            ]
        );

        UserMeta::firstOrCreate(
            [
                'user_id' => $professional->id,
                'key' => 'timezone',
            ],
            [
                'value' => 'UTC',
            ]
        );
    }
}
