<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class AdminOperationsSeeder extends Seeder
{
    public function run(): void
    {
        $settingModel = new class extends Model
        {
            protected $table = 'platform_settings';
            protected $guarded = [];
        };

        foreach ($this->platformSettings() as $setting) {
            $settingModel->newQuery()->updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                    'description' => $setting['description'],
                ]
            );
        }

        $admin = User::query()->where('role', 'admin')->orderBy('id')->first();
        $user = User::query()->orderBy('id')->first();

        if (! $admin || ! $user) {
            return;
        }

        $ticketModel = new class extends Model
        {
            protected $table = 'support_tickets';
            protected $guarded = [];
            protected $casts = ['resolved_at' => 'datetime'];
        };

        $replyModel = new class extends Model
        {
            protected $table = 'support_ticket_replies';
            protected $guarded = [];
        };

        $penaltyModel = new class extends Model
        {
            protected $table = 'account_penalties';
            protected $guarded = [];
            protected $casts = ['evidence_ref' => 'array', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
        };

        $integrationModel = new class extends Model
        {
            protected $table = 'admin_integrations';
            protected $guarded = [];
            protected $casts = ['config_metadata' => 'array', 'token_expires_at' => 'datetime'];
        };

        $approvalModel = new class extends Model
        {
            protected $table = 'content_approval_queue';
            protected $guarded = [];
            protected $casts = ['submitted_at' => 'datetime', 'reviewed_at' => 'datetime'];
        };

        $ticket = $ticketModel->newQuery()->firstOrCreate(
            ['user_id' => $user->id, 'subject' => 'Demo subscription support request'],
            [
                'category' => 'subscription_support',
                'priority' => 'normal',
                'status' => 'open',
                'description' => 'Demo support ticket for admin operations review.',
                'assigned_to' => $admin->id,
            ]
        );

        $replyModel->newQuery()->firstOrCreate(
            ['ticket_id' => $ticket->id, 'sender_id' => $admin->id, 'content' => 'Demo internal triage note for support workflow.'],
            ['is_internal_note' => true]
        );

        $penaltyModel->newQuery()->firstOrCreate(
            ['user_id' => $user->id, 'action_type' => 'warning', 'starts_at' => now()->startOfDay()],
            [
                'reason' => 'Demo warning record for admin audit trail.',
                'evidence_ref' => ['source' => 'demo_seed'],
                'admin_id' => $admin->id,
            ]
        );

        $integrationModel->newQuery()->firstOrCreate(
            ['user_id' => $admin->id, 'provider' => 'gmail'],
            [
                'access_token' => 'demo-access-token',
                'refresh_token' => 'demo-refresh-token',
                'token_expires_at' => now()->addDays(7),
                'config_metadata' => ['mailbox' => 'admin@example.test'],
            ]
        );

        $approvalModel->newQuery()->firstOrCreate(
            ['approvable_type' => 'demo_article', 'approvable_id' => 1],
            [
                'submitted_by' => $user->id,
                'submitter_tier' => 'demo',
                'content_title' => 'Demo content approval item',
                'content_type_label' => 'Article',
                'priority' => 'normal',
                'status' => 'pending',
                'assigned_to' => $admin->id,
                'submitted_at' => now(),
            ]
        );
    }

    private function platformSettings(): array
    {
        return [
            ['key' => 'general.platform_name', 'value' => 'Charley', 'group' => 'general', 'description' => 'Shown in the browser tab, emails, and navigation.'],
            ['key' => 'general.support_email', 'value' => 'support@charley.tech', 'group' => 'general', 'description' => 'Destination for direct support communication.'],
            ['key' => 'general.default_timezone', 'value' => 'Central European Time (CET)', 'group' => 'general', 'description' => 'Default timezone for scheduling and displayed dates.'],
            ['key' => 'general.default_language', 'value' => 'English', 'group' => 'general', 'description' => 'Default language for new accounts.'],
            ['key' => 'general.maintenance_mode', 'value' => '0', 'group' => 'general', 'description' => 'Restrict platform access to admins only.'],
            ['key' => 'security.session_timeout_minutes', 'value' => '240', 'group' => 'security', 'description' => 'Inactive session timeout in minutes.'],
            ['key' => 'security.max_login_attempts', 'value' => '5', 'group' => 'security', 'description' => 'Failed login attempts before temporary lockout.'],
            ['key' => 'verification.expiry_months', 'value' => '24', 'group' => 'verification', 'description' => 'Profile verification expiry window in months.'],
            ['key' => 'ai.monthly_quota_default', 'value' => '100', 'group' => 'ai', 'description' => 'Default monthly Charley AI query quota.'],
            ['key' => 'community.moderation_required', 'value' => '1', 'group' => 'community', 'description' => 'Require moderation before community content becomes visible.'],
            ['key' => 'reputation.points_enabled', 'value' => '1', 'group' => 'reputation', 'description' => 'Enable reputation point accrual.'],
            ['key' => 'quizzes.passing_threshold', 'value' => '80', 'group' => 'quizzes', 'description' => 'Required quiz passing percentage.'],
            ['key' => 'partners.require_admin_approval', 'value' => '1', 'group' => 'partners', 'description' => 'Require admin approval for new partner registrations.'],
            ['key' => 'notifications.email_enabled', 'value' => '1', 'group' => 'notifications', 'description' => 'Enable email notifications.'],
            ['key' => 'notifications.in_app_enabled', 'value' => '1', 'group' => 'notifications', 'description' => 'Enable in-app notifications.'],
            ['key' => 'notifications.admin_digest_frequency', 'value' => 'Daily', 'group' => 'notifications', 'description' => 'Admin digest cadence.'],
            ['key' => 'support.auto_assign_enabled', 'value' => 'true', 'group' => 'support', 'description' => 'Auto assign support tickets to available admins.'],
            ['key' => 'approval.default_priority', 'value' => 'normal', 'group' => 'approval', 'description' => 'Default priority for content approval queue items.'],
            ['key' => 'integrations.email_provider', 'value' => 'gmail', 'group' => 'integrations', 'description' => 'Default admin email integration provider.'],
        ];
    }
}