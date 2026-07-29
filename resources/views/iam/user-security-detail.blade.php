@extends('layouts.rebuild-dashboard')

@section('title', ($userName ?? 'Account') . ' - Account Detail')

@section('content')
@php
    $viewErrors = $errors ?? new Illuminate\Support\ViewErrorBag;
    $securityUser = $user ?? null;
    $penalties = collect($accountPenalties ?? $penalties ?? []);
    $latestPenalty = $penalties->sortByDesc('created_at')->first();
    $latestVerification = $latestVerification ?? null;
    $userName = $securityUser ? (trim(($securityUser->first_name ?? '') . ' ' . ($securityUser->last_name ?? '')) ?: ($securityUser->username ?? 'Unknown account')) : 'Khalid Habib';
    $userEmail = $securityUser->email ?? 'k.habib@petrosyntech.com';
    $userRole = $securityUser->role ?? 'professional';
    $userStatus = $securityUser->status ?? 'frozen';
    $initials = collect(explode(' ', $userName))->filter()->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') ?: 'KH';
    $statusClass = in_array($userStatus, ['frozen', 'suspended'], true) ? $userStatus : 'active';
    $primaryReason = $latestPenalty->reason ?? 'Unauthorized advertising';
    $durationText = $latestPenalty?->ends_at ? $latestPenalty->ends_at->format('d M Y') : ($userStatus === 'frozen' ? 'Indefinite' : 'No active expiry');
    $registeredAt = $securityUser?->created_at?->format('d M Y') ?? '14 Mar 2024';
    $verifiedLabel = ($securityUser->is_verified ?? true) ? 'Verified' : 'Not verified';
    $verificationExpiry = $securityUser?->verification_expires_at?->format('d M Y') ?? '14 Mar 2026';
    $profileCompany = 'PetroSynTech';
    $profileTitle = $userRole === 'partner' ? 'Partner Account' : 'Senior Process Engineer';
    $profilePlantType = 'Ammonia - Methanol';
    $profileLocation = 'Doha, Qatar';
    $profileLinkedIn = 'linkedin.com/in/khalid-habib';
    $penaltyCount = $penalties->count() ?: 3;
    $warningCount = $penalties->where('action_type', 'warning')->count() ?: 1;
    $suspensionCount = $penalties->where('action_type', 'temporary_suspension')->count() ?: 1;
    $actionControlsFor = function (string $actionType): array {
        return match ($actionType) {
            'warning' => [['label' => 'Escalate', 'modal' => 'warning', 'class' => 'btn-warning'], ['label' => 'Freeze Account', 'modal' => 'freeze', 'class' => 'btn-danger']],
            'temporary_suspension' => [['label' => 'Lift Suspension', 'modal' => 'unfreeze', 'class' => 'btn-success'], ['label' => 'Freeze Account', 'modal' => 'freeze', 'class' => 'btn-danger']],
            'account_freeze' => [['label' => 'Lift Freeze', 'modal' => 'unfreeze', 'class' => 'btn-success'], ['label' => 'Review Freeze', 'modal' => 'freeze', 'class' => 'btn-ghost']],
            'unfreeze' => [['label' => 'Freeze Account', 'modal' => 'freeze', 'class' => 'btn-danger'], ['label' => 'Issue a Warning', 'modal' => 'warning', 'class' => 'btn-warning']],
            'ban' => [['label' => 'Review Ban', 'modal' => 'freeze', 'class' => 'btn-ghost'], ['label' => 'Restore Review', 'modal' => 'unfreeze', 'class' => 'btn-success']],
            'self_freeze' => [['label' => 'Lift Freeze', 'modal' => 'unfreeze', 'class' => 'btn-success'], ['label' => 'Review Self Freeze', 'modal' => 'freeze', 'class' => 'btn-ghost']],
            'self_unfreeze' => [['label' => 'Freeze Account', 'modal' => 'freeze', 'class' => 'btn-danger'], ['label' => 'Issue a Warning', 'modal' => 'warning', 'class' => 'btn-warning']],
            default => [['label' => 'Review Account', 'modal' => 'warning', 'class' => 'btn-ghost']],
        };
    };
    $detailControls = $actionControlsFor($latestPenalty->action_type ?? 'warning');
