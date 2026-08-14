@extends('layouts.rebuild-dashboard')

@section('title', 'Platform Settings')

@section('content')
@php
    $settingsByGroup = collect($settingsByGroup ?? []);
    $settingsByKey = collect($settingsByKey ?? $settingsByGroup->flatten(1)->keyBy('key'));
    $viewErrors = $errors ?? new Illuminate\Support\ViewErrorBag;

    $settingValue = fn (string $key, mixed $default = ''): string => (string) old(str_replace('.', '_', $key), $settingsByKey->get($key)?->value ?? $default);
    $settingBool = fn (string $key, bool $default = false): bool => filter_var(old(str_replace('.', '_', $key), $settingsByKey->get($key)?->value ?? ($default ? '1' : '0')), FILTER_VALIDATE_BOOLEAN);

    $settingSections = [
        'general' => [
            'label' => 'General',
            'icon' => 'icon-general',
            'summary' => 'Platform identity and default regional settings.',
            'panels' => [
                ['fields' => [
                    ['key' => 'general.platform_name', 'label' => 'Platform name', 'description' => 'Shown in the browser tab, emails, and the top navigation bar.', 'type' => 'text', 'default' => 'Charley', 'class' => 'input-md'],
                    ['key' => 'general.support_email', 'label' => 'Support email', 'description' => 'Destination for the direct support communication channel.', 'type' => 'email', 'default' => 'support@charley.tech', 'class' => 'input-md'],
                    ['key' => 'general.default_timezone', 'label' => 'Default timezone', 'description' => 'Used for scheduling weekly digests, weekly themes, and displayed dates platform-wide.', 'type' => 'select', 'default' => 'Central European Time (CET)', 'class' => 'input-md', 'options' => ['Central European Time (CET)' => 'Central European Time (CET)', 'Gulf Standard Time (GST)' => 'Gulf Standard Time (GST)', 'Eastern Time (ET)' => 'Eastern Time (ET)', 'Coordinated Universal Time (UTC)' => 'Coordinated Universal Time (UTC)']],
                    ['key' => 'general.default_language', 'label' => 'Default language', 'description' => 'Applied to new accounts until the member changes it.', 'type' => 'select', 'default' => 'English', 'class' => 'input-md', 'options' => ['English' => 'English', 'Vietnamese' => 'Vietnamese', 'Arabic' => 'Arabic']],
                ]],
                ['fields' => [
                    ['key' => 'general.maintenance_mode', 'label' => 'Maintenance mode', 'description' => 'Temporarily restrict the platform to admins only while maintenance is active.', 'type' => 'boolean', 'default' => false],
                ]],
            ],
        ],
        'security' => [
            'label' => 'Security & Access',
            'icon' => 'icon-security-access',
            'summary' => 'Authentication, session, and access-control rules that apply to every account type.',
            'panels' => [
                ['fields' => [
                    ['key' => 'security.session_timeout_minutes', 'label' => 'Session timeout', 'description' => 'How long a member stays signed in without activity before re-authentication.', 'type' => 'select', 'default' => '240', 'class' => 'input-md', 'options' => ['30' => '30 minutes', '240' => '4 hours', '480' => '8 hours', '43200' => 'Persistent (until logout)']],
                    ['key' => 'security.max_login_attempts', 'label' => 'Max failed login attempts', 'description' => 'Incorrect password attempts allowed before temporary lockout.', 'type' => 'number', 'default' => '5', 'class' => 'input-sm'],
                    ['key' => 'security.lockout_minutes', 'label' => 'Lockout duration', 'description' => 'How long an account stays locked after exceeding the failed-login limit.', 'type' => 'number', 'default' => '15', 'class' => 'input-sm', 'unit' => 'minutes'],
                    ['key' => 'security.api_rate_limit_per_minute', 'label' => 'API rate limit', 'description' => 'Maximum requests allowed per user per minute across the public API.', 'type' => 'number', 'default' => '120', 'class' => 'input-sm', 'unit' => 'req / min'],
                ]],
                ['fields' => [
                    ['key' => 'security.admin_mfa_required', 'label' => 'Require multi-factor authentication for admins', 'description' => 'Future-ready capability currently in rollout for Super Admin accounts only.', 'type' => 'boolean', 'default' => false],
                    ['type' => 'locked', 'label' => 'Audit logging', 'description' => 'Records every admin action for compliance review.', 'locked_note' => 'Always on - required by platform security policy', 'checked' => true],
                    ['key' => 'security.audit_retention_days', 'label' => 'Audit log retention', 'description' => 'How long audit log entries are kept before automatic archival.', 'type' => 'number', 'default' => '365', 'class' => 'input-sm', 'unit' => 'days'],
                ]],
            ],
        ],
        'verification' => [
            'label' => 'Verification',
            'icon' => 'icon-verification-queue',
            'summary' => 'Rules governing how Registered Members become Verified Professionals.',
            'panels' => [
                ['fields' => [
                    ['key' => 'verification.expiry_months', 'label' => 'Verification validity period', 'description' => 'How long a professional verification stays valid before re-verification.', 'type' => 'number', 'default' => '24', 'class' => 'input-sm', 'unit' => 'months'],
                    ['key' => 'verification.reminder_days', 'label' => 'Re-verification reminder', 'description' => 'Days before expiry that renewal reminders start appearing.', 'type' => 'number', 'default' => '30', 'class' => 'input-sm', 'unit' => 'days'],
                    ['key' => 'verification.require_admin_approval', 'label' => 'Require admin approval for every verification request', 'description' => 'When off, requests matching an accepted document type are approved automatically.', 'type' => 'boolean', 'default' => true],
                ]],
                ['title' => 'Accepted verification documents', 'summary' => 'Members can submit any one of the enabled options below.', 'layout' => 'chips', 'fields' => [
                    ['key' => 'verification.doc_company_work_email', 'label' => 'Company work email', 'type' => 'boolean', 'default' => true],
                    ['key' => 'verification.doc_linkedin_profile', 'label' => 'LinkedIn profile', 'type' => 'boolean', 'default' => true],
                    ['key' => 'verification.doc_company_letter', 'label' => 'Company letter', 'type' => 'boolean', 'default' => true],
                    ['key' => 'verification.doc_university_letter', 'label' => 'University letter', 'type' => 'boolean', 'default' => true],
                    ['key' => 'verification.doc_justification_letter', 'label' => 'Justification letter', 'type' => 'boolean', 'default' => true],
                ]],
            ],
        ],
        'ai' => [
            'label' => 'Charley AI',
            'icon' => 'icon-ai-dataset',
            'summary' => 'Usage limits, knowledge sources, and governance for the Charley AI assistant.',
            'panels' => [
                ['fields' => [
                    ['key' => 'ai.monthly_quota_default', 'label' => 'Monthly AI usage limit per member', 'description' => 'Number of Charley AI queries a Verified Professional can send per month.', 'type' => 'number', 'default' => '100', 'class' => 'input-sm', 'unit' => 'queries'],
                    ['key' => 'ai.unlimited_subscription_enabled', 'label' => 'Unlimited AI usage via subscription', 'description' => 'Allow premium add-ons that remove the monthly query limit.', 'type' => 'boolean', 'default' => false],
                    ['key' => 'ai.retraining_schedule', 'label' => 'AI retraining schedule', 'description' => 'How often approved library and Q&A content enters the AI knowledge base.', 'type' => 'select', 'default' => 'Weekly', 'class' => 'input-md', 'options' => ['Manual' => 'Manual', 'Weekly' => 'Weekly', 'Monthly' => 'Monthly']],
                    ['key' => 'ai.require_content_approval', 'label' => 'Require admin approval before new content enters the AI knowledge base', 'description' => 'Prevents unverified content from being treated as authoritative by Charley AI.', 'type' => 'boolean', 'default' => true],
                    ['key' => 'ai.safety_lessons_only', 'label' => 'Restrict safety symposium content to lessons learned only', 'description' => 'Charley AI surfaces recommendations only for sources with uncertain reuse rights.', 'type' => 'boolean', 'default' => true],
                    ['key' => 'ai.alert_commercial_misuse', 'label' => 'Alert admin on suspected commercial misuse', 'description' => 'Flags AI conversations that look like advertising or unauthorized marketing.', 'type' => 'boolean', 'default' => true],
                ]],
                ['title' => 'Knowledge source categories', 'summary' => 'Charley AI must clearly label which categories an answer draws from.', 'layout' => 'source-tags', 'fields' => [
                    ['key' => 'ai.source_admin_verified', 'label' => 'Admin Verified', 'color' => '#3B82F6', 'type' => 'boolean', 'default' => true],
                    ['key' => 'ai.source_partner_provided', 'label' => 'Partner Provided', 'color' => '#F59E0B', 'type' => 'boolean', 'default' => true],
                    ['key' => 'ai.source_community_provided', 'label' => 'Community Provided', 'color' => '#10B981', 'type' => 'boolean', 'default' => true],
                    ['key' => 'ai.source_ai_generated', 'label' => 'AI Generated', 'color' => '#7C3AED', 'type' => 'boolean', 'default' => true],
                ]],
            ],
        ],
        'community' => [
            'label' => 'Q&A & Community',
            'icon' => 'icon-qa',
            'summary' => 'Posting rules and engagement mechanics for the Technical Q&A pillar.',
            'panels' => [
                ['fields' => [
                    ['key' => 'community.allow_anonymous_posting', 'label' => 'Allow anonymous posting', 'description' => 'Users can hide their identity from the community. Admins can always see who posted.', 'type' => 'boolean', 'default' => true],
                    ['type' => 'locked', 'label' => 'Allow general status posts for regular users', 'description' => 'Kept off - Charley is not a social feed. Members can only post technical questions.', 'locked_note' => 'Locked off by platform design', 'checked' => false],
                    ['key' => 'community.weekly_ask_expert_enabled', 'label' => 'Weekly "Ask the Expert" topic', 'description' => 'Feature one topic per week to give members a reason to post and answer.', 'type' => 'boolean', 'default' => true],
                ]],
                ['title' => 'Confidentiality reminder', 'summary' => 'Shown to every user right before they submit a technical question.', 'fields' => [
                    ['key' => 'community.confidentiality_reminder', 'label' => 'Confidentiality reminder', 'type' => 'textarea', 'default' => "Please don't share confidential plant data, company-sensitive information, or personal data. Consider simplifying numbers or removing company names before posting.", 'class' => 'form-textarea'],
                ]],
            ],
        ],
        'reputation' => [
            'label' => 'Reputation & Points',
            'icon' => 'icon-expert-recognition',
            'summary' => 'Point values and thresholds behind Contribution Rank and Monthly Expert Recognition.',
            'panels' => [
                ['title' => 'Activity points', 'summary' => 'Awarded purely for participation, regardless of answer quality.', 'fields' => [
                    ['key' => 'reputation.question_points', 'label' => 'Asking a question', 'type' => 'number', 'default' => '10', 'class' => 'input-sm', 'unit' => 'points'],
                    ['key' => 'reputation.answer_points', 'label' => 'Answering a question', 'type' => 'number', 'default' => '30', 'class' => 'input-sm', 'unit' => 'points'],
                    ['key' => 'reputation.improvement_points', 'label' => '"Help Improve Charley" submission', 'type' => 'number', 'default' => '50', 'class' => 'input-sm', 'unit' => 'points'],
                    ['key' => 'reputation.inactivity_deduction', 'label' => 'Inactivity deduction', 'description' => 'Points removed after each period of inactivity.', 'type' => 'number', 'default' => '500', 'class' => 'input-sm', 'unit' => 'pts / 2 months'],
                ]],
                ['title' => 'Contribution level thresholds', 'fields' => [
                    ['key' => 'reputation.active_threshold', 'label' => 'Active Contributor', 'type' => 'number', 'default' => '2500', 'class' => 'input-sm', 'unit' => 'pts'],
                    ['key' => 'reputation.leading_threshold', 'label' => 'Leading Contributor', 'type' => 'number', 'default' => '5000', 'class' => 'input-sm', 'unit' => 'pts'],
                    ['key' => 'reputation.top_threshold', 'label' => 'Top Contributor', 'type' => 'number', 'default' => '10000', 'class' => 'input-sm', 'unit' => 'pts'],
                    ['key' => 'reputation.monthly_winners', 'label' => 'Monthly Expert Recognition winners', 'description' => 'Maximum number of members recognized per month.', 'type' => 'number', 'default' => '10', 'class' => 'input-sm', 'unit' => 'winners'],
                ]],
            ],
        ],
        'quizzes' => [
            'label' => 'Quizzes',
            'icon' => 'icon-quiz-and-question-bank-charley',
            'summary' => 'Rules for section quizzes that unlock Expertise Rank.',
            'panels' => [[ 'fields' => [
                ['key' => 'quizzes.passing_threshold', 'label' => 'Passing threshold', 'type' => 'number', 'default' => '80', 'class' => 'input-sm', 'unit' => '%'],
                ['key' => 'quizzes.bank_size', 'label' => 'Question bank size per section', 'description' => "Total questions available for each library section's quiz pool.", 'type' => 'number', 'default' => '100', 'class' => 'input-sm', 'unit' => 'questions'],
                ['key' => 'quizzes.questions_per_attempt', 'label' => 'Questions per attempt', 'type' => 'number', 'default' => '50', 'class' => 'input-sm', 'unit' => 'questions'],
                ['key' => 'quizzes.retake_cooldown_hours', 'label' => 'Retake cooldown', 'description' => 'Wait time required after a failed attempt before the same quiz can be retaken.', 'type' => 'number', 'default' => '24', 'class' => 'input-sm', 'unit' => 'hours'],
                ['key' => 'quizzes.simplified_fallback_mode', 'label' => 'Simplified fallback mode', 'description' => 'Use 3 pre-built quizzes per section instead of the full randomized bank.', 'type' => 'boolean', 'default' => false],
            ]]],
        ],
        'partners' => [
            'label' => 'Partner Tiers & Billing',
            'icon' => 'icon-partners',
            'summary' => 'Annual pricing and payment rules for Diamond, Gold, and Platinum partners.',
            'panels' => [
                ['fields' => [
                    ['key' => 'partners.diamond_annual_price', 'label' => 'Diamond Partner - annual price', 'type' => 'number', 'default' => '12000', 'class' => 'input-sm', 'unit_prefix' => '$', 'dot_style' => 'background:linear-gradient(135deg,#38BDF8,#6366F1);'],
                    ['key' => 'partners.gold_annual_price', 'label' => 'Gold Partner - annual price', 'type' => 'number', 'default' => '6000', 'class' => 'input-sm', 'unit_prefix' => '$', 'dot_style' => 'background:linear-gradient(135deg,#F59E0B,#D97706);'],
                    ['key' => 'partners.platinum_annual_price', 'label' => 'Platinum Partner - annual price', 'type' => 'number', 'default' => '4000', 'class' => 'input-sm', 'unit_prefix' => '$', 'dot_style' => 'background:linear-gradient(135deg,#94A3B8,#475569);'],
                ]],
                ['fields' => [
                    ['key' => 'partners.require_admin_approval', 'label' => 'Require admin approval for new partner registrations', 'type' => 'boolean', 'default' => true],
                    ['type' => 'locked', 'label' => 'Accept bank transfer', 'locked_note' => 'Primary Stage-1 payment method', 'checked' => true],
                    ['key' => 'partners.accept_offline_payments', 'label' => 'Accept manual / offline payment records', 'description' => "Lets admins log payments received outside bank transfer from a partner's billing tab.", 'type' => 'boolean', 'default' => true],
                    ['key' => 'partners.auto_renew_subscriptions', 'label' => 'Auto-renew subscriptions', 'description' => 'Off in Stage 1 - renewals are reviewed and processed manually by admins.', 'type' => 'boolean', 'default' => false],
                ]],
            ],
        ],
        'notifications' => [
            'label' => 'Notifications',
            'icon' => 'icon-notifications-mark-all-read-s',
            'summary' => 'Delivery channels and digest cadence for platform-wide notifications.',
            'panels' => [[ 'fields' => [
                ['key' => 'notifications.email_enabled', 'label' => 'Email notifications', 'type' => 'boolean', 'default' => true],
                ['key' => 'notifications.in_app_enabled', 'label' => 'In-app notifications', 'type' => 'boolean', 'default' => true],
                ['key' => 'notifications.weekly_digest_enabled', 'label' => 'Weekly technical digest', 'type' => 'boolean', 'default' => true],
                ['key' => 'notifications.unanswered_alerts_enabled', 'label' => 'Unanswered question expert alerts', 'type' => 'boolean', 'default' => true],
                ['key' => 'notifications.help_upload_admin_notice', 'label' => 'Notify admin on every "Help Improve Charley" upload', 'type' => 'boolean', 'default' => true],
                ['key' => 'notifications.admin_digest_frequency', 'label' => 'Admin digest frequency', 'type' => 'select', 'default' => 'Daily', 'class' => 'input-md', 'options' => ['Real-time' => 'Real-time', 'Daily' => 'Daily', 'Weekly' => 'Weekly']],
            ]]],
        ],
    ];
