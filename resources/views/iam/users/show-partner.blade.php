@extends('layouts.rebuild-dashboard')

@section('title', 'Partner Detail')

@php
    $profile = $detail['profile'];
    $initials = collect(explode(' ', trim($profile->company_name ?? $detail['name'])))->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('') ?: 'P';
    $contactInitials = collect(explode(' ', trim($detail['name'])))->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('') ?: 'PC';
    $company = $profile->company_name ?? $detail['name'];
    $contactEmail = $profile->contact_email ?? $detail['email'];
    $activeSubscription = $profile->active_subscription ?? null;
    $tier = $profile->partner_tier ?? 'No active tier';
    $tierShort = str($tier)->replace(' Partner', '')->title();
    $tierAmount = $activeSubscription?->tier?->monthly_price;
    $tierCycle = $activeSubscription?->tier?->billing_cycle ?? 'yearly';
    $tierAmountLabel = $tierAmount ? '$'.number_format((float) $tierAmount, 0) : 'TODO-safe billing';
    $tierPeriodLabel = $tierCycle === 'monthly' ? 'Monthly Subscription' : 'Annual Subscription';
    $tierCode = str($tierShort)->lower()->replace(' ', '-');
    $tierClass = in_array((string) $tierCode, ['diamond', 'gold', 'platinum'], true) ? 'tier-'.$tierCode : 'tier-gold';
    $subscriptionExpires = $profile->subscription_expires_at ?? null;
    $profileVerifiedAt = $profile->verified_at ?? null;
    $renewal = $subscriptionExpires ? \Illuminate\Support\Carbon::parse($subscriptionExpires)->format('M j, Y') : 'Not scheduled';
    $website = $profile->website ?? 'No website';
    $country = $profile->country ?? 'Location not set';
    $overview = $profile->overview ?? 'Partner profile overview is not available yet. Product, announcement, and member records stay as TODO-safe demo content until their data contracts are supplied.';
    $keywords = collect(is_array($profile->keywords ?? null) ? $profile->keywords : explode(',', (string) ($profile->keywords ?? '')))->map(fn ($item) => trim((string) $item))->filter()->take(8);
    $approval = $profile->approval_status ?? $detail['verification'];
    $approvalClass = in_array((string) $approval, ['approved', 'Verified'], true) ? 'status-active' : ((string) $approval === 'suspended' ? 'status-suspended' : 'status-pending');
    $subscriptionStatus = $profile->subscription_status ?? 'inactive';
    $joined = $detail['joined'] ?? ($user->created_at?->format('M j, Y') ?? 'Unknown');
    $logoUrl = $partnerLogoUrl ?? null;
@endphp

