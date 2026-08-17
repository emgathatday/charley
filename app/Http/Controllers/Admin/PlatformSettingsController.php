<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlatformSettingsController extends Controller
{
    public function index(): View
    {
        $settings = Schema::hasTable('platform_settings')
            ? PlatformSetting::query()
                ->whereIn('group', $this->groups())
                ->orderBy('group')
                ->orderBy('key')
                ->get(['key', 'value', 'group', 'description'])
            : collect();

        return view('admin.operations.platform-settings', [
            'settingsByGroup' => $settings->groupBy('group')->map(fn (Collection $items) => $items->keyBy('key')),
            'settingsByKey' => $settings->keyBy('key'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Schema::hasTable('platform_settings'), 503, 'Database table [platform_settings] is not available.');

        $validated = $request->validate([
            'general_platform_name' => ['required', 'string', 'max:120'],
            'general_support_email' => ['required', 'email', 'max:255'],
            'general_default_timezone' => ['required', Rule::in(['Central European Time (CET)', 'Gulf Standard Time (GST)', 'Eastern Time (ET)', 'Coordinated Universal Time (UTC)'])],
            'general_default_language' => ['required', Rule::in(['English', 'Vietnamese', 'Arabic'])],
            'security_session_timeout_minutes' => ['required', 'integer', 'min:30', 'max:43200'],
            'security_max_login_attempts' => ['required', 'integer', 'min:1', 'max:20'],
            'security_lockout_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'security_api_rate_limit_per_minute' => ['required', 'integer', 'min:1', 'max:100000'],
            'security_audit_retention_days' => ['required', 'integer', 'min:30', 'max:3650'],
            'verification_expiry_months' => ['required', 'integer', 'min:1', 'max:120'],
            'verification_reminder_days' => ['required', 'integer', 'min:1', 'max:365'],
            'ai_monthly_quota_default' => ['required', 'integer', 'min:0', 'max:1000000'],
            'ai_retraining_schedule' => ['required', Rule::in(['Manual', 'Weekly', 'Monthly'])],
            'community_confidentiality_reminder' => ['required', 'string', 'max:1000'],
            'reputation_question_points' => ['required', 'integer', 'min:0', 'max:100000'],
            'reputation_answer_points' => ['required', 'integer', 'min:0', 'max:100000'],
            'reputation_improvement_points' => ['required', 'integer', 'min:0', 'max:100000'],
            'reputation_inactivity_deduction' => ['required', 'integer', 'min:0', 'max:100000'],
            'reputation_active_threshold' => ['required', 'integer', 'min:0', 'max:10000000'],
            'reputation_leading_threshold' => ['required', 'integer', 'min:0', 'max:10000000'],
            'reputation_top_threshold' => ['required', 'integer', 'min:0', 'max:10000000'],
            'reputation_monthly_winners' => ['required', 'integer', 'min:1', 'max:1000'],
            'quizzes_passing_threshold' => ['required', 'integer', 'min:1', 'max:100'],
            'quizzes_bank_size' => ['required', 'integer', 'min:1', 'max:10000'],
            'quizzes_questions_per_attempt' => ['required', 'integer', 'min:1', 'max:10000'],
            'quizzes_retake_cooldown_hours' => ['required', 'integer', 'min:0', 'max:8760'],
            'partners_diamond_annual_price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'partners_gold_annual_price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'partners_platinum_annual_price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'notifications_admin_digest_frequency' => ['required', Rule::in(['Real-time', 'Daily', 'Weekly'])],
        ]);

        foreach ($this->settingDefinitions() as $input => $definition) {
            $value = $definition['type'] === 'boolean'
                ? ($request->boolean($input) ? '1' : '0')
                : (string) $validated[$input];

            PlatformSetting::query()->updateOrCreate(
                ['key' => $definition['key']],
                [
                    'value' => $value,
                    'group' => $definition['group'],
                    'description' => $definition['description'],
                ]
            );
        }

        return redirect()->route('admin.dashboard.platform-settings.index')->with('status', 'Platform settings saved.');
    }

    private function groups(): array
    {
        return ['general', 'security', 'verification', 'ai', 'community', 'reputation', 'quizzes', 'partners', 'notifications', 'danger', 'support', 'approval', 'integrations'];
    }

    private function settingDefinitions(): array
    {
        return [
            'general_platform_name' => ['key' => 'general.platform_name', 'group' => 'general', 'type' => 'string', 'description' => 'Shown in the browser tab, emails, and navigation.'],
            'general_support_email' => ['key' => 'general.support_email', 'group' => 'general', 'type' => 'string', 'description' => 'Destination for direct support communication.'],
            'general_default_timezone' => ['key' => 'general.default_timezone', 'group' => 'general', 'type' => 'string', 'description' => 'Default timezone for scheduling and displayed dates.'],
            'general_default_language' => ['key' => 'general.default_language', 'group' => 'general', 'type' => 'string', 'description' => 'Default language for new accounts.'],
            'general_maintenance_mode' => ['key' => 'general.maintenance_mode', 'group' => 'general', 'type' => 'boolean', 'description' => 'Restrict platform access to admins only.'],
            'security_session_timeout_minutes' => ['key' => 'security.session_timeout_minutes', 'group' => 'security', 'type' => 'integer', 'description' => 'Inactive session timeout in minutes.'],
            'security_max_login_attempts' => ['key' => 'security.max_login_attempts', 'group' => 'security', 'type' => 'integer', 'description' => 'Failed login attempts before temporary lockout.'],
            'security_lockout_minutes' => ['key' => 'security.lockout_minutes', 'group' => 'security', 'type' => 'integer', 'description' => 'Temporary lockout duration in minutes.'],
            'security_api_rate_limit_per_minute' => ['key' => 'security.api_rate_limit_per_minute', 'group' => 'security', 'type' => 'integer', 'description' => 'Public API rate limit per user per minute.'],
            'security_admin_mfa_required' => ['key' => 'security.admin_mfa_required', 'group' => 'security', 'type' => 'boolean', 'description' => 'Require multi-factor authentication for admins.'],
            'security_audit_retention_days' => ['key' => 'security.audit_retention_days', 'group' => 'security', 'type' => 'integer', 'description' => 'Audit log retention period in days.'],
            'verification_expiry_months' => ['key' => 'verification.expiry_months', 'group' => 'verification', 'type' => 'integer', 'description' => 'Profile verification expiry window in months.'],
            'verification_reminder_days' => ['key' => 'verification.reminder_days', 'group' => 'verification', 'type' => 'integer', 'description' => 'Days before expiry to show renewal reminders.'],
            'verification_require_admin_approval' => ['key' => 'verification.require_admin_approval', 'group' => 'verification', 'type' => 'boolean', 'description' => 'Require admin approval for verification requests.'],
            'verification_doc_company_work_email' => ['key' => 'verification.doc_company_work_email', 'group' => 'verification', 'type' => 'boolean', 'description' => 'Accept company work email as verification evidence.'],
            'verification_doc_linkedin_profile' => ['key' => 'verification.doc_linkedin_profile', 'group' => 'verification', 'type' => 'boolean', 'description' => 'Accept LinkedIn profile as verification evidence.'],
            'verification_doc_company_letter' => ['key' => 'verification.doc_company_letter', 'group' => 'verification', 'type' => 'boolean', 'description' => 'Accept company letter as verification evidence.'],
            'verification_doc_university_letter' => ['key' => 'verification.doc_university_letter', 'group' => 'verification', 'type' => 'boolean', 'description' => 'Accept university letter as verification evidence.'],
            'verification_doc_justification_letter' => ['key' => 'verification.doc_justification_letter', 'group' => 'verification', 'type' => 'boolean', 'description' => 'Accept justification letter as verification evidence.'],
            'ai_monthly_quota_default' => ['key' => 'ai.monthly_quota_default', 'group' => 'ai', 'type' => 'integer', 'description' => 'Default monthly Charley AI query quota.'],
            'ai_unlimited_subscription_enabled' => ['key' => 'ai.unlimited_subscription_enabled', 'group' => 'ai', 'type' => 'boolean', 'description' => 'Allow subscription-based unlimited AI usage.'],
            'ai_retraining_schedule' => ['key' => 'ai.retraining_schedule', 'group' => 'ai', 'type' => 'string', 'description' => 'AI retraining schedule.'],
            'ai_require_content_approval' => ['key' => 'ai.require_content_approval', 'group' => 'ai', 'type' => 'boolean', 'description' => 'Require approval before content enters the AI knowledge base.'],
            'ai_safety_lessons_only' => ['key' => 'ai.safety_lessons_only', 'group' => 'ai', 'type' => 'boolean', 'description' => 'Restrict safety symposium sources to lessons learned.'],
            'ai_alert_commercial_misuse' => ['key' => 'ai.alert_commercial_misuse', 'group' => 'ai', 'type' => 'boolean', 'description' => 'Alert admins on suspected commercial misuse.'],
            'ai_source_admin_verified' => ['key' => 'ai.source_admin_verified', 'group' => 'ai', 'type' => 'boolean', 'description' => 'Enable Admin Verified knowledge source labels.'],
            'ai_source_partner_provided' => ['key' => 'ai.source_partner_provided', 'group' => 'ai', 'type' => 'boolean', 'description' => 'Enable Partner Provided knowledge source labels.'],
            'ai_source_community_provided' => ['key' => 'ai.source_community_provided', 'group' => 'ai', 'type' => 'boolean', 'description' => 'Enable Community Provided knowledge source labels.'],
            'ai_source_ai_generated' => ['key' => 'ai.source_ai_generated', 'group' => 'ai', 'type' => 'boolean', 'description' => 'Enable AI Generated knowledge source labels.'],
            'community_allow_anonymous_posting' => ['key' => 'community.allow_anonymous_posting', 'group' => 'community', 'type' => 'boolean', 'description' => 'Allow anonymous posting in community areas.'],
            'community_weekly_ask_expert_enabled' => ['key' => 'community.weekly_ask_expert_enabled', 'group' => 'community', 'type' => 'boolean', 'description' => 'Enable weekly Ask the Expert topic.'],
            'community_confidentiality_reminder' => ['key' => 'community.confidentiality_reminder', 'group' => 'community', 'type' => 'string', 'description' => 'Reminder shown before technical question submission.'],
            'reputation_question_points' => ['key' => 'reputation.question_points', 'group' => 'reputation', 'type' => 'integer', 'description' => 'Points awarded for asking a question.'],
            'reputation_answer_points' => ['key' => 'reputation.answer_points', 'group' => 'reputation', 'type' => 'integer', 'description' => 'Points awarded for answering a question.'],
            'reputation_improvement_points' => ['key' => 'reputation.improvement_points', 'group' => 'reputation', 'type' => 'integer', 'description' => 'Points awarded for Help Improve Charley submissions.'],
            'reputation_inactivity_deduction' => ['key' => 'reputation.inactivity_deduction', 'group' => 'reputation', 'type' => 'integer', 'description' => 'Points deducted after inactivity.'],
            'reputation_active_threshold' => ['key' => 'reputation.active_threshold', 'group' => 'reputation', 'type' => 'integer', 'description' => 'Active Contributor threshold.'],
            'reputation_leading_threshold' => ['key' => 'reputation.leading_threshold', 'group' => 'reputation', 'type' => 'integer', 'description' => 'Leading Contributor threshold.'],
            'reputation_top_threshold' => ['key' => 'reputation.top_threshold', 'group' => 'reputation', 'type' => 'integer', 'description' => 'Top Contributor threshold.'],
            'reputation_monthly_winners' => ['key' => 'reputation.monthly_winners', 'group' => 'reputation', 'type' => 'integer', 'description' => 'Monthly Expert Recognition winner count.'],
            'quizzes_passing_threshold' => ['key' => 'quizzes.passing_threshold', 'group' => 'quizzes', 'type' => 'integer', 'description' => 'Required quiz passing percentage.'],
            'quizzes_bank_size' => ['key' => 'quizzes.bank_size', 'group' => 'quizzes', 'type' => 'integer', 'description' => 'Question bank size per section.'],
            'quizzes_questions_per_attempt' => ['key' => 'quizzes.questions_per_attempt', 'group' => 'quizzes', 'type' => 'integer', 'description' => 'Questions shown per quiz attempt.'],
            'quizzes_retake_cooldown_hours' => ['key' => 'quizzes.retake_cooldown_hours', 'group' => 'quizzes', 'type' => 'integer', 'description' => 'Retake cooldown in hours.'],
            'quizzes_simplified_fallback_mode' => ['key' => 'quizzes.simplified_fallback_mode', 'group' => 'quizzes', 'type' => 'boolean', 'description' => 'Use simplified fallback quiz pools.'],
            'partners_diamond_annual_price' => ['key' => 'partners.diamond_annual_price', 'group' => 'partners', 'type' => 'integer', 'description' => 'Diamond Partner annual price.'],
            'partners_gold_annual_price' => ['key' => 'partners.gold_annual_price', 'group' => 'partners', 'type' => 'integer', 'description' => 'Gold Partner annual price.'],
            'partners_platinum_annual_price' => ['key' => 'partners.platinum_annual_price', 'group' => 'partners', 'type' => 'integer', 'description' => 'Platinum Partner annual price.'],
            'partners_require_admin_approval' => ['key' => 'partners.require_admin_approval', 'group' => 'partners', 'type' => 'boolean', 'description' => 'Require admin approval for new partner registrations.'],
            'partners_accept_offline_payments' => ['key' => 'partners.accept_offline_payments', 'group' => 'partners', 'type' => 'boolean', 'description' => 'Allow admins to record offline payments.'],
            'partners_auto_renew_subscriptions' => ['key' => 'partners.auto_renew_subscriptions', 'group' => 'partners', 'type' => 'boolean', 'description' => 'Enable subscription auto-renewal.'],
            'notifications_email_enabled' => ['key' => 'notifications.email_enabled', 'group' => 'notifications', 'type' => 'boolean', 'description' => 'Enable email notifications.'],
            'notifications_in_app_enabled' => ['key' => 'notifications.in_app_enabled', 'group' => 'notifications', 'type' => 'boolean', 'description' => 'Enable in-app notifications.'],
            'notifications_weekly_digest_enabled' => ['key' => 'notifications.weekly_digest_enabled', 'group' => 'notifications', 'type' => 'boolean', 'description' => 'Enable weekly technical digest.'],
            'notifications_unanswered_alerts_enabled' => ['key' => 'notifications.unanswered_alerts_enabled', 'group' => 'notifications', 'type' => 'boolean', 'description' => 'Enable unanswered question expert alerts.'],
            'notifications_help_upload_admin_notice' => ['key' => 'notifications.help_upload_admin_notice', 'group' => 'notifications', 'type' => 'boolean', 'description' => 'Notify admins on Help Improve Charley uploads.'],
            'notifications_admin_digest_frequency' => ['key' => 'notifications.admin_digest_frequency', 'group' => 'notifications', 'type' => 'string', 'description' => 'Admin digest cadence.'],
        ];
    }
}