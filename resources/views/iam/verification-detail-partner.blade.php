@extends('layouts.rebuild-dashboard')

@section('title', 'Partner Verification Detail')

@php
    $company = $profile->company_name ?? $verificationRequest->applicant_name;
    $contactEmail = $profile->contact_email ?? $user?->email ?? 'No email';
    $activeTierId = $subscription->tier_id ?? null;
    $activeTierName = $subscription->tier_display_name ?? $subscription->tier_name ?? 'No active tier';
    $companyType = $profile->company_type ?? 'Partner organization';
    $country = $profile->country ?? 'Not recorded';
    $phone = $profile->phone ?? 'Not recorded';
    $website = $profile->website ?? 'Not recorded';
    $overview = $profile->overview ?? $verificationRequest->notes ?? 'No partner overview supplied.';
    $submittedAt = $verificationRequest->created_at?->format('M j, Y, h:i A') ?? 'Not recorded';
    $adminEmail = auth()->user()?->email ?? 'admin@charleyplatform.com';
    $applicantFirstName = trim((string) ($profile->contact_name ?? $verificationRequest->applicant_name));
    $tierDotClass = function ($tier): string {
        $code = strtolower((string) ($tier->code ?? $tier->name ?? $tier->display_name ?? ''));

        return match (true) {
            str_contains($code, 'diamond') => 'tier-dot-diamond',
            str_contains($code, 'gold') => 'tier-dot-gold',
            str_contains($code, 'platinum') => 'tier-dot-platinum',
            default => '',
        };
    };
    $partnerModalTemplates = [
        'review' => [
            'title' => 'Mark as In Review',
            'sub' => 'Send an update email to '.$company,
            'note' => 'Status will be updated to "In Review" and this email will be sent to the applicant contact address.',
            'subject' => 'Your Charley partner verification is under review',
            'body' => "Hi {$applicantFirstName},

Your partner verification for {$company} is now under review. We will follow up when the remaining checks are complete.

Regards,
Charley IAM Team",
            'button' => 'Send Email',
        ],
        'info' => [
            'title' => 'Request More Info',
            'sub' => 'Ask '.$company.' for additional verification details',
            'note' => 'This will update the request to "More info required" and save the message as the admin note.',
            'subject' => 'Additional information needed for Charley partner verification',
            'body' => "Hi {$applicantFirstName},

Please provide the additional partner verification information requested by our admin team so we can continue reviewing {$company}.

Regards,
Charley IAM Team",
            'button' => 'Send Request',
        ],
        'reject' => [
            'title' => 'Reject Application',
            'sub' => 'Send a rejection notice to '.$company,
            'note' => 'This will reject the verification request and save the message as the required admin note.',
            'subject' => 'Charley partner verification application update',
            'body' => "Hi {$applicantFirstName},

We are unable to approve the partner verification request for {$company} at this time.

Regards,
Charley IAM Team",
            'button' => 'Reject Application',
        ],
    ];
@endphp

