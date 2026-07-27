@extends('layouts.rebuild-dashboard')

@section('title', 'Admin Profile')

@section('content')
@php
    $allowedSections = ['profile', 'account', 'notifications', 'security', 'sessions', 'activity'];
    $activeSection = in_array(request('section', 'profile'), $allowedSections, true) ? request('section', 'profile') : 'profile';
    $sectionClass = fn (string $section): string => $activeSection === $section ? 'admin-section' : 'admin-section is-hidden';
    $navClass = fn (string $section): string => $activeSection === $section ? 'snav-item active' : 'snav-item';
    $status = ucfirst((string) ($admin->status ?? 'active'));
    $statusPill = ($admin->status ?? 'active') === 'active' ? 'pill ok' : 'pill warn';
    $lastLogin = $admin->last_login_at?->format('d M Y H:i') ?? 'Never';
    $memberSince = $admin->created_at?->format('d F Y') ?? 'Unknown';
    $roleLabel = Illuminate\Support\Str::headline((string) ($admin->role ?: 'admin'));
    $mfaLabel = $admin->mfa_enabled ? 'Enabled' : 'Disabled';
    $mfaPill = $admin->mfa_enabled ? 'pill ok' : 'pill warn';
    $currentSessionSummary = $latestSession
        ? trim(($latestSession->ip_address ?? 'Unknown IP').' - '.($latestSession->user_agent ?? 'Unknown browser'))
        : 'TODO-safe boundary - current session metadata pending';
@endphp

<div class="page-heading">
    <h1>My Profile &amp; Settings</h1>
    <p>Manage your admin account, security preferences, and notification settings.</p>
</div>