@endphp

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($viewErrors->any())
        <div class="alert alert-danger">{{ $viewErrors->first() }}</div>
    @endif

    <a class="back-link" href="{{ route('admin.dashboard.iam.account-penalty-freeze') }}">
        <svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-back-to-account-penalty-and') }}"></use></svg>
        Back to Account Penalty &amp; Freeze
    </a>

    <div class="hero">
        <div class="hero-avatar">{{ $initials }}</div>
        <div class="hero-info">
            <div class="hero-name">{{ $userName }}</div>
            <div class="hero-role">{{ str_replace('_', ' ', $userRole) }}</div>
            <div class="hero-email">{{ $userEmail }}</div>
            <div class="hero-tags">
                <span class="badge {{ $statusClass }}"><span class="badge-dot"></span>Account {{ ucfirst($userStatus) }}</span>
                <span class="penalty-chip chip-ads">{{ $primaryReason }}</span>
                <span class="member-since">Member since {{ $registeredAt }}</span>
            </div>
        </div>
        <div class="hero-actions">
            @if ($securityUser)
                <a class="btn btn-ghost" href="{{ route('admin.dashboard.iam.users.show', $securityUser) }}">
                    <svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-view-profile-svg-viewbox-0-0') }}"></use></svg>
                    View Profile
                </a>
            @else
                <button class="btn btn-ghost" type="button" onclick="window.open('#')">
                    <svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-view-profile-svg-viewbox-0-0') }}"></use></svg>
                    View Profile
                </button>
            @endif
            <a class="btn btn-ghost" href="mailto:{{ $userEmail }}">
                <svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-send-email-svg-viewbox-0-0') }}"></use></svg>
                Send Email
            </a>
        </div>
    </div>

    <div class="status-banner">
        <div class="status-banner-icon"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-account-penalty-and-freeze-3') }}"></use></svg></div>
        <div class="status-banner-body">
            <div class="status-banner-title">Account is currently {{ ucfirst($userStatus) }} - {{ $durationText }}</div>
            <div class="status-banner-sub">{{ $latestPenalty?->reason ?? 'Confirmed security fields are shown first. Unresolved penalty workflow actions remain display-only until the controller contract is defined.' }}</div>
        </div>
        <div class="status-banner-actions">
            <button class="btn btn-success btn-sm" onclick="openModal('unfreeze')" type="button">
                <svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-profile-verification-queue-5-svg') }}"></use></svg>
                Lift Freeze
            </button>
        </div>
    </div>

    <div class="detail-sidebar-grid account-detail-grid">
        <div class="detail-sidebar-main detail-left">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-my-profile-path-d-m') }}"></use></svg>Account Information</div>
                </div>
                <div class="card-body">
                    <div class="row row-cols-1 row-cols-md-2 g-3">
                        <div class="col"><div class="info-item"><div class="info-label">Full Name</div><div class="info-value">{{ $userName }}</div></div></div>
                        <div class="col"><div class="info-item"><div class="info-label">Account Type</div><div class="info-value">{{ str_replace('_', ' ', $userRole) }}</div></div></div>
                        <div class="col"><div class="info-item"><div class="info-label">Email Address</div><div class="info-value"><a href="mailto:{{ $userEmail }}">{{ $userEmail }}</a></div></div></div>
                        <div class="col"><div class="info-item"><div class="info-label">Company</div><div class="info-value">{{ $profileCompany }}</div></div></div>
                        <div class="col"><div class="info-item"><div class="info-label">Job Title</div><div class="info-value">{{ $profileTitle }}</div></div></div>
                        <div class="col"><div class="info-item"><div class="info-label">Industry / Plant Type</div><div class="info-value">{{ $profilePlantType }}</div></div></div>
                        <div class="col"><div class="info-item"><div class="info-label">Location</div><div class="info-value muted">{{ $profileLocation }}</div></div></div>
                        <div class="col"><div class="info-item"><div class="info-label">Registered</div><div class="info-value muted">{{ $registeredAt }}</div></div></div>
                        <div class="col"><div class="info-item"><div class="info-label">Verification Status</div><div class="info-value"><span class="badge active"><span class="badge-dot"></span>{{ $verifiedLabel }}</span></div></div></div>
                        <div class="col"><div class="info-item"><div class="info-label">Verification Expiry</div><div class="info-value muted">{{ $verificationExpiry }}</div></div></div>
                        <div class="col"><div class="info-item"><div class="info-label">Current Account Status</div><div class="info-value"><span class="badge {{ $statusClass }}"><span class="badge-dot"></span>{{ ucfirst($userStatus) }}</span></div></div></div>
                        <div class="col"><div class="info-item"><div class="info-label">LinkedIn Profile</div><div class="info-value"><a href="#" class="text-link">{{ $profileLinkedIn }}</a></div></div></div>
                        <div class="col"><div class="info-item"><div class="info-label">Login Attempts</div><div class="info-value">{{ $securityUser->login_attempts ?? 0 }}</div></div></div>
                        <div class="col"><div class="info-item"><div class="info-label">MFA Enabled</div><div class="info-value">{{ ($securityUser->mfa_enabled ?? false) ? 'Enabled' : 'Disabled' }}</div></div></div>
                        <div class="col"><div class="info-item"><div class="info-label">Locked Until</div><div class="info-value muted">{{ $securityUser?->locked_until?->format('d M Y H:i') ?? '-' }}</div></div></div>
                        <div class="col"><div class="info-item"><div class="info-label">Self Frozen At</div><div class="info-value muted">{{ $securityUser?->self_frozen_at?->format('d M Y H:i') ?? '-' }}</div></div></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-penalty-history-3-actions-recorded') }}"></use></svg>Penalty History</div>
                    <span class="card-header-meta">{{ $penaltyCount }} actions recorded</span>
                </div>
                <div class="timeline">
                    @forelse ($penalties->sortByDesc('created_at') as $penalty)
                        @php
                            $actionClass = match ($penalty->action_type ?? '') {
                                'account_freeze', 'self_freeze', 'ban' => 'freeze',
                                'temporary_suspension' => 'suspend',
                                default => 'warn',
                            };
                        @endphp
                        <div class="timeline-item">
                            <div class="tl-line"></div>
                            <div class="tl-icon {{ $actionClass }}"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-account-penalty-and-freeze-3') }}"></use></svg></div>
                            <div class="tl-body">
                                <div class="tl-action">{{ str_replace('_', ' ', ucfirst($penalty->action_type ?? 'Penalty')) }}</div>
                                <div class="tl-reason">{{ $penalty->reason ?? 'No reason recorded' }}</div>
                                <div class="tl-note">Evidence reference is stored in the confirmed account_penalties contract when available.</div>
                                <div class="tl-meta">
                                    <span><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-5-jul-2026-14-32-circle') }}"></use></svg>{{ $penalty->starts_at?->format('d M Y H:i') ?? $penalty->created_at?->format('d M Y H:i') ?? '-' }}</span>
                                    <span><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-my-profile-path-d-m') }}"></use></svg>{{ $penalty->admin_id ? 'Admin #' . $penalty->admin_id : 'Admin source pending' }}</span>
                                    <span class="text-state {{ $penalty->ends_at && $penalty->ends_at->isPast() ? 'muted' : 'red' }}">{{ $penalty->ends_at ? $penalty->ends_at->format('d M Y') : 'No expiry' }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="timeline-item">
                            <div class="tl-line"></div>
                            <div class="tl-icon freeze"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-account-penalty-and-freeze-3') }}"></use></svg></div>
                            <div class="tl-body">
                                <div class="tl-action">Account Freeze - Indefinite</div>
                                <div class="tl-reason">Third violation on the same violation type. Account frozen indefinitely pending account review.</div>
                                <div class="tl-note">Static demo boundary until account_penalties records are provided by the controller.</div>
                                <div class="tl-meta"><span>5 Jul 2026, 14:32</span><span>Admin: Sara Reyes</span><span class="text-state red">Active - No expiry</span></div>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="tl-line"></div>
                            <div class="tl-icon suspend"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-temporary-suspension-7') }}"></use></svg></div>
                            <div class="tl-body">
                                <div class="tl-action">Temporary Suspension - 7 days</div>
                                <div class="tl-reason">Repeated unauthorized advertising after prior warning.</div>
                                <div class="tl-note">Static demo boundary until account_penalties records are provided by the controller.</div>
                                <div class="tl-meta"><span>18 Jun 2026 - 25 Jun 2026</span><span>Admin: Sara Reyes</span><span class="text-state muted">Completed</span></div>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="tl-icon warn"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-formal-warning-first-violation') }}"></use></svg></div>
                            <div class="tl-body">
                                <div class="tl-action">Formal Warning</div>
                                <div class="tl-reason">First violation: commercial link and service mention in a public Q&amp;A answer.</div>
                                <div class="tl-note">Static demo boundary until account_penalties records are provided by the controller.</div>
                                <div class="tl-meta"><span>2 Jun 2026</span><span>Admin: Sara Reyes</span><span class="text-state muted">Acknowledged</span></div>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-moderation-and-reports-path-d-m14') }}"></use></svg>Flagged Content</div>
                    <span class="card-action">TODO: moderation contract pending</span>
                </div>
                <div class="flagged-list" id="flaggedList">
                    <div class="flagged-item" id="flag-1">
                        <div class="flagged-item-header"><span class="flagged-type">Q&amp;A Answer - Removed</span><span class="flagged-date">5 Jul 2026</span></div>
                        <div class="flagged-content">Static flagged-content preview retained from rebuild source until moderation records are confirmed for this screen.</div>
                        <div class="flagged-action"><span class="flagged-status danger">Removed - display only</span><button class="btn btn-success-outline btn-sm" onclick="markFlagSafe('flag-1',this)" type="button"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-profile-verification-queue-5-svg') }}"></use></svg>Mark as Safe</button></div>
                    </div>
                    <div class="flagged-item" id="flag-2">
                        <div class="flagged-item-header"><span class="flagged-type">Q&amp;A Answer - Removed</span><span class="flagged-date">16 Jun 2026</span></div>
                        <div class="flagged-content">Static flagged-content preview retained from rebuild source until moderation records are confirmed for this screen.</div>
                        <div class="flagged-action"><span class="flagged-status warning">Removed - display only</span><button class="btn btn-success-outline btn-sm" onclick="markFlagSafe('flag-2',this)" type="button"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-profile-verification-queue-5-svg') }}"></use></svg>Mark as Safe</button></div>
                    </div>
                    <div class="flagged-item" id="flag-3">
                        <div class="flagged-item-header"><span class="flagged-type">Q&amp;A Answer - Removed</span><span class="flagged-date">2 Jun 2026</span></div>
                        <div class="flagged-content">Static flagged-content preview retained from rebuild source until moderation records are confirmed for this screen.</div>
                        <div class="flagged-action"><span class="flagged-status amber">Removed - display only</span><button class="btn btn-success-outline btn-sm" onclick="markFlagSafe('flag-3',this)" type="button"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-profile-verification-queue-5-svg') }}"></use></svg>Mark as Safe</button></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-ai-usage-monitor-charley-calculator') }}"></use></svg>Platform Activity Summary</div>
                    <span class="card-header-meta">Static until owning module contracts are confirmed</span>
                </div>
                <div class="card-body card-body-flush">
                    <table class="act-table">
                        <thead><tr><th>Activity</th><th>Total</th><th>Last 30 days</th><th>Status</th></tr></thead>
                        <tbody>
                            <tr><td>Questions posted</td><td>12</td><td>2</td><td><span class="text-state green">Normal</span></td></tr>
                            <tr><td>Answers submitted</td><td>38</td><td>7</td><td><span class="text-state amber">3 removed</span></td></tr>
                            <tr><td>Library documents downloaded</td><td>24</td><td>4</td><td><span class="text-state green">Normal</span></td></tr>
                            <tr><td>AI assistant queries</td><td>61</td><td>8</td><td><span class="text-state green">Normal</span></td></tr>
                            <tr><td>Connections</td><td>17</td><td>3</td><td><span class="text-state green">Normal</span></td></tr>
                            <tr><td>Messages sent</td><td>9</td><td>0</td><td><span class="text-state muted">Suspended</span></td></tr>
                            <tr><td>Moderation reports received</td><td>4</td><td>2</td><td><span class="text-state red">High</span></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="detail-sidebar-aside detail-right">
            <div class="card">
                <div class="card-header"><div class="card-title"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-account-penalty-and-freeze-3') }}"></use></svg>Penalty Summary</div></div>
                <div class="card-body card-body-tight">
                    <div class="meta-list">
                        <div class="meta-row"><span class="meta-key">Current status</span><span class="meta-val red">{{ ucfirst($userStatus) }}</span></div>
                        <div class="meta-row"><span class="meta-key">Freeze type</span><span class="meta-val">{{ $securityUser?->self_frozen_at ? 'Self-requested' : 'Admin-initiated' }}</span></div>
                        <div class="meta-row"><span class="meta-key">Freeze duration</span><span class="meta-val red">{{ $durationText }}</span></div>
                        <div class="meta-row"><span class="meta-key">Frozen since</span><span class="meta-val">{{ $securityUser?->self_frozen_at?->format('d M Y') ?? $latestPenalty?->starts_at?->format('d M Y') ?? '-' }}</span></div>
                        <div class="meta-row"><span class="meta-key">Total penalties</span><span class="meta-val red">{{ $penaltyCount }}</span></div>
                        <div class="meta-row"><span class="meta-key">Warnings</span><span class="meta-val" id="warningCount">{{ $warningCount }}</span></div>
                        <div class="meta-row"><span class="meta-key">Suspensions</span><span class="meta-val">{{ $suspensionCount }}</span></div>
                        <div class="meta-row"><span class="meta-key">Primary violation</span><span class="meta-val">{{ $primaryReason }}</span></div>
                        <div class="meta-row"><span class="meta-key">Latest verification</span><span class="meta-val green">{{ $latestVerification->status ?? 'TODO: not provided' }}</span></div>
                        <div class="meta-row"><span class="meta-key">Initiated by</span><span class="meta-val">{{ $latestPenalty?->admin_id ? 'Admin #' . $latestPenalty->admin_id : 'Admin source pending' }}</span></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><div class="card-title"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-admin-actions-button-class-btn-btn-danger') }}"></use></svg>Admin Actions</div></div>
                <div class="card-body card-body-stack">
                    <div class="meta-row"><span class="meta-key">Action context</span><span class="meta-val">{{ str_replace('_', ' ', $latestPenalty->action_type ?? 'warning') }}</span></div>
                    @foreach ($detailControls as $control)
                        <button class="btn {{ $control['class'] }} btn-block-start" onclick="openModal({{ Illuminate\Support\Js::from($control['modal']) }}, {{ Illuminate\Support\Js::from($control['label']) }})" type="button"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-account-penalty-and-freeze-3') }}"></use></svg>{{ $control['label'] }}</button>
                    @endforeach
                    <div class="form-hint">Action buttons are placeholder-safe. New account_penalties writes require a confirmed controller or service contract.</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><div class="card-title"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-quick-links-a-href-class-btn') }}"></use></svg>Quick Links</div></div>
                <div class="card-body quick-link-stack">
                    <a href="#" class="btn btn-ghost btn-block-start"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-audit-log-pat') }}"></use></svg>View in Audit Log</a>
                    <a href="#" class="btn btn-ghost btn-block-start"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-moderation-and-reports-path-d-m14') }}"></use></svg>View Moderation Reports</a>
                    <a href="#" class="btn btn-ghost btn-block-start"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-support-inbox-5-users-and') }}"></use></svg>Open Support Thread</a>
                    @if ($securityUser)
                        <a href="{{ route('admin.dashboard.iam.users.show', $securityUser) }}" class="btn btn-ghost btn-block-start"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-my-profile-path-d-m') }}"></use></svg>Open Full User Profile</a>
                    @else
                        <a href="#" class="btn btn-ghost btn-block-start"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-my-profile-path-d-m') }}"></use></svg>Open Full User Profile</a>
                    @endif
                </div>
            </div>
        </div>
    </div>