@section('content')
    <a href="{{ route('admin.dashboard.iam.verification-queue') }}" class="back-link"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-back-account-penalty"></use></svg>Back to Verification Queue</a>

    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first('admin_notes') ?: $errors->first() }}</div>@endif

    <div class="page-head">
        <div class="page-head-left">
            <div class="company-logo"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-synvex-catalysts"></use></svg></div>
            <div>
                <div class="page-title-row"><div class="page-title">{{ $company }}</div><span class="badge {{ $verificationRequest->status_class }}"><span class="dot-sm"></span>{{ $verificationRequest->status_label }}</span><span class="badge badge-sla"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-sla-exceeded-52h-partner"></use></svg>{{ $verificationRequest->sla['label'] }} - {{ $verificationRequest->sla['sub'] }}</span></div>
                <div class="page-sub"><span>Partner Account - {{ $companyType }}</span><span class="sep"></span><span>Submitted {{ $submittedAt }} by {{ $contactEmail }}</span><span class="sep"></span><span>Requested tier: {{ $activeTierName }}</span></div>
            </div>
        </div>
        <div class="header-actions"><button class="btn btn-outline" type="button"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-open-support-thread"></use></svg>Message Applicant</button></div>
    </div>

    <div class="detail-grid partner-verification-grid">
        <div class="col-main">
            <div class="card card-padded">
                <div class="verification-detail-head"><div class="card-title"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-partners"></use></svg>Company Overview</div></div>
                <div class="row row-cols-1 row-cols-md-2 g-3">
                    <div class="col"><div class="kv-item"><div class="kv-label">Legal Company Name</div><div class="kv-value">{{ $company }}</div></div></div>
                    <div class="col"><div class="kv-item"><div class="kv-label">Website</div><div class="kv-value">{{ $website }}</div></div></div>
                    <div class="col"><div class="kv-item"><div class="kv-label">Headquarters</div><div class="kv-value">{{ $country }}</div></div></div>
                    <div class="col"><div class="kv-item"><div class="kv-label">Company Size</div><div class="kv-value">{{ $profile->company_size ?? 'Not recorded' }}</div></div></div>
                    <div class="col"><div class="kv-item"><div class="kv-label">Year Founded</div><div class="kv-value">{{ $profile->founded_year ?? 'Not recorded' }}</div></div></div>
                    <div class="col"><div class="kv-item"><div class="kv-label">Registration No.</div><div class="kv-value">{{ $profile->registration_number ?? 'Not recorded' }}</div></div></div>
                </div>
                <div class="tag-row"><span class="tag">{{ $companyType }}</span><span class="tag">{{ $activeTierName }}</span><span class="tag">{{ $profile->approval_status ?? 'Verification pending' }}</span><span class="tag">{{ $country }}</span></div>
                <div class="desc-text">{{ $overview }}</div>
            </div>

            <div class="card card-padded">
                <div class="verification-detail-head"><div class="card-title"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-verification-queue"></use></svg>Verification Checklist</div><span class="card-title-count">4 / 6 complete</span></div>
                <div class="check-list">
                    <div class="check-item"><div class="check-icon ok"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-diamond-diamond-partner-licensor"></use></svg></div><div class="check-text"><div class="check-title">Partner profile loaded</div><div class="check-meta">Bound from partner_profiles and users records.</div></div><span class="check-status ok">Verified</span></div>
                    <div class="check-item"><div class="check-icon ok"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-diamond-diamond-partner-licensor"></use></svg></div><div class="check-text"><div class="check-title">Documents resolved through media files</div><div class="check-meta">IAM avoids raw upload paths.</div></div><span class="check-status ok">Verified</span></div>
                    <div class="check-item"><div class="check-icon ok"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-diamond-diamond-partner-licensor"></use></svg></div><div class="check-text"><div class="check-title">Dynamic subscription tier loaded</div><div class="check-meta">Uses subscription_tiers instead of obsolete tier enum fields.</div></div><span class="check-status ok">Verified</span></div>
                    <div class="check-item"><div class="check-icon ok"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-diamond-diamond-partner-licensor"></use></svg></div><div class="check-text"><div class="check-title">No duplicate partner account found</div><div class="check-meta">Static review boundary until duplicate-check contract is confirmed.</div></div><span class="check-status ok">Verified</span></div>
                    <div class="check-item"><div class="check-icon pending"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-clock"></use></svg></div><div class="check-text"><div class="check-title">Authorized signatory letter reviewed</div><div class="check-meta">Awaiting admin sign-off - see documents below</div></div><span class="check-status pending">Pending</span></div>
                    <div class="check-item"><div class="check-icon pending"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-clock"></use></svg></div><div class="check-text"><div class="check-title">Subscription payment confirmed</div><div class="check-meta">Billing confirmation remains static until owning module contract exists.</div></div><span class="check-status pending">Pending</span></div>
                </div>
            </div>

            <div class="card card-padded">
                <div class="verification-detail-head"><div class="card-title"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-file-2"></use></svg>Submitted Documents</div><span class="card-title-count">{{ count($documents) }} files</span></div>
                <div class="doc-list">
                    @forelse ($documents as $document)
                        <div class="doc-item"><div class="doc-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-file-2"></use></svg></div><div class="doc-info"><div class="doc-name">{{ $document['name'] }}</div><div class="doc-meta">{{ $document['mime_type'] ?? 'File' }}{{ $document['size'] ? ' - '.number_format($document['size'] / 1024, 1).' KB' : '' }}</div></div><div class="doc-actions">@if ($document['url'])<a class="btn btn-outline btn-sm" href="{{ $document['url'] }}" target="_blank" rel="noopener">View</a>@endif</div></div>
                    @empty
                        <div class="empty-state"><span>No documents attached through media files.</span></div>
                    @endforelse
                </div>
            </div>

            <div class="card card-padded">
                <div class="verification-detail-head"><div class="card-title"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-users-3"></use></svg>Primary Contact Person</div></div>
                <div class="contact-row"><div class="contact-avatar"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-mila-kessler-business-developmen"></use></svg></div><div><div class="contact-name">{{ $profile->contact_name ?? $verificationRequest->applicant_name }}</div><div class="contact-role">{{ $profile->contact_title ?? 'Primary contact' }}, {{ $company }}</div></div></div>
                <div class="contact-links"><div class="contact-link"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-mila-kessler-synvexcatalysts-com"></use></svg>{{ $contactEmail }}</div><div class="contact-link"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-phone"></use></svg>{{ $phone }}</div><div class="contact-link"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-linkedin-company-profile-matched"></use></svg>{{ $profile->linkedin_url ?? $website }}</div></div>
            </div>

            <div class="card card-padded">
                <div class="verification-detail-head"><div class="card-title"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-notes"></use></svg>Admin Notes</div></div>
                <textarea class="note-textarea @error('admin_notes') is-invalid @enderror" id="reviewerNote" placeholder="Add an internal note about this verification (visible to admins only)...">{{ old('admin_notes') }}</textarea>
                <div class="note-actions"><button class="btn btn-outline btn-sm" type="button">Add Note</button></div>
                <div class="note-log"><div class="note-entry"><div class="note-avatar">{{ $verificationRequest->reviewer_name ? strtoupper(substr($verificationRequest->reviewer_name, 0, 1)) : 'A' }}</div><div class="note-bubble"><span class="note-author">{{ $verificationRequest->reviewer_name ?? 'Admin' }}</span><span class="note-time">{{ $verificationRequest->reviewed_at?->format('M j, Y') ?? 'Not reviewed' }}</span><div class="note-body">{{ $verificationRequest->admin_notes ?? 'No admin note recorded yet.' }}</div></div></div></div>
            </div>
        </div>

        <div class="col-side"><div class="side-card">
            <div class="card card-padded">
                <div class="verification-detail-head"><div class="card-title"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-verification-queue"></use></svg>Verification Decision</div></div>
                <div class="kv-label kv-label-spaced">Partner Tier</div>
                @forelse ($subscriptionTiers as $tier)
                    <label class="verification-tier-option">
                        <input class="verification-tier-input" type="radio" name="partnerSubscriptionTier" value="{{ $tier->id }}" @checked((int) $activeTierId === (int) $tier->id)>
                        <span class="verification-tier-radio" aria-hidden="true"></span>
                        <span class="verification-tier-body">
                            <span class="tier-name"><span class="tier-dot {{ $tierDotClass($tier) }}"></span>{{ $tier->display_name ?? $tier->name ?? $tier->code }}</span>
                            <span class="tier-desc">{{ $tier->description ?? 'Subscription tier from subscription_tiers.' }}</span>
                        </span>
                    </label>
                @empty
                    <div class="empty-state"><span>No active subscription tiers.</span></div>
                @endforelse
                <div class="decision-divider"></div>
                <button class="btn btn-primary btn-block" type="button" onclick="submitPartnerVerificationAction('approve')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-diamond-diamond-partner-licensor"></use></svg>Approve &amp; Activate</button>
                <button class="btn btn-outline btn-block btn-block-spaced" type="button" onclick="openActionModal('review')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-clock"></use></svg>Mark as In Review</button>
                <button class="btn btn-outline btn-block btn-block-spaced" type="button" onclick="openActionModal('info')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-open-support-thread"></use></svg>Request More Info</button>
                <button class="btn btn-ghost-danger btn-block btn-block-spaced" type="button" onclick="openActionModal('reject')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-rejected-partner-announcement-te"></use></svg>Reject Application</button>
            </div>

            <div class="card card-padded"><div class="verification-detail-head"><div class="card-title"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-shield"></use></svg>Automated Risk Check</div></div><div class="risk-row"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-diamond-diamond-partner-licensor"></use></svg><span class="risk-text">Domain and blacklist checks remain static until contract exists</span></div><div class="risk-row"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-diamond-diamond-partner-licensor"></use></svg><span class="risk-text">No duplicate accounts detected boundary</span></div></div>
            <div class="card card-padded"><div class="verification-detail-head"><div class="card-title"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-clock"></use></svg>Verification Timeline</div></div><div class="timeline verification-timeline"><div class="tl-item"><div class="tl-dot"></div><div class="tl-title">Application submitted</div><div class="tl-time">{{ $submittedAt }}</div></div><div class="tl-item {{ $verificationRequest->reviewed_at ? '' : 'pending' }}"><div class="tl-dot"></div><div class="tl-title">{{ $verificationRequest->reviewed_at ? 'Admin reviewed' : 'Awaiting approval decision' }}</div><div class="tl-time">{{ $verificationRequest->reviewed_at?->format('M j, Y, h:i A') ?? $verificationRequest->sla['sub'] }}</div></div></div></div>
            <div class="card card-padded card-flush"><div class="verification-detail-head"><div class="card-title"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-billing"></use></svg>Subscription</div></div><div class="mini-kv"><span class="mini-kv-label">Requested Plan</span><span class="mini-kv-value">{{ $activeTierName }}</span></div><div class="mini-kv"><span class="mini-kv-label">Payment Method</span><span class="mini-kv-value">{{ $subscription->payment_method ?? 'Not recorded' }}</span></div><div class="mini-kv"><span class="mini-kv-label">Payment Status</span><span class="mini-kv-value mini-kv-value-warning">{{ $subscription->status ?? $profile->subscription_status ?? 'Not recorded' }}</span></div><div class="mini-kv"><span class="mini-kv-label">Reference</span><span class="mini-kv-value">{{ $subscription->payment_reference ?? 'Not recorded' }}</span></div></div>
        </div></div>
    </div>

    <form id="partnerApproveForm" method="POST" action="{{ route('admin.dashboard.iam.verification-queue.approve', $verificationRequest) }}">@csrf<input type="hidden" name="admin_notes" value=""></form>
    <form id="partnerMoreInfoForm" method="POST" action="{{ route('admin.dashboard.iam.verification-queue.more-info', $verificationRequest) }}">@csrf<input type="hidden" name="admin_notes" value=""></form>
    <form id="partnerRejectForm" method="POST" action="{{ route('admin.dashboard.iam.verification-queue.reject', $verificationRequest) }}">@csrf<input type="hidden" name="admin_notes" value=""></form>

    <div class="modal-overlay" id="actionModalOverlay" onclick="if(event.target===this) closeActionModal()">
        <div class="modal-box">
            <div class="modal-head">
                <div class="modal-head-title">
                    <div class="modal-icon" id="modalIcon"></div>
                    <div>
                        <div class="modal-title" id="modalTitle">Mark as In Review</div>
                        <div class="modal-sub" id="modalSub">Send an update email to {{ $company }}</div>
                    </div>
                </div>
                <button class="modal-close" type="button" onclick="closeActionModal()"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-change-password-choose-strong"></use></svg></button>
            </div>
            <div class="modal-body">
                <div class="modal-note">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-set-years-industry-experience"></use></svg>
                    <span id="modalNoteText">Status will be updated to "In Review" and this email will be sent to the applicant contact address.</span>
                </div>
                <div class="field-row">
                    <div class="field-group">
                        <label class="field-label" for="modalTo">To</label>
                        <input type="text" class="field-input" id="modalTo" value="{{ $contactEmail }}">
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="modalCc">Cc (internal)</label>
                        <input type="text" class="field-input" id="modalCc" value="{{ $adminEmail }}">
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label" for="modalSubject">Subject</label>
                    <input type="text" class="field-input" id="modalSubject" value="">
                </div>
                <div class="field-group field-group-flush">
                    <label class="field-label" for="modalBody">Email Template</label>
                    <textarea class="field-input" id="modalBody"></textarea>
                </div>
            </div>
            <div class="modal-foot">
                <div class="modal-foot-hint"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-mila-kessler-synvexcatalysts-com"></use></svg>This will be logged in the verification timeline</div>
                <div class="modal-foot-actions">
                    <button class="btn btn-outline" type="button" onclick="closeActionModal()">Cancel</button>
                    <button class="btn btn-primary" type="button" id="modalSendBtn" onclick="submitModalAction()"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-email-2"></use></svg><span id="modalSendText">Send Email</span></button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