@endphp

    @if (session('status'))
      <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($viewErrors->any())
      <div class="alert alert-danger">{{ $viewErrors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.dashboard.platform-settings.store') }}">
      @csrf

      <div class="page-head">
      <div class="page-head-title">
        <div class="page-head-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-settings-2"></use></svg></div>
        <div>
          <h1>Platform Settings</h1>
          <div class="sub">Configure global rules, limits, and governance for the Charley platform. Changes apply platform-wide the moment you save.</div>
        </div>
      </div>
      <span class="status-pill status-active"><span class="dot"></span>All systems operational</span>
    </div>

      <div class="settings-shell">
      <div class="settings-nav">
        @foreach ($settingSections as $sectionKey => $section)
          <div class="settings-nav-item @if ($loop->first) active @endif" data-tab="{{ $sectionKey }}">
            <svg class="icon"><use href="/assets/icons/sprite.svg#{{ $section['icon'] }}"></use></svg>
            {{ $section['label'] }}
          </div>
        @endforeach
        <div class="settings-nav-divider"></div>
        <div class="settings-nav-item danger" data-tab="danger">
          <svg class="icon"><use href="/assets/icons/sprite.svg#icon-danger-zone"></use></svg>
          Danger Zone
        </div>
      </div>

      <div class="settings-content">
        @foreach ($settingSections as $sectionKey => $section)
          <div class="settings-section @if ($loop->first) active @endif" id="settings-{{ $sectionKey }}">
            <div class="settings-section-head">
              <h2>{{ $section['label'] }}</h2>
              <div class="sub">{{ $section['summary'] }}</div>
            </div>

            @foreach ($section['panels'] as $panel)
              <div class="panel-card">
                @isset($panel['title'])<h3>{{ $panel['title'] }}</h3>@endisset
                @isset($panel['summary'])<div class="sub">{{ $panel['summary'] }}</div>@endisset

                @if (($panel['layout'] ?? null) === 'chips')
                  <div class="checkbox-chip-group">
                    @foreach ($panel['fields'] as $field)
                      @php $fieldName = $field['name'] ?? str_replace('.', '_', $field['key']); @endphp
                      <label class="checkbox-chip">
                        <input type="checkbox" name="{{ $fieldName }}" value="1" @checked($settingBool($field['key'], (bool) ($field['default'] ?? false)))>
                        <span class="checkbox-chip-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><use href="/assets/icons/sprite.svg#icon-diamond-diamond-partner-licensor"></use></svg></span>
                        {{ $field['label'] }}
                      </label>
                    @endforeach
                  </div>
                @elseif (($panel['layout'] ?? null) === 'source-tags')
                  @foreach ($panel['fields'] as $field)
                    @php $fieldName = $field['name'] ?? str_replace('.', '_', $field['key']); @endphp
                    <div class="source-tag-row">
                      <span class="source-dot" style="background:{{ $field['color'] }};"></span>
                      <span class="name">{{ $field['label'] }}</span>
                      <label class="switch"><input type="checkbox" name="{{ $fieldName }}" value="1" @checked($settingBool($field['key'], (bool) ($field['default'] ?? false)))><span class="slider"></span></label>
                    </div>
                  @endforeach
                @else
                  @foreach ($panel['fields'] as $field)
                    @php
                        $fieldType = $field['type'] ?? 'text';
                        $fieldName = isset($field['key']) ? ($field['name'] ?? str_replace('.', '_', $field['key'])) : null;
                        $fieldClass = $field['class'] ?? 'input-md';
                        $fieldDefault = $field['default'] ?? '';
                        $currentValue = $fieldName && $fieldType !== 'boolean' ? $settingValue($field['key'], $fieldDefault) : null;
                    @endphp

                    @if ($fieldType === 'textarea')
                      @isset($field['label'])<h3>{{ $field['label'] }}</h3>@endisset
                      @isset($field['description'])<div class="sub">{{ $field['description'] }}</div>@endisset
                      <textarea class="{{ $fieldClass }}" name="{{ $fieldName }}">{{ $currentValue }}</textarea>
                    @else
                      <div class="setting-row">
                        <div class="setting-row-text">
                          <div class="setting-row-label">@isset($field['dot_style'])<span class="tier-dot" style="display:inline-block;width:9px;height:9px;border-radius:50%;{{ $field['dot_style'] }}margin-right:7px;"></span>@endisset{{ $field['label'] }}</div>
                          @isset($field['description'])<div class="setting-row-desc">{{ $field['description'] }}</div>@endisset
                          @isset($field['locked_note'])<div class="locked-note"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-lock"></use></svg>{{ $field['locked_note'] }}</div>@endisset
                        </div>
                        <div class="setting-row-control">
                          @if ($fieldType === 'locked')
                            <label class="switch is-disabled"><input type="checkbox" @checked((bool) ($field['checked'] ?? false)) disabled><span class="slider"></span></label>
                          @elseif ($fieldType === 'boolean')
                            <label class="switch"><input type="checkbox" name="{{ $fieldName }}" value="1" @checked($settingBool($field['key'], (bool) $fieldDefault))><span class="slider"></span></label>
                          @elseif ($fieldType === 'select')
                            <select class="form-select {{ $fieldClass }}" name="{{ $fieldName }}">
                              @foreach ($field['options'] as $optionValue => $optionLabel)
                                <option value="{{ $optionValue }}" @selected((string) $currentValue === (string) $optionValue)>{{ $optionLabel }}</option>
                              @endforeach
                            </select>
                          @elseif (isset($field['unit']) || isset($field['unit_prefix']))
                            <div class="input-unit-wrap">
                              @isset($field['unit_prefix'])<span class="input-unit">{{ $field['unit_prefix'] }}</span>@endisset
                              <input type="{{ $fieldType }}" class="form-input {{ $fieldClass }}" name="{{ $fieldName }}" value="{{ $currentValue }}">
                              @isset($field['unit'])<span class="input-unit">{{ $field['unit'] }}</span>@endisset
                            </div>
                          @else
                            <input type="{{ $fieldType }}" class="form-input {{ $fieldClass }}" name="{{ $fieldName }}" value="{{ $currentValue }}">
                          @endif
                        </div>
                      </div>
                    @endif
                  @endforeach
                @endif
              </div>
            @endforeach
          </div>
        @endforeach

        <div class="settings-section" id="settings-danger">
          <div class="settings-section-head">
            <h2>Danger Zone</h2>
            <div class="sub">High-impact operations stay disabled until backend workflows are intentionally wired.</div>
          </div>
          <div class="danger-card">
            <div class="setting-row">
              <div class="setting-row-text"><div class="setting-row-label">Clear Charley AI knowledge cache</div><div class="setting-row-desc">Forces Charley AI to re-sync from approved sources on its next query.</div></div>
              <div class="setting-row-control"><button type="button" class="btn-danger-outline" onclick="openModal('clearcache')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-renew-subscription-extend-curren"></use></svg>Clear cache</button></div>
            </div>
            <div class="setting-row">
              <div class="setting-row-text"><div class="setting-row-label">Export all platform data</div><div class="setting-row-desc">Generates a full data export as a downloadable archive.</div></div>
              <div class="setting-row-control"><button type="button" class="btn-danger-outline" onclick="openModal('export')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-download-pdf"></use></svg>Export data</button></div>
            </div>
          </div>
        </div>
      </div>
    </div>

      <div class="save-bar" id="saveBar">
      <div class="save-bar-text"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-danger-zone"></use></svg>You have unsaved changes</div>
      <div class="save-bar-actions">
        <a class="btn-secondary" href="{{ route('admin.dashboard.platform-settings.index') }}">Discard</a>
        <button class="btn-primary" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-save-changes"></use></svg>Save changes</button>
      </div>
    </div>
    </form>

    <div class="modal-overlay" id="modalOverlay" onclick="closeModalOnOverlay(event)">
      <div class="modal-box" id="modal-clearcache">
        <div class="modal-head">
          <div class="modal-head-title"><div class="modal-head-icon" style="background:#FEF2F2;color:#DC2626;"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-renew-subscription-extend-curren"></use></svg></div><div><h3>Clear Charley AI knowledge cache?</h3><div class="sub">Display-only until the backend cache workflow is approved</div></div></div>
          <button type="button" class="modal-close" onclick="closeModal()"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-change-password-choose-strong"></use></svg></button>
        </div>
        <div class="modal-body">Charley AI cache clearing is intentionally disabled until a confirmed Admin Operations service contract exists.</div>
        <div class="modal-footer"><button type="button" class="modal-btn modal-btn-secondary" onclick="closeModal()">Close</button></div>
      </div>

      <div class="modal-box" id="modal-export">
        <div class="modal-head">
          <div class="modal-head-title"><div class="modal-head-icon" style="background:#EFF6FF;color:#1D4ED8;"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-download-pdf"></use></svg></div><div><h3>Export all platform data</h3><div class="sub">Display-only until secure export handling is approved</div></div></div>
          <button type="button" class="modal-close" onclick="closeModal()"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-change-password-choose-strong"></use></svg></button>
        </div>
        <div class="modal-body">Full platform data export is intentionally disabled until a confirmed secure export workflow exists.</div>
        <div class="modal-footer"><button type="button" class="modal-btn modal-btn-secondary" onclick="closeModal()">Close</button></div>
      </div>
    </div>
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/pages/platform-settings.js') }}"></script>
@endpush
