@extends('layouts.rebuild-dashboard')

@section('title', 'Verify Applicant')

@php
    $name = $verificationRequest->applicant_name;
    $email = $user?->email ?? 'No email';
    $company = $profile->current_company ?? $profile->current_institution ?? 'No employer recorded';
    $position = $profile->position ?? 'No role recorded';
    $expertise = $profile->industry_specialization ?? $profile->field_of_study ?? $verificationRequest->expertise_label;
    $years = $profile->experience_years ?? null;
    $phone = $profile->phone ?? 'No phone';
    $linkedin = $profile->linkedin_url ?? 'Not supplied';
    $summary = $profile->bio ?? $verificationRequest->notes ?? 'No applicant summary supplied.';
    $submittedAt = $verificationRequest->created_at?->format('M j, Y, h:i A') ?? 'Not recorded';
@endphp

@section('content')
    <a href="{{ route('admin.dashboard.iam.verification-queue') }}" class="back-link"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-back-account-penalty"></use></svg>Back to Verification Queue</a>

    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first('admin_notes') ?: $errors->first() }}</div>@endif

    <div class="profile-head">
        <div class="profile-head-left">
            <div class="profile-avatar">{{ strtoupper(substr($name, 0, 1)) }}</div>
            <div>
                <div class="profile-name-row"><div class="profile-name">{{ $name }}</div><span class="badge {{ $verificationRequest->status_class }}" id="statusBadge"><span class="dot-sm"></span>{{ $verificationRequest->status_label }}</span></div>
                <div class="profile-role">{{ $position }} at <b>{{ $company }}</b></div>
                <div class="profile-tags">
                    <span class="meta-chip"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-partners"></use></svg>{{ $expertise }}</span>
                    <span class="meta-chip"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-yrs-experience-p"></use></svg>{{ $years !== null ? $years.' yrs experience' : 'Experience not recorded' }}</span>
                    <span class="meta-chip"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-james-carter-petrotechvn-com"></use></svg>{{ $email }}</span>
                    <span class="meta-chip"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-phone-2"></use></svg>{{ $phone }}</span>
                </div>
            </div>
        </div>
        <div class="profile-head-right"><div class="sla-chip"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-user-k-habib-flagged"></use></svg>{{ $verificationRequest->sla['label'] }} - {{ $verificationRequest->sla['sub'] }}</div><div class="submitted-info">Application ID: VR-{{ $verificationRequest->id }}<br>Submitted {{ $submittedAt }}</div></div>
    </div>

    <div class="detail-grid verification-detail-grid">
        <div>
            <div class="section-card section-card-padded">
                <div class="section-head verification-section-head"><div class="section-title"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-users-3"></use></svg>Profile Details</div></div>
                <div class="row row-cols-1 row-cols-md-2 g-3">
                    <div class="col"><div><div class="field-label">Full name</div><div class="field-value">{{ $name }}</div></div></div>
                    <div class="col"><div><div class="field-label">Requested expertise category</div><div class="field-value">{{ $expertise }}</div></div></div>
                    <div class="col"><div><div class="field-label">Current employer</div><div class="field-value">{{ $company }}</div></div></div>
                    <div class="col"><div><div class="field-label">Job title</div><div class="field-value">{{ $position }}</div></div></div>
                    <div class="col"><div><div class="field-label">Work email</div><div class="field-value">{{ $email }}</div></div></div>
                    <div class="col"><div><div class="field-label">Phone</div><div class="field-value">{{ $phone }}</div></div></div>
                    <div class="col"><div><div class="field-label">Verification method</div><div class="field-value">{{ $verificationRequest->method_label }}</div></div></div>
                    <div class="col"><div><div class="field-label">LinkedIn</div><div class="field-value mono">{{ $linkedin }}</div></div></div>
                </div>
                <div class="field-summary-block"><div class="field-label">Applicant summary</div><div class="bio-text bio-text-spaced">{{ $summary }}</div></div>
            </div>

            <div class="section-card section-card-padded">
                <div class="section-head verification-section-head"><div class="section-title"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-partners"></use></svg>Work History</div><div class="section-sub">Self-reported, cross-checked against submitted documents</div></div>
                <div class="history-item"><div class="history-dot-col"><div class="history-dot"></div><div class="history-line"></div></div><div><div class="history-role">{{ $position }}</div><div class="history-company">{{ $company }}</div><div class="history-period">Current role</div><div class="history-desc">Primary profile role submitted with this verification request.</div></div></div>
                <div class="history-item"><div class="history-dot-col"><div class="history-dot history-dot-muted"></div></div><div><div class="history-role">Professional background</div><div class="history-company">Charley verification record</div><div class="history-period">{{ $years !== null ? $years.' years reported' : 'Duration not recorded' }}</div><div class="history-desc">Activity, contribution history, AI usage, reputation, and connections remain TODO-safe static boundaries.</div></div></div>
            </div>

            <div class="section-card section-card-padded">
                <div class="section-head verification-section-head"><div class="section-title"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-file-2"></use></svg>Credentials &amp; Documents</div><div class="section-sub">{{ count($documents) }} files submitted</div></div>
                <div class="row row-cols-1 row-cols-md-2 g-3">
                    @forelse ($documents as $document)
                        <div class="col"><div class="doc-card"><div class="doc-thumb icon-tone-danger"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-file-2"></use></svg></div><div><div class="doc-name">{{ $document['name'] }}</div><div class="doc-meta">{{ $document['mime_type'] ?? 'File' }}{{ $document['size'] ? ' - '.number_format($document['size'] / 1024, 1).' KB' : '' }}</div></div>@if ($document['url'])<a class="doc-open" href="{{ $document['url'] }}" target="_blank" rel="noopener"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-eye"></use></svg></a>@endif</div></div>
                    @empty
                        <div class="col"><div class="empty-state"><span>No documents attached through media files.</span></div></div>
                    @endforelse
                </div>
            </div>

            <div class="section-card section-card-padded section-card-flush">
                <div class="section-head verification-section-head"><div class="section-title"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-verification-queue"></use></svg>Verification Checklist</div></div>
                <div class="check-list">
                    <div class="check-item"><div class="check-left"><div class="check-icon done"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-check-2"></use></svg></div><div><div class="check-title">Applicant identity data loaded</div><div class="check-sub">Bound from users and engineer profile records.</div></div></div><span class="check-status done">Ready</span></div>
                    <div class="check-item"><div class="check-left"><div class="check-icon done"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-check-2"></use></svg></div><div><div class="check-title">Documents resolved through media files</div><div class="check-sub">IAM does not display raw upload paths.</div></div></div><span class="check-status done">Safe</span></div>
                    <div class="check-item"><div class="check-left"><div class="check-icon warn"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-certification-authenticity-pe-li"></use></svg></div><div><div class="check-title">Manual credential review</div><div class="check-sub">Admin decision and notes use existing verification routes.</div></div></div><span class="check-status warn">Needs review</span></div>
                    <div class="check-item"><div class="check-left"><div class="check-icon idle"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-clock"></use></svg></div><div><div class="check-title">External activity checks</div><div class="check-sub">Reputation and connections stay static until their module contracts exist.</div></div></div><span class="check-status idle">Not started</span></div>
                </div>
            </div>
        </div>

        <div class="decision-panel">
            <div class="decision-title">Review decision</div><div class="decision-sub">Approving grants verified status for this applicant. Reject and more-info decisions require an admin note.</div>
            <button class="decision-btn approve" type="button" onclick="submitVerificationAction('approve')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-check-2"></use></svg>Approve &amp; Verify</button>
            <button class="decision-btn inreview" type="button" onclick="openActionModal('inreview')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-clock"></use></svg>In Review</button>
            <button class="decision-btn info" type="button" onclick="openActionModal('info')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-featured-answer-on-co-absorber"></use></svg>Request More Info</button>
            <button class="decision-btn reject" type="button" onclick="openActionModal('reject')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-reject-application-internal-revi"></use></svg>Reject Application</button>
            <div class="divider-h"></div><label class="note-label" for="reviewerNote">Internal reviewer note</label><textarea id="reviewerNote" class="note-box @error('admin_notes') is-invalid @enderror" placeholder="Add context for this decision - visible only to Admins...">{{ old('admin_notes') }}</textarea>
            <div class="submitted-info">Reviewed by: {{ $verificationRequest->reviewer_name ?? 'Not reviewed' }}<br>Reviewed at: {{ $verificationRequest->reviewed_at?->format('M j, Y, h:i A') ?? 'Not reviewed' }}</div>
        </div>
    </div>

    <form id="approveForm" method="POST" action="{{ route('admin.dashboard.iam.verification-queue.approve', $verificationRequest) }}">@csrf<input type="hidden" name="admin_notes" value=""></form>
    <form id="moreInfoForm" method="POST" action="{{ route('admin.dashboard.iam.verification-queue.more-info', $verificationRequest) }}">@csrf<input type="hidden" name="admin_notes" value=""></form>
    <form id="rejectForm" method="POST" action="{{ route('admin.dashboard.iam.verification-queue.reject', $verificationRequest) }}">@csrf<input type="hidden" name="admin_notes" value=""></form>
@endsection

@push('scripts')
<script>
let currentVerificationAction='info';
function openActionModal(type){currentVerificationAction=type;if(type==='info')submitVerificationAction('info');else if(type==='reject')submitVerificationAction('reject');}
function submitVerificationAction(type){const form=document.getElementById({approve:'approveForm',info:'moreInfoForm',reject:'rejectForm'}[type]);if(!form)return;form.querySelector('input[name="admin_notes"]').value=document.getElementById('reviewerNote').value;form.submit();}
</script>
@endpush