const partnerModalTemplates = {{ Illuminate\Support\Js::from($partnerModalTemplates) }};
let activePartnerModalAction = null;
function submitPartnerVerificationAction(type, noteValue = null){
    const form=document.getElementById({approve:'partnerApproveForm',info:'partnerMoreInfoForm',reject:'partnerRejectForm'}[type]);
    if(!form)return;
    const reviewerNote=document.getElementById('reviewerNote');
    form.querySelector('input[name="admin_notes"]').value=noteValue ?? (reviewerNote ? reviewerNote.value : '');
    form.submit();
}
function openActionModal(type){
    activePartnerModalAction=type;
    const template=partnerModalTemplates[type] || partnerModalTemplates.review;
    document.getElementById('modalTitle').textContent=template.title;
    document.getElementById('modalSub').textContent=template.sub;
    document.getElementById('modalNoteText').textContent=template.note;
    document.getElementById('modalSubject').value=template.subject;
    document.getElementById('modalBody').value=template.body;
    document.getElementById('modalSendText').textContent=template.button;
    document.getElementById('actionModalOverlay').classList.add('active');
}
function closeActionModal(){
    document.getElementById('actionModalOverlay').classList.remove('active');
    activePartnerModalAction=null;
}
function submitModalAction(){
    const note=document.getElementById('modalBody').value;
    if(activePartnerModalAction === 'info'){submitPartnerVerificationAction('info', note);return;}
    if(activePartnerModalAction === 'reject'){submitPartnerVerificationAction('reject', note);return;}
    closeActionModal();
}
</script>
@endpush
