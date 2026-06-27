<?php

namespace Database\Seeders;

use App\Models\LoginToken;
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

        LoginToken::firstOrCreate(
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

        VerificationRequest::firstOrCreate(
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

        VerificationRequest::firstOrCreate(
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
