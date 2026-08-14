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
        : 'No database session recorded';
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
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-shield"></use></svg>
                {{ $roleLabel }}
            </div>
            <div class="snav-status">
                <span class="snav-status-dot"></span>
                {{ $status }} &middot; Last seen {{ $admin->last_login_at?->diffForHumans() ?? 'not recorded' }}
            </div>
        </div>

        <div class="snav-group">
            <div class="snav-group-label">Account</div>
            <div class="{{ $navClass('profile') }}" onclick="showSection('profile', this)">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-users-3"></use></svg>
                Profile
            </div>
            <div class="{{ $navClass('account') }}" onclick="showSection('account', this)">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-jul"></use></svg>
                Account details
            </div>
            <div class="{{ $navClass('notifications') }}" onclick="showSection('notifications', this)">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-notifications-mark-all-read-s"></use></svg>
                Notifications
                <span class="snav-badge">3</span>
            </div>
        </div>

        <div class="snav-divider"></div>

        <div class="snav-group">
            <div class="snav-group-label">Security</div>
            <div class="{{ $navClass('security') }}" onclick="showSection('security', this)">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-lock"></use></svg>
                Password &amp; 2FA
            </div>
            <div class="{{ $navClass('sessions') }}" onclick="showSection('sessions', this)">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-active-sessions"></use></svg>
                Active sessions
            </div>
            <div class="{{ $navClass('activity') }}" onclick="showSection('activity', this)">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-activity-log"></use></svg>
                Activity log
            </div>
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
                </div>
                <div class="field-group admin-profile-fields">
                    <div class="field-row">
                        <div class="field-label">Display name</div>
                        <div class="field-value">{{ $displayName }}</div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">Title / role</div>
                        <div class="field-value">{{ $profileTitle }}</div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">Organisation</div>
                        <div class="field-value">{{ $organisation }}</div>
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
                    <button class="card-action" type="button"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-edit-2"></use></svg>Edit</button>
                </div>
                <div class="field-group admin-profile-fields">
                    <div class="field-row"><div class="field-label">Full name<small>Shown on your profile</small></div><div class="field-value"><input type="text" value="{{ $displayName }}"></div></div>
                    <div class="field-row"><div class="field-label">Email address<small>Used for login &amp; alerts</small></div><div class="field-value"><input type="email" value="{{ $admin->email }}"></div></div>
                    <div class="field-row"><div class="field-label">Job title<small>Displayed on profile</small></div><div class="field-value"><input type="text" value="{{ $profileTitle }}"></div></div>
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
                <div class="sec-row" id="pw-row"><div class="sec-row-left"><div class="sec-row-icon icon-tone-success"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-lock"></use></svg></div><div><div class="sec-row-label">Password</div><div class="sec-row-sub" id="pw-sub">Last changed TODO-safe boundary - password age pending</div></div></div><button class="sec-row-action" id="changePasswordBtn" type="button" onclick="openPasswordModal()">Change password</button></div>
                <div class="sec-row"><div class="sec-row-left"><div class="sec-row-icon {{ $admin->mfa_enabled ? 'icon-tone-success' : 'icon-tone-warning' }}" id="tfa-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-active-sessions"></use></svg></div><div><div class="sec-row-label">Two-factor authentication</div><div class="sec-row-sub" id="tfa-sub">{{ $mfaLabel }} - strongly recommended for admin accounts</div></div></div><label class="switch" id="tfaToggle" title="Enable 2FA"><input id="tfaToggleInput" type="checkbox" @checked($admin->mfa_enabled) onchange="handle2FA(this.closest('.switch'))"><span class="slider"></span></label></div>
                <div class="sec-row"><div class="sec-row-left"><div class="sec-row-icon icon-tone-success"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-shield"></use></svg></div><div><div class="sec-row-label">Current session</div><div class="sec-row-sub">{{ $currentSessionSummary }}</div></div></div><span class="{{ $mfaPill }}" id="mfaStatusPill">{{ $mfaLabel }}</span></div>
                <div class="sec-row"><div class="sec-row-left"><div class="sec-row-icon icon-tone-info"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-audit-trail"></use></svg></div><div><div class="sec-row-label">Audit trail</div><div class="sec-row-sub">All admin actions are logged and retained for 12 months</div></div></div><span class="pill ok">Enabled</span></div>
            </div>
        </div>

        <div class="modal-overlay" id="pwModal" onclick="if(event.target===this) closePasswordModal()">
            <div class="pw-modal admin-profile-modal">
                <div class="modal-head"><div class="modal-head-top"><div class="modal-icon icon-tone-success"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-lock"></use></svg></div><button class="modal-close" type="button" onclick="closePasswordModal()"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-change-password-choose-strong"></use></svg></button></div><div class="modal-title">Change password</div><div class="modal-sub">Choose a strong password you haven't used before.</div></div>
                <div class="modal-body"><div class="pw-form-grid"><div class="pw-field"><label>Current password</label><div class="pw-field-wrap"><input type="password" id="currentPw" placeholder="Enter current password" autocomplete="current-password"><button class="pw-eye" type="button" onclick="togglePwVis('currentPw', this)"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-eye"></use></svg></button></div></div><div class="pw-field"><label>New password</label><div class="pw-field-wrap"><input type="password" id="newPw" placeholder="At least 8 characters" autocomplete="new-password" oninput="checkStrength(this.value)"><button class="pw-eye" type="button" onclick="togglePwVis('newPw', this)"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-eye"></use></svg></button></div><div class="pw-strength"><div class="pw-strength-bar" id="pwBar"></div></div><div class="pw-hint" id="pwHint">Use 8+ characters, a mix of letters, numbers &amp; symbols.</div></div><div class="pw-field"><label>Confirm new password</label><div class="pw-field-wrap"><input type="password" id="confirmPw" placeholder="Repeat new password" autocomplete="new-password"><button class="pw-eye" type="button" onclick="togglePwVis('confirmPw', this)"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-eye"></use></svg></button></div></div></div></div>
                <div class="modal-foot modal-foot-end"><button class="btn-ghost" type="button" onclick="closePasswordModal()">Cancel</button><button class="btn-save" type="button" onclick="savePassword()">Save new password</button></div>
            </div>
        </div>

        <div class="modal-overlay" id="tfaModal" onclick="if(event.target===this) close2FAModal()">
            <div class="modal admin-profile-modal">
                <div class="modal-head"><div class="modal-head-top"><div class="modal-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-active-sessions"></use></svg></div><button class="modal-close" type="button" onclick="close2FAModal()"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-change-password-choose-strong"></use></svg></button></div><div class="modal-title">Set up two-factor authentication</div><div class="modal-sub">Scan the QR code with your authenticator app, then enter the 6-digit code to confirm.</div></div>
                <div class="modal-body"><div class="modal-step active" id="tfa-step1"><div class="modal-step-label">Step 1 &mdash; Scan QR code</div><div class="qr-box"><div class="qr-placeholder" id="tfaQrPlaceholder"><img id="tfaQrImage" alt="Authenticator QR code" style="width:132px;height:132px;display:none;"></div><div class="qr-code-text">Open <strong>Google Authenticator</strong>, <strong>Authy</strong>, or any TOTP app and scan this code.</div><div class="qr-manual" id="tfaManualKey">Preparing setup...</div><div class="otp-hint" id="tfaProvisioningUri" style="word-break:break-all;"></div><div class="otp-hint" id="tfaSetupError" style="color:#B91C1C;"></div></div><div class="otp-hint">Can't scan? Enter the manual key above into your app.</div></div><div class="modal-step" id="tfa-step2"><div class="modal-step-label">Step 2 &mdash; Enter verification code</div><div class="otp-row">@for ($i = 0; $i < 6; $i++)<input class="otp-input" type="text" maxlength="1" oninput="otpMove(this, {{ $i }})" id="otp{{ $i }}">@endfor</div><div class="otp-hint" id="tfaModalHint">Enter the 6-digit code from your authenticator app.</div></div><div class="modal-step" id="tfa-step3"><div class="modal-success"><div class="modal-success-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-verification-queue"></use></svg></div><h3>2FA enabled successfully</h3><p>Your account is now protected with two-factor authentication.</p><div id="tfaRecoveryCodes" class="otp-hint" style="margin-top:12px;word-break:break-word;"></div></div></div></div>
                <div class="modal-foot"><div class="modal-step-dots"><div class="step-dot active" id="dot1"></div><div class="step-dot" id="dot2"></div><div class="step-dot" id="dot3"></div></div><button class="btn-ghost is-hidden" id="modalBackBtn" type="button" onclick="tfa2FABack()">Back</button><button class="btn-save" id="modalNextBtn" type="button" onclick="tfa2FANext()">Next &mdash; Enter code</button></div>
            </div>
        </div>

        <div id="section-sessions" class="{{ $sectionClass('sessions') }}">
            <div class="card">
                <div class="card-header"><div><div class="card-title">Active sessions</div><div class="card-subtitle">Devices currently logged into your admin account.</div></div>
                    @if ($sessions->contains(fn ($session): bool => ! (bool) ($session->is_current ?? false)))
                        <form method="POST" action="{{ route('admin.dashboard.iam.users.admin-profile.sessions.revoke-others') }}">
                            @csrf
                            @method('DELETE')
                            <button class="card-action" type="submit">Revoke all others</button>
                        </form>
                    @endif
                </div>
                @forelse ($sessions as $session)
                    @php
                        $isCurrentSession = (bool) ($session->is_current ?? false);
                        $lastActive = isset($session->last_activity) ? Illuminate\Support\Carbon::createFromTimestamp((int) $session->last_activity)->diffForHumans() : 'activity time unavailable';
                    @endphp
                    <div class="sec-row">
                        <div class="sec-row-left"><div class="sec-row-icon {{ $isCurrentSession ? 'icon-tone-success' : 'icon-tone-muted' }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-active-sessions"></use></svg></div><div><div class="sec-row-label">{{ $session->user_agent ?: 'Unknown browser' }}</div><div class="sec-row-sub">{{ $session->ip_address ?: 'Unknown IP' }} &middot; Last active {{ $lastActive }}</div></div></div>
                        @if ($isCurrentSession)<span class="pill ok">Current</span>@else<form method="POST" action="{{ route('admin.dashboard.iam.users.admin-profile.sessions.revoke', $session->id) }}">@csrf @method('DELETE')<button class="sec-row-action" type="submit">Revoke</button></form>@endif
                    </div>
                @empty
                    <div class="sec-row"><div class="sec-row-left"><div class="sec-row-icon icon-tone-muted"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-active-sessions"></use></svg></div><div><div class="sec-row-label">No database sessions recorded</div><div class="sec-row-sub">This account has no persisted session rows to display.</div></div></div><span class="pill warn">Unavailable</span></div>
                @endforelse
            </div>
        </div>

        <div id="section-activity" class="{{ $sectionClass('activity') }}">
            <div class="card">
                <div class="card-header"><div><div class="card-title">Activity log</div><div class="card-subtitle">Recent actions performed under your admin account.</div></div><button class="card-action" type="button">View full audit log</button></div>
                <div class="activity-list">
                    <div class="activity-item"><div class="activity-icon icon-tone-success"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-verification-queue"></use></svg></div><div><div class="activity-text">Approved 3 professional verifications - Ahmed Al-Rashid, Priya Nair, Carlos Mejia</div><div class="activity-meta">10 minutes ago</div></div></div>
                    <div class="activity-item"><div class="activity-icon icon-tone-info"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-featured-answer-on-co-absorber"></use></svg></div><div><div class="activity-text">Featured answer on CO<sub>2</sub> absorber foaming - marked as admin-verified</div><div class="activity-meta">1 hour ago</div></div></div>
                    <div class="activity-item"><div class="activity-icon icon-tone-primary"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-partners"></use></svg></div><div><div class="activity-text">Approved Synvex Catalysts Diamond Partner subscription</div><div class="activity-meta">2 hours ago</div></div></div>
                    <div class="activity-item"><div class="activity-icon icon-tone-warning"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-user-k-habib-flagged"></use></svg></div><div><div class="activity-text">Warning issued - user posted contact details inside Q&amp;A answer</div><div class="activity-meta">Yesterday, 4:15 PM</div></div></div>
                    <div class="activity-item"><div class="activity-icon icon-tone-info"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-library"></use></svg></div><div><div class="activity-text">Added 12 seed Q&amp;A questions to Technical Q&amp;A - Ammonia &amp; Methanol categories</div><div class="activity-meta">Yesterday, 11:00 AM</div></div></div>
                    <div class="activity-item"><div class="activity-icon icon-tone-danger"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-rejected-partner-announcement-te"></use></svg></div><div><div class="activity-text">Rejected partner announcement - TechGas Solutions: contained pricing information</div><div class="activity-meta">2 days ago</div></div></div>
                    <div class="activity-item"><div class="activity-icon icon-tone-success"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-approved-library-articles"></use></svg></div><div><div class="activity-text">Approved 3 library articles - Reformer Tube Inspection, Benfield Solution Mgmt, HTS Catalyst Loading</div><div class="activity-meta">2 days ago</div></div></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    var mfaEnabled = @json((bool) $admin->mfa_enabled);
    var mfaSetupSecret = null;

    function showSection(section, item) {
        document.querySelectorAll('.admin-section').forEach(function (el) { el.classList.add('is-hidden'); });
        var target = document.getElementById('section-' + section);
        if (target) target.classList.remove('is-hidden');
        document.querySelectorAll('.settings-nav .snav-item').forEach(function (el) { el.classList.remove('active'); });
        if (item) item.classList.add('active');
    }

    function csrfToken() { return document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'; }
    function apiFetch(url, options) {
        options = options || {};
        options.headers = Object.assign({ 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() }, options.headers || {});
        return fetch(url, options).then(function (response) {
            return response.json().then(function (json) {
                if (! response.ok) throw json;
                return json;
            });
        });
    }

    function openPasswordModal() { var modal = document.getElementById('pwModal'); if (! modal) return; modal.classList.add('show'); document.body.classList.add('modal-open'); window.setTimeout(function () { document.getElementById('currentPw')?.focus(); }, 100); }
    function closePasswordModal() { var modal = document.getElementById('pwModal'); if (modal) modal.classList.remove('show'); document.body.classList.remove('modal-open'); ['currentPw', 'newPw', 'confirmPw'].forEach(function (id) { var input = document.getElementById(id); if (input) input.value = ''; }); var bar = document.getElementById('pwBar'); if (bar) bar.className = 'pw-strength-bar'; }
    function open2FAModal() { var modal = document.getElementById('tfaModal'); if (modal) modal.classList.add('show'); document.body.classList.add('modal-open'); }
    function close2FAModal() { var modal = document.getElementById('tfaModal'); if (modal) modal.classList.remove('show'); document.body.classList.remove('modal-open'); syncMfaUi(mfaEnabled); }

    function syncMfaUi(enabled) {
        mfaEnabled = !! enabled;
        var toggle = document.getElementById('tfaToggleInput');
        var sub = document.getElementById('tfa-sub');
        var pill = document.getElementById('mfaStatusPill');
        var icon = document.getElementById('tfa-icon');
        if (toggle) toggle.checked = mfaEnabled;
        if (sub) sub.textContent = (mfaEnabled ? 'Enabled' : 'Disabled') + ' - strongly recommended for admin accounts';
        if (pill) { pill.textContent = mfaEnabled ? 'Enabled' : 'Disabled'; pill.className = mfaEnabled ? 'pill ok' : 'pill warn'; }
        if (icon) { icon.classList.toggle('icon-tone-success', mfaEnabled); icon.classList.toggle('icon-tone-warning', ! mfaEnabled); }
    }

    function readableError(error, fallback) {
        if (error?.message) return error.message;
        var errors = error?.errors ? Object.values(error.errors).flat() : [];
        return errors[0] || fallback;
    }

    function handle2FA(toggle) {
        var input = toggle?.querySelector('input');
        if (! input) return;
        if (input.checked && ! mfaEnabled) {
            startMfaSetup();
            return;
        }
        if (! input.checked && mfaEnabled) {
            var code = window.prompt('Enter a 6-digit authenticator code or recovery code to disable 2FA.');
            if (! code) { syncMfaUi(true); return; }
            disableMfa(code);
        }
    }

    function setupQrUri(data) {
        var secret = data.secret || '';
        return 'otpauth://totp/Charley?secret=' + encodeURIComponent(secret) + '&issuer=Charley';
    }
    function startMfaSetup() {
        tfaStep = 1;
        sync2FAStep();
        open2FAModal();
        document.getElementById('tfaManualKey').textContent = 'Preparing setup...';
        document.getElementById('tfaProvisioningUri').textContent = '';
        document.getElementById('tfaRecoveryCodes').textContent = '';
        document.getElementById('tfaSetupError').textContent = '';
        apiFetch('/api/v1/account/security/mfa/setup', { method: 'POST', body: JSON.stringify({}) })
            .then(function (json) {
                mfaSetupSecret = json.data.secret;
                var qrUri = setupQrUri(json.data);
                var qrImage = document.getElementById('tfaQrImage');
                document.getElementById('tfaManualKey').textContent = json.data.secret;
                document.getElementById('tfaProvisioningUri').textContent = json.data.provisioning_uri || qrUri;
                if (qrImage && json.data.qr_data_uri) { qrImage.src = json.data.qr_data_uri; qrImage.style.display = 'block'; }
            })
            .catch(function (error) { document.getElementById('tfaSetupError').textContent = readableError(error, 'Unable to start 2FA setup.'); syncMfaUi(false); });
    }

    function confirmMfa() {
        var code = Array.from(document.querySelectorAll('.otp-input')).map(function (input) { return input.value; }).join('');
        if (! mfaSetupSecret || code.length !== 6) { document.getElementById('tfaModalHint').textContent = 'Enter the 6-digit code before continuing.'; return; }
        apiFetch('/api/v1/account/security/mfa/confirm', { method: 'POST', body: JSON.stringify({ secret: mfaSetupSecret, code: code }) })
            .then(function (json) {
                syncMfaUi(true);
                var recovery = json.data.recovery_codes || [];
                document.getElementById('tfaRecoveryCodes').innerHTML = recovery.length ? '<strong>Recovery codes:</strong><br>' + recovery.join('<br>') : '';
                tfaStep = 3;
                sync2FAStep();
            })
            .catch(function (error) { document.getElementById('tfaModalHint').textContent = readableError(error, 'Invalid verification code.'); syncMfaUi(false); });
    }

    function disableMfa(value) {
        var payload = value.indexOf('-') >= 0 || value.length > 6 ? { recovery_code: value } : { code: value };
        apiFetch('/api/v1/account/security/mfa/disable', { method: 'POST', body: JSON.stringify(payload) })
            .then(function () { syncMfaUi(false); })
            .catch(function (error) { window.alert(readableError(error, 'Unable to disable 2FA.')); syncMfaUi(true); });
    }

    function togglePwVis(id) { var input = document.getElementById(id); if (input) input.type = input.type === 'password' ? 'text' : 'password'; }
    function checkStrength(value) { var bar = document.getElementById('pwBar'); if (bar) bar.className = 'pw-strength-bar ' + (value.length > 12 ? 'strong' : value.length > 7 ? 'medium' : 'weak'); }
    function savePassword() { closePasswordModal(); }
    function otpMove(input) { if (input.value && input.nextElementSibling) input.nextElementSibling.focus(); }
    var tfaStep = 1;
    function tfa2FANext() {
        if (tfaStep === 2) { confirmMfa(); return; }
        if (tfaStep === 3) { close2FAModal(); return; }
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
        document.getElementById('modalBackBtn')?.classList.toggle('is-hidden', tfaStep === 1 || tfaStep === 3);
        var next = document.getElementById('modalNextBtn');
        if (next) next.innerHTML = tfaStep === 1 ? 'Next &mdash; Enter code' : (tfaStep === 2 ? 'Verify code' : 'Done');
    }
    syncMfaUi(mfaEnabled);
</script>
@endpush