@section('content')
    <a href="{{ route('admin.dashboard.iam.users.partners') }}" class="back-link">
        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-back-to-account-penalty-and"></use></svg>
        Back to Partner Management
    </a>

    <!-- Profile header -->
    <div class="profile-head" aria-label="Legal Identity Partner Display TODO-safe display boundary">
        <div class="profile-head-row">
            <div class="profile-head-main">
                <div class="profile-logo">@if ($logoUrl)<img class="profile-logo-img" src="{{ $logoUrl }}" alt="{{ $company }} logo" style="width: 100%;height: 100%;object-fit: cover;">@else{{ $initials }}@endif</div>
                <div>
                    <div class="profile-title-row">
                        <h1>{{ $company }}</h1>
                        <span class="tier-badge {{ $tierClass }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-diamond-verified-div-class-profile-meta-i"></use></svg>{{ $tierShort }}</span>
                        <span class="status-pill {{ $approvalClass }}"><span class="dot"></span>{{ str_replace('_', ' ', ucfirst((string) $approval)) }}</span>
                    </div>
                    <div class="profile-meta-row">
                        <div class="profile-meta-item">
                            <svg class="icon"><use href="/assets/icons/sprite.svg#icon-service-management-circle-cx-9-cy-21"></use></svg>
                            {{ $detail['specialty'] }}
                        </div>
                        <div class="profile-meta-item">
                            <svg class="icon"><use href="/assets/icons/sprite.svg#icon-rotterdam-netherlands-svg-viewbox-0-0"></use></svg>
                            {{ $country }}
                        </div>
                        <div class="profile-meta-item">
                            <svg class="icon"><use href="/assets/icons/sprite.svg#icon-5-jul-2026-14-32-circle"></use></svg>
                            {{ $tierShort }} Partner since {{ $detail['joined'] }}
                        </div>
                        <div class="profile-meta-item">
                            <svg class="icon"><use href="/assets/icons/sprite.svg#icon-synvex-catalysts-com-manufactures-and-supplies-s"></use></svg>
                            <a href="#" class="accent-link">{{ $website }}</a>
                        </div>
                    </div>
                    <p class="profile-desc">{{ $overview }}</p>
                </div>
            </div>
            <div class="profile-head-actions">
                <button class="btn-secondary" type="button" onclick="showDetailToast('TODO: partner message contract not supplied')">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-message-partner-svg-viewbox-0-0"></use></svg>
                    Message Partner
                </button>
                <a class="btn-secondary" href="{{ route('admin.dashboard.iam.users.edit-partner', $user) }}">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-edit-profile-r"></use></svg>
                    Edit Profile
                </a>
                <button class="btn-danger-outline" type="button" onclick="openDetailModal('freeze')">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-account-penalty-and-freeze-3"></use></svg>
                    Freeze Account
                </button>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="detail-tabs">
        <div class="dtab active" onclick="setDetailTab(this,'overview')">Overview</div>
        <div class="dtab" onclick="setDetailTab(this,'billing')">Subscription &amp; Billing</div>
        <div class="dtab" onclick="setDetailTab(this,'connections')">Connections</div>
        <div class="dtab" onclick="setDetailTab(this,'audit')">Audit Log</div>
    </div>

    <!-- ============ OVERVIEW TAB ============ -->
    <div class="tab-panel active" id="panel-overview">
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-3"><div class="mini-stat"><div class="v">47</div><div class="l">Announcements</div></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="mini-stat"><div class="v">18</div><div class="l">Approved Uploads</div></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="mini-stat"><div class="v">63</div><div class="l">Q&amp;A Answers</div></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="mini-stat"><div class="v">211</div><div class="l">Messages Sent</div></div></div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-9">
                <div class="verify-panel">
                    <div class="verify-panel-head">
                        <div class="verify-panel-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-profile-verification-queue-5-svg"></use></svg></div>
                        <div><h3>Verification &amp; Documents</h3><p class="sub">Company identity and verification status</p></div>
                    </div>
                    <div class="verify-panel-body">
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="verify-status-box ok"><div class="status-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-profile-verification-queue-5-svg"></use></svg></div><div><div class="k">Account Status</div><div class="v">{{ $detail['verification'] }}</div></div></div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="verify-status-box ok"><div class="status-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-profile-verification-queue-5-svg"></use></svg></div><div><div class="k">Business Email</div><div class="v">{{ $contactEmail }}</div></div></div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="verify-status-box due"><div class="status-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-schedule-publish-automatically-at-a"></use></svg></div><div><div class="k">Renewal Due</div><div class="v">{{ $renewal }}</div></div></div>
                            </div>
                        </div>
                        <div class="doc-list">
                            <div class="doc-row"><div class="doc-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-company-registration-certificate-verified-10"></use></svg></div><div class="doc-text"><div class="doc-name">Company Registration Certificate</div><div class="doc-meta">TODO-safe static document until media IDs are exposed.</div></div><button class="doc-view-btn" type="button">View</button></div>
                            <div class="doc-row"><div class="doc-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-business-email-domain-verification-confirmed"></use></svg></div><div class="doc-text"><div class="doc-name">Business Email Domain Verification</div><div class="doc-meta">Confirmed - {{ $contactEmail }}</div></div><button class="doc-view-btn" type="button">View</button></div>
                            <div class="doc-row"><div class="doc-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-linkedin-company-profile-matched-3"></use></svg></div><div class="doc-text"><div class="doc-name">LinkedIn Company Profile</div><div class="doc-meta">TODO-safe static placeholder until social account metadata is exposed.</div></div><button class="doc-view-btn" type="button">View</button></div>
                        </div>
                        <div class="info-divider spaced"></div>
                        <div class="info-row"><span class="k">Verified by</span><span class="v">Admin review</span></div>
                        <div class="info-row verified-row"><span class="k">Verified on</span><span class="v">{{ $profileVerifiedAt ? \Illuminate\Support\Carbon::parse($profileVerifiedAt)->format('M j, Y') : $detail['verified_at'] }}</span></div>
                    </div>
                </div>

                <div class="panel-card"><h3>Products &amp; Services</h3><p class="sub">Shown on the partner's public profile page</p><div class="chip-group"><span class="chip">Steam reforming services</span><span class="chip">ATR support</span><span class="chip">Methanol synthesis support</span><span class="chip">Catalyst loading services</span><span class="chip">Performance monitoring</span><span class="chip">Spent catalyst handling</span></div></div>
                <div class="panel-card"><h3>Searchable Keywords</h3><p class="sub">Partner-defined keywords used by the Partner Directory search</p><div class="chip-group">@forelse ($keywords as $keyword)<span class="chip keyword">{{ $keyword }}</span>@empty<span class="chip keyword">catalyst</span><span class="chip keyword">partner profile</span><span class="chip keyword">technical services</span>@endforelse</div></div>
                <div class="panel-card"><div class="panel-card-head"><div><h3 class="panel-title-tight">Recent Activity</h3></div><span class="panel-link">View all</span></div>@forelse ($detail['activity']['feed'] as $activity)<div class="activity-item"><div class="activity-icon activity-blue"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-activity-log-svg-viewbox-0-0"></use></svg></div><div><div class="activity-text">{{ str_replace('_', ' ', ucfirst($activity->activity_type)) }}</div><div class="activity-time">{{ \Illuminate\Support\Carbon::parse($activity->created_at)->diffForHumans() }}</div></div></div>@empty<div class="activity-item"><div class="activity-icon activity-green"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-profile-verification-queue-5-svg"></use></svg></div><div><div class="activity-text">TODO-safe recent activity placeholder</div><div class="activity-time">No activity_feed records yet</div></div></div><div class="activity-item"><div class="activity-icon activity-amber"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-announcement-management-charley-library-svg"></use></svg></div><div><div class="activity-text">Partner announcements and content remain static until their contracts are supplied.</div><div class="activity-time">TODO-safe boundary</div></div></div>@endforelse</div>
            </div>

            <div class="col-12 col-xl-3">
                <div class="pb-card"><div class="pb-tier-label"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-diamond-verified-div-class-profile-meta-i"></use></svg>{{ $tier }}</div><div class="pb-amount">{{ $subscriptionStatus }}</div><div class="pb-amount-sub">Partner Subscription</div><div class="pb-row"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-5-jul-2026-14-32-circle"></use></svg>Renews {{ $renewal }}</div><div class="pb-row"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-profile-verification-queue-5-svg"></use></svg>{{ str_replace('_', ' ', ucfirst((string) $approval)) }}</div></div>
                <div class="panel-card uniform-13"><div class="panel-card-head"><div class="panel-title-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-partner-management-path-d-m9-12"></use></svg><h3 class="panel-title-tight">Company Info</h3></div><button class="icon-edit-btn" type="button"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-edit-profile-r"></use></svg></button></div><div class="info-list"><div class="info-row"><span class="k">Legal Name</span><span class="v">{{ $company }}</span></div><div class="info-row"><span class="k">Country</span><span class="v">{{ $profile->country ?? 'No profile yet' }}</span></div><div class="info-row"><span class="k">Founded</span><span class="v">{{ $profile->founded_year ?? 'No profile yet' }}</span></div><div class="info-row"><span class="k">Website</span><span class="v">{{ $website }}</span></div><div class="info-row"><span class="k">Primary Email</span><span class="v">{{ $contactEmail }}</span></div><div class="info-row"><span class="k">Partner ID</span><span class="v mono-muted">#PTN-{{ str_pad($user->id, 5, '0', STR_PAD_LEFT) }}</span></div></div></div>
                <div class="panel-card uniform-13"><h3 class="panel-title-space">Primary Contact</h3><div class="contact-card"><div class="contact-avatar">{{ $contactInitials }}</div><div><div class="contact-name">{{ $detail['name'] }}</div><div class="contact-role">Primary partner contact</div></div></div><div class="info-divider compact"></div><div class="info-list"><div class="info-row"><span class="k">Email</span><span class="v">{{ $contactEmail }}</span></div><div class="info-row"><span class="k">Phone</span><span class="v">{{ $profile->phone ?? 'No profile yet' }}</span></div></div></div>
                <div class="panel-card"><h3 class="panel-title-space">Admin Actions</h3><div class="admin-action-list">@foreach (['active' => 'Activate partner', 'suspended' => 'Suspend partner', 'frozen' => 'Freeze partner'] as $status => $label)<form method="POST" action="{{ route('admin.dashboard.iam.account-penalty-freeze.update', $user) }}">@csrf @method('PUT')<input type="hidden" name="role" value="partner"><input type="hidden" name="status" value="{{ $status }}"><input type="hidden" name="admin_note" value="Status changed from partner detail."><button class="admin-action-btn {{ $status === 'frozen' ? 'danger' : '' }}" type="submit">{{ $label }}</button></form>@endforeach</div></div>
            </div>
        </div>
    </div>

    <div class="tab-panel" id="panel-billing">
        <div class="panel-card">
            <h3 class="panel-title-space">Current Plan</h3>
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6 col-xl-3"><div class="billing-box bib-tier"><div class="bib-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-diamond-verified-div-class-profile-meta-i"></use></svg></div><div><div class="k">Tier</div><div class="v">{{ $tierShort }}</div></div></div></div>
                <div class="col-12 col-md-6 col-xl-3"><div class="billing-box bib-status"><div class="bib-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-profile-verification-queue-5-svg"></use></svg></div><div><div class="k">Status</div><div class="v">{{ str_replace('_', ' ', ucfirst((string) $subscriptionStatus)) }}</div></div></div></div>
                <div class="col-12 col-md-6 col-xl-3"><div class="billing-box bib-renewal"><div class="bib-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-5-jul-2026-14-32-circle"></use></svg></div><div><div class="k">Renewal Date</div><div class="v">{{ $renewal }}</div></div></div></div>
                <div class="col-12 col-md-6 col-xl-3"><div class="billing-box bib-payment"><div class="bib-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-subscription-and-billing"></use></svg></div><div><div class="k">Payment Method</div><div class="v">Bank Transfer</div></div></div></div>
            </div>
            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-3"><div class="action-card ac-green" onclick="openDetailModal('renew')"><div class="ac-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-renew-subscription-extend-current-term"></use></svg></div><div class="ac-text"><div class="ac-label">Renew subscription</div><div class="ac-sub">Extend current term</div></div></div></div>
                <div class="col-12 col-md-6 col-xl-3"><div class="action-card ac-indigo" onclick="openDetailModal('changeTier')"><div class="ac-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-library-and-pfd-content-path"></use></svg></div><div class="ac-text"><div class="ac-label">Change tier</div><div class="ac-sub">Upgrade or downgrade</div></div></div></div>
                <div class="col-12 col-md-6 col-xl-3"><div class="action-card ac-blue" onclick="openDetailModal('payment')"><div class="ac-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-record-manual-payment-log-an"></use></svg></div><div class="ac-text"><div class="ac-label">Record manual payment</div><div class="ac-sub">Log an offline payment</div></div></div></div>
                <div class="col-12 col-md-6 col-xl-3"><div class="action-card ac-amber" onclick="openDetailModal('sendInvoice')"><div class="ac-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-send-invoice-email-invoice-to"></use></svg></div><div class="ac-text"><div class="ac-label">Send invoice</div><div class="ac-sub">Email invoice to partner</div></div></div></div>
            </div>
        </div>

        <div class="panel-card">
            <h3>Billing History</h3>
            <table>
                <thead><tr><th>Invoice</th><th>Period</th><th>Amount</th><th>Method</th><th>Status</th></tr></thead>
                <tbody>
                    <tr><td>INV-{{ now()->format('Y') }}-{{ str_pad((string) $user->id, 4, '0', STR_PAD_LEFT) }}</td><td>{{ $joined }} - {{ $renewal }}</td><td>{{ $tierAmountLabel }}</td><td>Bank transfer</td><td><span class="status-pill status-active"><span class="dot"></span>Paid</span><span class="row-invoice-btn" onclick="showDetailToast('TODO: invoice document contract not supplied')">View Invoice</span></td></tr>
                    <tr><td>INV-{{ now()->subYear()->format('Y') }}-{{ str_pad((string) $user->id, 4, '0', STR_PAD_LEFT) }}</td><td>Previous term</td><td>{{ $tierAmountLabel }}</td><td>Bank transfer</td><td><span class="status-pill status-active"><span class="dot"></span>Paid</span><span class="row-invoice-btn" onclick="showDetailToast('TODO: invoice document contract not supplied')">View Invoice</span></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="tab-panel" id="panel-connections">
        <div class="row g-3"><div class="col-12 col-md-6 col-xl-4"><div class="mini-stat"><div class="v">16</div><div class="l">Total Connections</div></div></div><div class="col-12 col-md-6 col-xl-4"><div class="mini-stat"><div class="v">4</div><div class="l">New This Month</div></div></div><div class="col-12 col-md-6 col-xl-4"><div class="mini-stat clickable"><div class="v">5</div><div class="l">Pending Requests</div></div></div></div>
        <div class="panel-card"><h3>Connections</h3><p class="sub">Static rebuilt panel until partner connections contract is supplied.</p></div>
    </div>

    <div class="tab-panel" id="panel-audit">
        <div class="panel-card"><h3>Account History</h3><div class="activity-item"><div class="activity-icon activity-green"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-profile-verification-queue-5-svg"></use></svg></div><div><div class="activity-text">Partner account <b>{{ str_replace('_', ' ', (string) $approval) }}</b> by <b>Admin review</b></div><div class="activity-time">{{ $profileVerifiedAt ? \Illuminate\Support\Carbon::parse($profileVerifiedAt)->format('M j, Y') : $detail['verified_at'] }}</div></div></div><div class="activity-item"><div class="activity-icon activity-blue"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-subscription-and-billing"></use></svg></div><div><div class="activity-text">Subscription state <b>{{ $subscriptionStatus }}</b> for <b>{{ $tier }}</b></div><div class="activity-time">Renewal {{ $renewal }}</div></div></div><div class="activity-item"><div class="activity-icon activity-amber"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-audit-log-pat"></use></svg></div><div><div class="activity-text">Last known login source {{ $detail['security']['latest_ip'] }}</div><div class="activity-time">{{ $detail['last_login'] }}</div></div></div></div>
    </div>

    <!-- ============ MODALS ============ -->
    <div class="modal-overlay" id="freezeModal">
        <div class="modal-box active">
            <div class="modal-head"><div class="modal-head-title"><div><h3>Freeze this account?</h3><div class="sub">{{ $company }} will lose partner access until manually restored.</div></div></div><button class="modal-close" type="button" onclick="closeDetailModal('freeze')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-change-password-choose-a-strong"></use></svg></button></div>
            <form method="POST" action="{{ route('admin.dashboard.iam.account-penalty-freeze.update', $user) }}">
                @csrf
                @method('PUT')
                <div class="modal-body"><input type="hidden" name="role" value="partner"><input type="hidden" name="status" value="frozen"><textarea class="form-textarea" name="admin_note">Frozen from IAM partner detail view.</textarea></div>
                <div class="modal-footer"><button class="modal-btn modal-btn-secondary" type="button" onclick="closeDetailModal('freeze')">Cancel</button><button class="modal-btn modal-btn-primary red" type="submit">Freeze Account</button></div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="renewModal">
        <div class="modal-box active"><div class="modal-head"><div class="modal-head-title"><div><h3>Renew Subscription</h3><div class="sub">Extend {{ $company }} {{ $tier }} term.</div></div></div><button class="modal-close" type="button" onclick="closeDetailModal('renew')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-change-password-choose-a-strong"></use></svg></button></div><div class="modal-body"><div class="invoice-block"><div class="invoice-row"><span>Current plan</span><b>{{ $tier }}</b></div><div class="invoice-row"><span>Current renewal date</span><b>{{ $renewal }}</b></div><div class="invoice-row"><span>Amount</span><b>{{ $tierAmountLabel }}</b></div></div><p class="form-hint">TODO-safe modal boundary until renewal write contract is supplied.</p></div><div class="modal-footer"><button class="modal-btn modal-btn-secondary" type="button" onclick="closeDetailModal('renew')">Cancel</button><button class="modal-btn modal-btn-primary green" type="button" onclick="closeDetailModal('renew')">Confirm Renewal</button></div></div>
    </div>
    <div class="modal-overlay" id="changeTierModal">
        <div class="modal-box active"><div class="modal-head"><div class="modal-head-title"><div><h3>Change Partner Tier</h3><div class="sub">Currently on {{ $tier }}</div></div></div><button class="modal-close" type="button" onclick="closeDetailModal('changeTier')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-change-password-choose-a-strong"></use></svg></button></div><div class="modal-body"><label class="tier-option selected"><input type="radio" checked><div><div class="to-name">{{ $tier }}</div><div class="to-price">{{ $tierAmountLabel }} - {{ $tierPeriodLabel }}</div></div></label><p class="form-hint">TODO-safe modal boundary until subscription tier update contract is supplied.</p></div><div class="modal-footer"><button class="modal-btn modal-btn-secondary" type="button" onclick="closeDetailModal('changeTier')">Cancel</button><button class="modal-btn modal-btn-primary" type="button" onclick="closeDetailModal('changeTier')">Save Changes</button></div></div>
    </div>
    <div class="modal-overlay" id="paymentModal">
        <div class="modal-box active"><div class="modal-head"><div class="modal-head-title"><div><h3>Record Manual Payment</h3><div class="sub">Log an offline payment for {{ $company }}</div></div></div><button class="modal-close" type="button" onclick="closeDetailModal('payment')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-change-password-choose-a-strong"></use></svg></button></div><div class="modal-body"><div class="form-row"><div class="form-group"><label class="form-label">Amount</label><input class="form-input" type="number" placeholder="{{ $tierAmount ? (float) $tierAmount : 0 }}"></div><div class="form-group"><label class="form-label">Payment method</label><select class="form-select"><option>Manual / Offline</option><option>Bank Transfer</option></select></div></div><p class="form-hint">TODO-safe modal boundary until payment write contract is supplied.</p></div><div class="modal-footer"><button class="modal-btn modal-btn-secondary" type="button" onclick="closeDetailModal('payment')">Cancel</button><button class="modal-btn modal-btn-primary" type="button" onclick="closeDetailModal('payment')">Record Payment</button></div></div>
    </div>
    <div class="modal-overlay" id="sendInvoiceModal">
        <div class="modal-box active"><div class="modal-head"><div class="modal-head-title"><div><h3>Send Invoice</h3><div class="sub">Email invoice to {{ $contactEmail }}</div></div></div><button class="modal-close" type="button" onclick="closeDetailModal('sendInvoice')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-change-password-choose-a-strong"></use></svg></button></div><div class="modal-body"><div class="form-group"><label class="form-label">Send to</label><input class="form-input" type="email" value="{{ $contactEmail }}"></div><p class="form-hint">TODO-safe modal boundary until invoice email contract is supplied.</p></div><div class="modal-footer"><button class="modal-btn modal-btn-secondary" type="button" onclick="closeDetailModal('sendInvoice')">Cancel</button><button class="modal-btn modal-btn-primary" type="button" onclick="closeDetailModal('sendInvoice')">Send Invoice</button></div></div>
    </div>
    <div class="toast-container" id="detailToastContainer"></div>
@endsection

@include('iam.users._detail-scripts')