<div class="modal-overlay" id="modal-warning">
    <div class="modal">
        <div class="modal-head-row"><div class="modal-head-icon warning"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-formal-warning-first-violation') }}"></use></svg></div><div class="modal-title">Issue a Warning</div></div>
        <p class="modal-intro">Write a message explaining the violation. This is display-only until warning notification contracts are confirmed.</p>
        <div class="form-group"><label class="form-label">Warning Message to User <span class="required-mark">*</span></label><textarea class="form-textarea" id="warningMsg" rows="5" placeholder="Dear {{ $userName }},"></textarea><div class="form-hint">Display-only preview.</div></div>
        <div class="modal-footer"><button class="btn btn-ghost" onclick="closeAllModals()" type="button">Cancel</button><button class="btn btn-warning" onclick="submitWarning()" type="button"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-send-email-svg-viewbox-0-0') }}"></use></svg>Send Warning Email</button></div>
    </div>
</div>

<div class="modal-overlay" id="modal-freeze">
    <div class="modal">
        <div class="modal-head-row"><div class="modal-head-icon danger"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-account-penalty-and-freeze-3') }}"></use></svg></div><div class="modal-title">Freeze Account</div></div>
        <p class="modal-intro">Freezing workflow is retained as source-aligned static UI until the write contract is confirmed.</p>
        <div class="form-group"><label class="form-label">Freeze Until (optional)</label><input type="date" class="form-input" id="freezeUntil"><div class="form-hint">Display-only preview.</div></div>
        <div class="form-group"><label class="form-label">Reason / Message to User <span class="required-mark">*</span></label><textarea class="form-textarea" id="freezeMsg" rows="4" placeholder="Dear {{ $userName }},"></textarea><div class="form-hint">Display-only preview.</div></div>
        <div class="modal-footer"><button class="btn btn-ghost" onclick="closeAllModals()" type="button">Cancel</button><button class="btn btn-danger" onclick="submitFreeze()" type="button"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-account-penalty-and-freeze-3') }}"></use></svg>Confirm Freeze &amp; Send Email</button></div>
    </div>