<div class="row g-4 settings-layout">
    <div class="settings-nav col-12 col-lg-3">
        <div class="snav-profile">
            <div class="snav-avatar">{{ $initials }}</div>
            <div class="snav-name">{{ $displayName }}</div>
            <div class="snav-role">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-penalty-history-3-actions-recorded"></use></svg>
                {{ $roleLabel }}
            </div>
            <div class="snav-status">
                <span class="snav-status-dot"></span>
                {{ $status }} &middot; Last seen {{ $admin->last_login_at?->diffForHumans() ?? 'not recorded' }}
            </div>
        </div>

        <div class="snav-group">
            <div class="snav-group-label">Account</div>
            <button class="{{ $navClass('profile') }}" type="button" onclick="showSection('profile', this)">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-my-profile-path-d-m"></use></svg>
                Profile
            </button>
            <button class="{{ $navClass('account') }}" type="button" onclick="showSection('account', this)">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-5-jul-2026-14-32-circle"></use></svg>
                Account details
            </button>
            <button class="{{ $navClass('notifications') }}" type="button" onclick="showSection('notifications', this)">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-notifications-mark-all-read-s"></use></svg>
                Notifications
                <span class="snav-badge">3</span>
            </button>
        </div>

        <div class="snav-divider"></div>

        <div class="snav-group">
            <div class="snav-group-label">Security</div>
            <button class="{{ $navClass('security') }}" type="button" onclick="showSection('security', this)">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-account-penalty-and-freeze-3"></use></svg>
                Password &amp; 2FA
            </button>
            <button class="{{ $navClass('sessions') }}" type="button" onclick="showSection('sessions', this)">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-active-sessions-svg-viewbox-0-0"></use></svg>
                Active sessions
            </button>
            <button class="{{ $navClass('activity') }}" type="button" onclick="showSection('activity', this)">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-activity-log-svg-viewbox-0-0"></use></svg>
                Activity log
            </button>
        </div>

        <div class="snav-divider"></div>

        <form class="snav-group" method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="snav-item danger" type="submit">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-sign-out"></use></svg>
                Sign out
            </button>
        </form>
    </div>

    <div class="settings-content col-12 col-lg-9">
        <div id="section-profile" class="{{ $sectionClass('profile') }}">
            <div class="card mb-0">
                <div class="card-header">
                    <div>
                        <div class="card-title">Activity overview</div>
                        <div class="card-subtitle">This month vs last month</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
                        <div class="col"><div class="metric-tile"><div class="metric-tile-label">Actions taken</div><div class="metric-tile-value">1,284</div><div class="metric-tile-trend up">&uarr; 12%</div></div></div>
                        <div class="col"><div class="metric-tile"><div class="metric-tile-label">Content approved</div><div class="metric-tile-value">347</div><div class="metric-tile-trend up">&uarr; 8%</div></div></div>
                        <div class="col"><div class="metric-tile"><div class="metric-tile-label">Users verified</div><div class="metric-tile-value">89</div><div class="metric-tile-trend neutral">&rarr; Same</div></div></div>
                        <div class="col"><div class="metric-tile"><div class="metric-tile-label">Moderation actions</div><div class="metric-tile-value">56</div><div class="metric-tile-trend up">&uarr; 3%</div></div></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Public profile</div>
                        <div class="card-subtitle">This information is visible to platform members.</div>
                    </div>
                    <button class="card-action primary" id="editProfileBtn" type="button" onclick="toggleProfileEdit()">
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-edit-profile-div-c"></use></svg>
                        <span id="editProfileBtnText">Edit profile</span>
                    </button>
                </div>
                <div class="field-group admin-profile-fields">
                    <div class="field-row">
                        <div class="field-label">Display name</div>
                        <div class="field-value" id="pf-name-view">{{ $displayName }}</div>
                        <div class="field-value is-hidden" id="pf-name-edit"><input type="text" class="profile-input" value="{{ $displayName }}" id="pf-name-input"></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">Title / role</div>
                        <div class="field-value" id="pf-title-view">{{ $profileTitle }}</div>
                        <div class="field-value is-hidden" id="pf-title-edit"><input type="text" class="profile-input" value="{{ $profileTitle }}" id="pf-title-input"></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">Organisation</div>
                        <div class="field-value" id="pf-org-view">{{ $organisation }}</div>
                        <div class="field-value is-hidden" id="pf-org-edit"><input type="text" class="profile-input" value="{{ $organisation }}" id="pf-org-input"></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">Member since</div>
                        <div class="field-value muted">{{ $memberSince }}</div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">Access level</div>
                        <div class="field-value tags"><span class="tag">{{ $roleLabel }}</span><span class="tag green">Full access</span><span class="tag green">AI governance</span><span class="tag amber">Content moderation</span></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">Account status</div>
                        <div class="field-value"><span class="{{ $statusPill }}"><span class="status-dot"></span>{{ $status }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div id="section-account" class="{{ $sectionClass('account') }}">
            <div class="card">
                <div class="card-header">
                    <div><div class="card-title">Account details</div><div class="card-subtitle">Update your login email and personal information.</div></div>
                    <button class="card-action" type="button"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-edit-profile-div-c"></use></svg>Edit</button>
                </div>
                <div class="field-group admin-profile-fields">
                    <div class="field-row"><div class="field-label">Full name<small>Shown on your profile</small></div><div class="field-value"><input type="text" value="{{ $displayName }}"></div></div>
                    <div class="field-row"><div class="field-label">Email address<small>Used for login &amp; alerts</small></div><div class="field-value"><input type="email" value="{{ $admin->email }}"></div></div>
                    <div class="field-row"><div class="field-label">Username<small>Used for account lookup</small></div><div class="field-value"><input type="text" value="{{ $admin->username }}"></div></div>
                    <div class="field-row"><div class="field-label">Timezone<small>Used for audit timestamps</small></div><div class="field-value"><input type="text" value="{{ $timezone }}"></div></div>
                    <div class="field-row"><div class="field-label">Last login</div><div class="field-value muted">{{ $lastLogin }} - {{ $currentSessionSummary }}</div></div>
                </div>
                <div class="save-bar"><p>Changes are saved to your admin account immediately.</p><div class="save-bar-actions"><button class="btn-ghost" type="button">Cancel</button><button class="btn-save" type="button">Save changes</button></div></div>
            </div>
        </div>

        <div id="section-notifications" class="{{ $sectionClass('notifications') }}">
            <div class="card">
                <div class="card-header"><div><div class="card-title">Notification preferences</div><div class="card-subtitle">Choose which platform events trigger an alert for you.</div></div></div>
                <div class="notif-group-label">Users</div>
                <div class="notif-row-flat"><div class="notif-row-text"><div class="notif-label">New verification request</div><div class="notif-sub">Notify when a professional submits a verification request.</div></div><label class="switch"><input type="checkbox" checked><span class="slider"></span></label></div>
                <div class="notif-row-flat"><div class="notif-row-text"><div class="notif-label">Verification SLA breach</div><div class="notif-sub">Alert when a request exceeds the 48-hour review window.</div></div><label class="switch"><input type="checkbox" checked><span class="slider"></span></label></div>
                <div class="notif-row-flat"><div class="notif-row-text"><div class="notif-label">Account freeze / penalty applied</div><div class="notif-sub">Confirm when a freeze or suspension is activated.</div></div><label class="switch"><input type="checkbox"><span class="slider"></span></label></div>
                <div class="notif-group-label">Content &amp; Q&amp;A</div>
                <div class="notif-row-flat"><div class="notif-row-text"><div class="notif-label">Flagged Q&amp;A content</div><div class="notif-sub">Notify when a question or answer is reported.</div></div><label class="switch"><input type="checkbox" checked><span class="slider"></span></label></div>
                <div class="notif-row-flat"><div class="notif-row-text"><div class="notif-label">Content approval queue update</div><div class="notif-sub">Alert when new items arrive in the approval queue.</div></div><label class="switch"><input type="checkbox" checked><span class="slider"></span></label></div>
                <div class="notif-row-flat"><div class="notif-row-text"><div class="notif-label">Help Improve Charley submission</div><div class="notif-sub">Notify when users submit documents for review.</div></div><label class="switch"><input type="checkbox"><span class="slider"></span></label></div>
                <div class="notif-group-label">AI &amp; System</div>
                <div class="notif-row-flat"><div class="notif-row-text"><div class="notif-label">AI commercial misuse detected</div><div class="notif-sub">Alert when the AI monitor flags potential advertising abuse.</div></div><label class="switch"><input type="checkbox" checked><span class="slider"></span></label></div>
                <div class="notif-row-flat"><div class="notif-row-text"><div class="notif-label">AI dataset ingestion completed</div><div class="notif-sub">Confirm when a new dataset finishes syncing to Charley AI.</div></div><label class="switch"><input type="checkbox" checked><span class="slider"></span></label></div>
                <div class="notif-row-flat last"><div class="notif-row-text"><div class="notif-label">Weekly platform digest</div><div class="notif-sub">Receive a weekly summary of platform activity and stats.</div></div><label class="switch"><input type="checkbox" checked><span class="slider"></span></label></div>
            </div>
        </div>

        <div id="section-security" class="{{ $sectionClass('security') }}">
            <div class="card">
                <div class="card-header"><div><div class="card-title">Password &amp; two-factor authentication</div><div class="card-subtitle">Keep your admin account secure.</div></div></div>
                <div class="sec-row" id="pw-row"><div class="sec-row-left"><div class="sec-row-icon icon-tone-success"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-account-penalty-and-freeze-3"></use></svg></div><div><div class="sec-row-label">Password</div><div class="sec-row-sub" id="pw-sub">Last changed TODO-safe boundary - password age pending</div></div></div><button class="sec-row-action" id="changePasswordBtn" type="button" onclick="openPasswordModal()">Change password</button></div>
                <div class="sec-row"><div class="sec-row-left"><div class="sec-row-icon icon-tone-warning" id="tfa-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-active-sessions-svg-viewbox-0-0"></use></svg></div><div><div class="sec-row-label">Two-factor authentication</div><div class="sec-row-sub" id="tfa-sub">{{ $mfaLabel }} - strongly recommended for admin accounts</div></div></div><label class="switch" id="tfaToggle" title="Enable 2FA"><input type="checkbox" @checked($admin->mfa_enabled) onchange="handle2FA(this.closest('.switch'))"><span class="slider"></span></label></div>
                <div class="sec-row"><div class="sec-row-left"><div class="sec-row-icon icon-tone-success"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-penalty-history-3-actions-recorded"></use></svg></div><div><div class="sec-row-label">Current session</div><div class="sec-row-sub">{{ $currentSessionSummary }}</div></div></div><span class="{{ $mfaPill }}">{{ $mfaLabel }}</span></div>
                <div class="sec-row"><div class="sec-row-left"><div class="sec-row-icon icon-tone-info"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-audit-trail-all-admin-actions"></use></svg></div><div><div class="sec-row-label">Audit trail</div><div class="sec-row-sub">All admin actions are logged and retained for 12 months</div></div></div><span class="pill ok">Enabled</span></div>
            </div>
        </div>

        <div class="modal-overlay" id="pwModal" onclick="if(event.target===this) closePasswordModal()">
            <div class="pw-modal">
                <div class="modal-head"><div class="modal-head-top"><div class="modal-icon icon-tone-success"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-account-penalty-and-freeze-3"></use></svg></div><button class="modal-close" type="button" onclick="closePasswordModal()"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-change-password-choose-a-strong"></use></svg></button></div><div class="modal-title">Change password</div><div class="modal-sub">Choose a strong password you have not used before.</div></div>
                <div class="modal-body"><div class="row row-cols-1 row-cols-md-2 g-3"><div class="col"><div class="pw-field"><label>Current password</label><div class="pw-field-wrap"><input type="password" id="currentPw" placeholder="Enter current password" autocomplete="current-password"><button class="pw-eye" type="button" onclick="togglePwVis('currentPw', this)"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-input-type-checkbox-id"></use></svg></button></div></div></div><div class="col"><div class="pw-field"><label>New password</label><div class="pw-field-wrap"><input type="password" id="newPw" placeholder="At least 8 characters" autocomplete="new-password" oninput="checkStrength(this.value)"><button class="pw-eye" type="button" onclick="togglePwVis('newPw', this)"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-input-type-checkbox-id"></use></svg></button></div><div class="pw-strength"><div class="pw-strength-bar" id="pwBar"></div></div><div class="pw-hint" id="pwHint">Use 8+ characters, a mix of letters, numbers &amp; symbols.</div></div></div><div class="col"><div class="pw-field"><label>Confirm new password</label><div class="pw-field-wrap"><input type="password" id="confirmPw" placeholder="Repeat new password" autocomplete="new-password"><button class="pw-eye" type="button" onclick="togglePwVis('confirmPw', this)"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-input-type-checkbox-id"></use></svg></button></div></div></div></div></div>
                <div class="modal-foot modal-foot-end"><button class="btn-ghost" type="button" onclick="closePasswordModal()">Cancel</button><button class="btn-save" type="button" onclick="savePassword()">Save new password</button></div>
            </div>
        </div>

        <div class="modal-overlay" id="tfaModal" onclick="if(event.target===this) close2FAModal()">
            <div class="modal">
                <div class="modal-head"><div class="modal-head-top"><div class="modal-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-active-sessions-svg-viewbox-0-0"></use></svg></div><button class="modal-close" type="button" onclick="close2FAModal()"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-change-password-choose-a-strong"></use></svg></button></div><div class="modal-title">Set up two-factor authentication</div><div class="modal-sub">Scan the QR code with your authenticator app, then enter the 6-digit code to confirm.</div></div>
                <div class="modal-body"><div class="modal-step active" id="tfa-step1"><div class="modal-step-label">Step 1 - Scan QR code</div><div class="qr-box"><div class="qr-placeholder"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-open-google-authenticator-authy"></use></svg></div><div class="qr-code-text">Open <strong>Google Authenticator</strong>, <strong>Authy</strong>, or any TOTP app and scan this code.</div><div class="qr-manual">JBSWY3DPEHPK3PXP</div></div><div class="otp-hint">Can not scan? Enter the manual key above into your app.</div></div><div class="modal-step" id="tfa-step2"><div class="modal-step-label">Step 2 - Enter verification code</div><div class="otp-row">@for ($i = 0; $i < 6; $i++)<input class="otp-input" type="text" maxlength="1" oninput="otpMove(this, {{ $i }})" id="otp{{ $i }}">@endfor</div><div class="otp-hint">Enter the 6-digit code from your authenticator app.</div></div><div class="modal-step" id="tfa-step3"><div class="modal-success"><div class="modal-success-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-profile-verification-queue-5-svg"></use></svg></div><h3>2FA enabled successfully</h3><p>Your account is now protected with two-factor authentication.</p></div></div></div>
                <div class="modal-foot"><div class="modal-step-dots"><div class="step-dot active" id="dot1"></div><div class="step-dot" id="dot2"></div><div class="step-dot" id="dot3"></div></div><button class="btn-ghost is-hidden" id="modalBackBtn" type="button" onclick="tfa2FABack()">Back</button><button class="btn-save" id="modalNextBtn" type="button" onclick="tfa2FANext()">Next - Enter code</button></div>
            </div>
        </div>

        <div id="section-sessions" class="{{ $sectionClass('sessions') }}">
            <div class="card">
                <div class="card-header"><div><div class="card-title">Active sessions</div><div class="card-subtitle">Devices currently logged into your admin account.</div></div><button class="card-action" type="button">Revoke all others</button></div>
                @forelse ($sessions as $session)
                    <div class="sec-row">
                        <div class="sec-row-left"><div class="sec-row-icon {{ $loop->first ? 'icon-tone-success' : 'icon-tone-muted' }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-active-sessions-svg-viewbox-0-0"></use></svg></div><div><div class="sec-row-label">{{ $session->user_agent ?: 'Unknown browser' }}</div><div class="sec-row-sub">{{ $session->ip_address ?: 'Unknown IP' }} &middot; Last active {{ Illuminate\Support\Carbon::createFromTimestamp((int) $session->last_activity)->diffForHumans() }}</div></div></div>
                        @if ($loop->first)<span class="pill ok">Current</span>@else<button class="sec-row-action" type="button">Revoke</button>@endif
                    </div>
                @empty
                    <div class="sec-row"><div class="sec-row-left"><div class="sec-row-icon icon-tone-success"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-chrome-macos-ventura-this"></use></svg></div><div><div class="sec-row-label">Current admin session</div><div class="sec-row-sub">TODO-safe boundary - session device details pending</div></div></div><span class="pill ok">Current</span></div>
                    <div class="sec-row"><div class="sec-row-left"><div class="sec-row-icon icon-tone-muted"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-active-sessions-svg-viewbox-0-0"></use></svg></div><div><div class="sec-row-label">Mobile browser</div><div class="sec-row-sub">Static design placeholder - last active 3 hours ago</div></div></div><button class="sec-row-action" type="button">Revoke</button></div>
                    <div class="sec-row"><div class="sec-row-left"><div class="sec-row-icon icon-tone-muted"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-chrome-macos-ventura-this"></use></svg></div><div><div class="sec-row-label">Desktop browser</div><div class="sec-row-sub">Static design placeholder - last active 2 days ago</div></div></div><button class="sec-row-action" type="button">Revoke</button></div>
                @endforelse
            </div>
        </div>

        <div id="section-activity" class="{{ $sectionClass('activity') }}">
            <div class="card">
                <div class="card-header"><div><div class="card-title">Activity log</div><div class="card-subtitle">Recent actions performed under your admin account.</div></div><button class="card-action" type="button">View full audit log</button></div>
                <div class="activity-list">
                    <div class="activity-item"><div class="activity-icon icon-tone-success"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-profile-verification-queue-5-svg"></use></svg></div><div><div class="activity-text">Approved 3 professional verifications - Ahmed Al-Rashid, Priya Nair, Carlos Mejia</div><div class="activity-meta">10 minutes ago</div></div></div>
                    <div class="activity-item"><div class="activity-icon icon-tone-info"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-featured-answer-on-co-absorber"></use></svg></div><div><div class="activity-text">Featured answer on CO<sub>2</sub> absorber foaming - marked as admin-verified</div><div class="activity-meta">1 hour ago</div></div></div>
                    <div class="activity-item"><div class="activity-icon icon-tone-primary"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-partner-management-path-d-m9-12"></use></svg></div><div><div class="activity-text">Approved Synvex Catalysts Diamond Partner subscription</div><div class="activity-meta">2 hours ago</div></div></div>
                    <div class="activity-item"><div class="activity-icon icon-tone-warning"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-user-k-habib-flagged-for"></use></svg></div><div><div class="activity-text">Warning issued - user posted contact details inside Q&amp;A answer</div><div class="activity-meta">Yesterday, 4:15 PM</div></div></div>
                    <div class="activity-item"><div class="activity-icon icon-tone-info"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-library-and-pfd-content-path"></use></svg></div><div><div class="activity-text">Added 12 seed Q&amp;A questions to Technical Q&amp;A - Ammonia &amp; Methanol categories</div><div class="activity-meta">Yesterday, 11:00 AM</div></div></div>
                    <div class="activity-item"><div class="activity-icon icon-tone-danger"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-rejected-partner-announcement-techgas"></use></svg></div><div><div class="activity-text">Rejected partner announcement - TechGas Solutions: contained pricing information</div><div class="activity-meta">2 days ago</div></div></div>
                    <div class="activity-item"><div class="activity-icon icon-tone-success"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-approved-3-library-articles"></use></svg></div><div><div class="activity-text">Approved 3 library articles - Reformer Tube Inspection, Benfield Solution Mgmt, HTS Catalyst Loading</div><div class="activity-meta">2 days ago</div></div></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function showSection(section, item) {
        document.querySelectorAll('.admin-section').forEach(function (el) { el.classList.add('is-hidden'); });
        var target = document.getElementById('section-' + section);
        if (target) target.classList.remove('is-hidden');
        document.querySelectorAll('.settings-nav .snav-item').forEach(function (el) { el.classList.remove('active'); });
        if (item) item.classList.add('active');
    }

    function toggleProfileEdit() {
        ['name', 'title', 'org'].forEach(function (key) {
            var view = document.getElementById('pf-' + key + '-view');
            var edit = document.getElementById('pf-' + key + '-edit');
            if (view && edit) {
                view.classList.toggle('is-hidden');
                edit.classList.toggle('is-hidden');
            }
        });
        var label = document.getElementById('editProfileBtnText');
        if (label) label.textContent = label.textContent === 'Edit profile' ? 'Done' : 'Edit profile';
    }

    function openPasswordModal() { document.getElementById('pwModal')?.classList.add('active'); }
    function closePasswordModal() { document.getElementById('pwModal')?.classList.remove('active'); }
    function open2FAModal() { document.getElementById('tfaModal')?.classList.add('active'); }
    function close2FAModal() { document.getElementById('tfaModal')?.classList.remove('active'); }
    function handle2FA(toggle) { if (toggle && toggle.querySelector('input')?.checked) open2FAModal(); }
    function togglePwVis(id) { var input = document.getElementById(id); if (input) input.type = input.type === 'password' ? 'text' : 'password'; }
    function checkStrength(value) { var bar = document.getElementById('pwBar'); if (bar) bar.className = 'pw-strength-bar ' + (value.length > 12 ? 'strong' : value.length > 7 ? 'medium' : 'weak'); }
    function savePassword() { closePasswordModal(); }
    function otpMove(input) { if (input.value && input.nextElementSibling) input.nextElementSibling.focus(); }
    var tfaStep = 1;
    function tfa2FANext() {
        tfaStep = Math.min(3, tfaStep + 1);
        sync2FAStep();
    }
    function tfa2FABack() {
        tfaStep = Math.max(1, tfaStep - 1);
        sync2FAStep();
    }
    function sync2FAStep() {
        [1, 2, 3].forEach(function (step) {
            document.getElementById('tfa-step' + step)?.classList.toggle('active', step === tfaStep);
            document.getElementById('dot' + step)?.classList.toggle('active', step === tfaStep);
        });
        document.getElementById('modalBackBtn')?.classList.toggle('is-hidden', tfaStep === 1);
        var next = document.getElementById('modalNextBtn');
        if (next) next.textContent = tfaStep === 1 ? 'Next - Enter code' : (tfaStep === 2 ? 'Verify code' : 'Done');
        if (tfaStep === 3 && next) next.onclick = close2FAModal;
    }
</script>
@endpush