</div>

<div class="modal-overlay" id="modal-unfreeze">
    <div class="modal">
        <div class="modal-head-row"><div class="modal-head-icon success"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-profile-verification-queue-5-svg') }}"></use></svg></div><div class="modal-title">Un-freeze Account</div></div>
        <p class="modal-intro modal-intro-compact">You are about to restore full platform access for <strong>{{ $userName }}</strong>.</p>
        <div class="alert amber modal-alert"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-formal-warning-first-violation') }}"></use></svg><p>This is display-only until the confirmed controller behavior is invoked from the detail screen.</p></div>
        <div class="form-group"><label class="form-label">Admin Note <span class="required-mark">*</span></label><textarea class="form-textarea" id="unfreezeNote" rows="3" placeholder="Reason for lifting the freeze."></textarea><div class="form-hint">Display-only preview.</div></div>
        <div class="modal-footer"><button class="btn btn-ghost" onclick="closeAllModals()" type="button">Cancel</button><button class="btn btn-success" onclick="submitUnfreeze()" type="button">Confirm - Restore Access</button></div>
    </div>
</div>

<div class="toast" id="toast">
    <svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-profile-verification-queue-5-svg') }}"></use></svg>
    <span id="toastMsg">Action applied.</span>
</div>
@endsection
@push('scripts')
<script>
    window.openModal = function (name, actionLabel = null) {
        const modal = document.getElementById(`modal-${name}`);
        modal?.classList.add('open');
        if (actionLabel) {
            const title = modal?.querySelector('.modal-title');
            if (title) title.textContent = actionLabel;
        }
    };

    window.closeAllModals = function () {
        document.querySelectorAll('.modal-overlay').forEach((modal) => modal.classList.remove('open'));
    };

    window.showToast = function (message) {
        const toast = document.getElementById('toast');
        const toastMsg = document.getElementById('toastMsg');
        if (!toast || !toastMsg) return;
        toastMsg.textContent = message;
        toast.classList.add('show');
        window.setTimeout(() => toast.classList.remove('show'), 2600);
    };

    window.submitWarning = function () {
        closeAllModals();
        showToast('Warning preview recorded.');
        const count = document.getElementById('warningCount');
        if (count) count.textContent = String((parseInt(count.textContent, 10) || 0) + 1);
    };

    window.submitFreeze = function () {
        closeAllModals();
        showToast('Freeze preview applied.');
    };

    window.submitUnfreeze = function () {
        closeAllModals();
        showToast('Un-freeze preview applied.');
    };

    window.markFlagSafe = function (id, button) {
        const item = document.getElementById(id);
        item?.querySelector('.flagged-status')?.classList.remove('danger', 'warning', 'amber');
        const status = item?.querySelector('.flagged-status');
        if (status) {
            status.classList.add('green');
            status.textContent = 'Marked safe - display only';
        }
        if (button) button.disabled = true;
        showToast('Flag marked safe in preview.');
    };
</script>
@endpush