@extends('layouts.rebuild-dashboard')

@section('title', 'Edit Engineer User')

@php
    $isProfessional = old('account_type', $user->role === 'professional' ? 'professional' : 'member') === 'professional';
    $displayName = trim(implode(' ', array_filter([$user->first_name, $user->last_name]))) ?: ($user->username ?: $user->email);
    $initials = collect(explode(' ', trim($displayName)))->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('') ?: 'U';
    $company = $profile->current_company ?? $profile->current_institution ?? '';
    $position = $profile->position ?? $profile->field_of_study ?? '';
    $experienceYears = $profile->experience_years ?? null;
    $jsonList = function ($value) {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        }

        if (is_array($value)) {
            return collect($value)->flatten()->filter()->implode(', ');
        }

        return (string) ($value ?? '');
    };
    $selectedPlantTypes = collect(old('plant_type_ids', $selectedPlantTypeIds ?? []))->map(fn ($id) => (string) $id)->all();
    $expertiseTags = $jsonList($profile->expertise_tags ?? null);
    $industrySpecialization = $jsonList($profile->industry_specialization ?? null);
    $searchableKeywords = $jsonList($profile->searchable_keywords ?? null);
    $splitList = fn ($value) => collect(explode(',', (string) $value))->map(fn ($item) => trim($item))->filter()->values();
    $expertiseTagItems = $splitList($expertiseTags);
    $keywordItems = $splitList($searchableKeywords);
    $topAreaItems = $splitList($industrySpecialization);
    $experienceValue = is_numeric($experienceYears) ? (int) $experienceYears : null;
    $rankLabel = is_null($experienceValue) ? 'Registered Member' : ($experienceValue >= 15 ? 'Senior Industry Expert' : ($experienceValue >= 8 ? 'Experienced Professional' : 'Industry Professional'));
    $rankCeiling = is_null($experienceValue) ? 0 : ($experienceValue >= 15 ? 70 : ($experienceValue >= 8 ? 50 : 30));
    $knowledgeDomainOptions = \Illuminate\Support\Facades\DB::table('knowledge_domains')
        ->select('id', 'name', 'plant_type_id')
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get();
    $userDomainExpertise = \Illuminate\Support\Facades\DB::table('user_domain_expertise')
        ->where('user_id', $user->id)
        ->get()
        ->keyBy('knowledge_domain_id');
    $passedDomainIds = \Illuminate\Support\Facades\DB::table('quiz_attempts')
        ->where('user_id', $user->id)
        ->where('is_passed', true)
        ->pluck('knowledge_domain_id')
        ->map(fn ($id) => (string) $id)
        ->all();
    $domainPayload = $knowledgeDomainOptions->map(function ($domain) use ($userDomainExpertise, $passedDomainIds) {
        $expertise = $userDomainExpertise->get($domain->id);

        return [
            'id' => (string) $domain->id,
            'name' => $domain->name,
            'plant_type_id' => is_null($domain->plant_type_id) ? null : (string) $domain->plant_type_id,
            'self_rated_percentage' => is_null($expertise?->self_rated_percentage) ? null : (int) $expertise->self_rated_percentage,
            'is_quiz_unlocked' => (bool) ($expertise?->is_quiz_unlocked ?? false),
            'quiz_passed' => in_array((string) $domain->id, $passedDomainIds, true),
        ];
    })->values();
    $findDomainByName = fn ($name) => $knowledgeDomainOptions->first(fn ($domain) => strcasecmp($domain->name, (string) $name) === 0);
    $topAreaRows = $topAreaItems->take(5)->map(function ($area) use ($findDomainByName, $userDomainExpertise, $passedDomainIds, $rankCeiling) {
        $domain = $findDomainByName($area);
        $expertise = $domain ? $userDomainExpertise->get($domain->id) : null;
        $isUnlocked = (bool) ($expertise?->is_quiz_unlocked ?? false) || ($domain && in_array((string) $domain->id, $passedDomainIds, true));
        $rating = is_null($expertise?->self_rated_percentage) ? min($rankCeiling, 65) : (int) $expertise->self_rated_percentage;

        return [
            'name' => (string) $area,
            'domain_id' => $domain ? (string) $domain->id : '',
            'plant_type_id' => $domain && ! is_null($domain->plant_type_id) ? (string) $domain->plant_type_id : '',
            'quiz_passed' => $domain && in_array((string) $domain->id, $passedDomainIds, true),
            'is_quiz_unlocked' => $isUnlocked,
            'value' => min($isUnlocked ? 100 : $rankCeiling, max(0, $rating)),
        ];
    })->values();
    if ($topAreaRows->isEmpty()) {
        $topAreaRows = collect([[
            'name' => '',
            'domain_id' => '',
            'plant_type_id' => '',
            'quiz_passed' => false,
            'is_quiz_unlocked' => false,
            'value' => min($rankCeiling, 20),
        ]]);
    }
    $verificationMethod = $latestVerificationRequest->verification_method ?? null;
@endphp

@section('content')
    <form id="editEngineerForm" method="POST" action="{{ route('admin.dashboard.iam.users.update-engineer', $user) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Unable to save changes.</strong> Please review the highlighted fields and try again.
            </div>
        @endif

        <div class="page-head">
            <a class="back-btn" href="{{ route('admin.dashboard.iam.users.show', $user) }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-back-to-account-penalty-and"></use></svg>
                Back to User Profile
            </a>
            <div class="header-actions">
                <a class="btn-ghost" href="{{ route('admin.dashboard.iam.users.show', $user) }}">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-button-class-btn-btn-ghost-style-flex-1-onclick-"></use></svg>
                    <span>Discard</span>
                </a>
                <button class="btn-primary" type="submit">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-save-as-draft-svg-viewbox-0"></use></svg>
                    Save Changes
                </button>
            </div>
        </div>

        <div class="edit-title-strip">
            <div class="edit-avatar">{{ $initials }}</div>
            <div class="edit-title-info">
                <div class="edit-title-name">{{ $displayName }}</div>
                <div class="edit-title-sub">User ID #{{ str_pad((string) $user->id, 5, '0', STR_PAD_LEFT) }} - Editing profile as Super Admin - Changes are logged to audit trail</div>
                <div class="edit-title-badges">
                    <span class="badge professional"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-profile-verification-queue-5-svg"></use></svg>{{ $user->role === 'professional' ? 'Verified Professional' : 'Registered Member' }}</span>
                    <span class="badge senior">{{ ((int) $experienceYears >= 15) ? 'Senior Industry Expert' : (((int) $experienceYears >= 8) ? 'Experienced Professional' : 'Industry Professional') }}</span>
                    <span class="badge active"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-9-verifications-exceeded-the-48h"></use></svg>{{ ucfirst($user->status) }}</span>
                </div>
            </div>
            <div class="unsaved-pill" id="unsavedPill">Unsaved changes</div>
        </div>

        <div class="edit-fixed-layout">
            <div class="edit-nav">
                <div class="edit-nav-section">Sections</div>
                <div class="edit-nav-item active" data-section="sec-basic"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-my-profile-path-d-m"></use></svg>Basic Information</div>
                <div class="edit-nav-item" data-section="sec-professional"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-partner-management-path-d-m9-12"></use></svg>Professional Details</div>
                <div class="edit-nav-item" data-section="sec-expertise"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-expertise-and-plant-focus-svg"></use></svg>Expertise &amp; Plant Focus<span class="nav-dot"></span></div>
                <div class="edit-nav-item" data-section="sec-topexpertise"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-top-expertise-areas-svg-viewbox-0"></use></svg>Top Expertise Areas</div>
                <div class="edit-nav-item" data-section="sec-account"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-penalty-history-3-actions-recorded"></use></svg>Account &amp; Role</div>
                <div class="edit-nav-item" data-section="sec-verification"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-profile-verification-queue-5-svg"></use></svg>Verification Status</div>
                <div class="edit-nav-item" data-section="sec-privacy"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-account-penalty-and-freeze-3"></use></svg>Privacy &amp; Visibility</div>
                <div class="edit-nav-divider"></div>
                <div class="edit-nav-section">Activity</div>
                <div class="edit-nav-item" data-section="sec-audit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-ai-usage-monitor-charley-calculator"></use></svg>Edit Audit Log</div>
            </div>

            <div class="edit-right">
                <div class="form-card" id="sec-basic">
                    <div class="form-card-head"><div class="form-accent blue"></div><div class="form-head-inner"><div class="form-icon blue"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-my-profile-path-d-m"></use></svg></div><div><div class="form-title">Basic Information</div><div class="form-sub">Name, contact details, and profile photo</div></div></div></div>
                    <div class="form-body">
                        <div class="form-row"><div class="form-group"><label class="form-label" for="profilePhotoInput">Profile Photo</label><div class="avatar-upload-zone"><div class="avatar-preview">@if ($profilePhotoUrl)<img src="{{ $profilePhotoUrl }}" alt="{{ $displayName }} profile photo" style="width: 100%;height: 100%;object-fit: cover;">@else{{ $initials }}@endif</div><div class="avatar-upload-info"><div class="avatar-upload-title">Upload a new photo</div><div class="avatar-upload-sub">JPG or PNG, max 2 MB. Current profile {{ $profilePhotoUrl ? 'uses uploaded media.' : 'uses initials placeholder.' }}</div><label class="avatar-upload-btn" for="profilePhotoInput"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-click-to-upload-or-drag"></use></svg>Choose file</label><input class="visually-hidden @error('profile_photo') is-invalid @enderror" id="profilePhotoInput" type="file" name="profile_photo" accept="image/jpeg,image/png">@error('profile_photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div></div></div></div>
                        <div class="form-divider"></div>
                        <div class="row row-cols-1 row-cols-md-2 g-3"><div class="form-group"><label class="form-label">First Name <span class="required">*</span></label><input class="form-input @error('first_name') is-invalid @enderror" type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required>@error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="form-group"><label class="form-label">Last Name</label><input class="form-input @error('last_name') is-invalid @enderror" type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}">@error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>
                        <div class="row row-cols-1 row-cols-md-2 g-3"><div class="form-group"><label class="form-label">Display Name <span class="hint">(shown on platform)</span></label><input class="form-input" type="text" value="{{ $displayName }}" disabled></div><div class="form-group"><label class="form-label">Job Title <span class="required">*</span></label><input class="form-input @error('position') is-invalid @enderror" type="text" name="position" value="{{ old('position', $position) }}">@error('position')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>
                        <div class="row row-cols-1 row-cols-md-2 g-3"><div class="form-group"><label class="form-label">Work Email <span class="required">*</span></label><input class="form-input @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email', $user->email) }}" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-hint">Used for verification and system notifications.</div></div><div class="form-group"><label class="form-label">Secondary / Personal Email</label><input class="form-input" type="email" placeholder="Optional"></div></div>
                        <div class="row row-cols-1 row-cols-md-2 g-3"><div class="form-group"><label class="form-label">LinkedIn Profile URL</label><input class="form-input @error('linkedin_url') is-invalid @enderror" type="url" name="linkedin_url" value="{{ old('linkedin_url', $profile->linkedin_url ?? '') }}" placeholder="https://linkedin.com/in/...">@error('linkedin_url')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="form-group"><label class="form-label">Country / Region</label><select class="form-select"><option>Netherlands</option><option>Saudi Arabia</option><option>United States</option><option>India</option><option>Other</option></select></div></div>
                        <div class="form-row"><div class="form-group"><label class="form-label">Short Bio <span class="hint">(visible on profile)</span></label><textarea class="form-textarea @error('bio') is-invalid @enderror" name="bio">{{ old('bio', $profile->bio ?? 'Over 22 years of experience in hydrogen and syngas plant engineering, specialising in primary reforming, catalyst management, and PSA purification systems.') }}</textarea>@error('bio')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-hint">Max 300 characters. Displayed on public profile and expert directory.</div></div></div>
                    </div>
                </div>

                <div class="form-card" id="sec-professional">
                    <div class="form-card-head"><div class="form-accent emerald"></div><div class="form-head-inner"><div class="form-icon emerald"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-partner-management-path-d-m9-12"></use></svg></div><div><div class="form-title">Professional Details</div><div class="form-sub">Employer, experience level, and industry context</div></div></div></div>
                    <div class="form-body"><div class="row row-cols-1 row-cols-md-2 g-3"><div class="form-group"><label class="form-label">Company / Employer <span class="required">*</span></label><input class="form-input @error('current_company') is-invalid @enderror" type="text" name="current_company" value="{{ old('current_company', $company) }}">@error('current_company')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="form-group"><label class="form-label">Years of Experience <span class="required">*</span></label><input class="form-input @error('experience_years') is-invalid @enderror" type="number" min="0" max="80" name="experience_years" value="{{ old('experience_years', $experienceYears) }}">@error('experience_years')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div><div class="row row-cols-1 row-cols-md-2 g-3"><div class="form-group"><label class="form-label">Job Availability Status</label><select class="form-select" name="job_availability"><option value="not_looking" @selected(old('job_availability', $profile->job_availability ?? '') === 'not_looking')>Not open to opportunities</option><option value="open_to_opportunities" @selected(old('job_availability', $profile->job_availability ?? '') === 'open_to_opportunities')>Open to opportunities</option><option value="open" @selected(old('job_availability', $profile->job_availability ?? '') === 'open')>Actively looking</option></select></div></div><div class="form-row"><div class="form-group"><label class="form-label">Professional Summary <span class="hint">(internal - not shown publicly)</span></label><textarea class="form-textarea" name="education">{{ old('education', $profile->education ?? 'Senior process engineering specialist with deep expertise in hydrogen plant operations.') }}</textarea></div></div></div>
                </div>

                <div class="form-card" id="sec-expertise">
                    <div class="form-card-head"><div class="form-accent indigo"></div><div class="form-head-inner"><div class="form-icon indigo"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-expertise-and-plant-focus-svg"></use></svg></div><div><div class="form-title">Expertise &amp; Plant Focus</div><div class="form-sub">Plant types, technical areas, and searchable keywords</div></div></div></div>
                    <div class="form-body">
                        <div class="form-row"><div class="form-group"><label class="form-label">Primary Plant Focus <span class="required">*</span></label><div class="checkbox-chip-group" id="plantGroup">@foreach ($plantTypeOptions as $plantTypeId => $plantTypeName)<label class="checkbox-chip"><input type="checkbox" name="plant_type_ids[]" value="{{ $plantTypeId }}" @checked(in_array((string) $plantTypeId, $selectedPlantTypes, true))><span class="checkbox-chip-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>{{ $plantTypeName }}</label>@endforeach</div>@error('plant_type_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror<div class="form-hint">Determines the default plant handbook and AI context.</div></div></div>
                        <div class="form-row"><div class="form-group"><label class="form-label">Technical Expertise Areas</label><div class="tag-input-wrap" id="expertiseTags" data-target="expertiseTagsInput"><input id="expertiseTagsInput" type="hidden" name="expertise_tags" value="{{ old('expertise_tags', $expertiseTags) }}">@forelse ($expertiseTagItems as $tag)<span class="tag-chip">{{ $tag }} <button type="button" aria-label="Remove tag"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-button-class-btn-btn-ghost-style-flex-1-onclick-"></use></svg></button></span>@empty<span class="tag-chip">Primary Reformer <button type="button" aria-label="Remove tag"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-button-class-btn-btn-ghost-style-flex-1-onclick-"></use></svg></button></span>@endforelse<input class="tag-inline-input" type="text" placeholder="Add expertise area..."></div><div class="form-hint">Press Enter to add. Used for expert directory search and AI context.</div></div></div>
                        <div class="form-row"><div class="form-group"><label class="form-label">Searchable Keywords</label><div class="tag-input-wrap" id="keywordTags" data-target="keywordTagsInput"><input id="keywordTagsInput" type="hidden" name="searchable_keywords" value="{{ old('searchable_keywords', $searchableKeywords) }}">@forelse ($keywordItems as $keyword)<span class="tag-chip" style="background:#F0FDF4;border-color:#BBF7D0;color:#065F46;">{{ $keyword }} <button type="button" aria-label="Remove keyword"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-button-class-btn-btn-ghost-style-flex-1-onclick-"></use></svg></button></span>@empty<span class="tag-chip" style="background:#F0FDF4;border-color:#BBF7D0;color:#065F46;">hydrogen <button type="button" aria-label="Remove keyword"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-button-class-btn-btn-ghost-style-flex-1-onclick-"></use></svg></button></span>@endforelse<input class="tag-inline-input" type="text" placeholder="Add keyword..."></div><div class="form-hint">Keywords improve discoverability in the Expert Directory.</div></div></div>
                        <div class="form-row"><div class="form-group"><label class="form-label">Expertise Rank <span class="required">*</span></label><div class="radio-group" id="levelGroup">@foreach (['Registered Member' => '(Unverified)', 'Industry Professional' => '0-7 yrs', 'Experienced Professional' => '8-15 yrs', 'Senior Industry Expert' => '15+ yrs'] as $label => $meta)<label class="radio-chip {{ $rankLabel === $label ? 'selected' : '' }}"><span class="chip-dot"></span>{{ $label }}<span style="font-size:11px;color:var(--ink-faint);margin-left:4px;">{{ $meta }}</span></label>@endforeach</div><div class="form-hint">Assigned during verification based on years of experience. Affects self-assessment ceiling in Top Expertise Areas.</div></div></div>
                    </div>
                </div>
                <div class="form-card" id="sec-topexpertise"><div class="form-card-head"><div class="form-accent" style="background:linear-gradient(180deg,#F59E0B,#EF4444);width:4px;align-self:stretch;flex-shrink:0;border-radius:0;"></div><div class="form-head-inner"><div class="form-icon" style="background:#FFFBEB;color:#B45309;"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-top-expertise-areas-svg-viewbox-0"></use></svg></div><div><div class="form-title">Top Expertise Areas</div><div class="form-sub">Up to 5 technical areas - user self-rates within the ceiling set by their Expertise Rank</div></div></div></div><div class="form-body"><input id="topAreasInput" type="hidden" name="industry_specialization" value="{{ old('industry_specialization', $industrySpecialization) }}"><div id="rankCeilingBanner" style="display:flex;align-items:center;gap:10px;padding:11px 14px;background:linear-gradient(135deg,#FFFBEB,#FEF3C7);border:1px solid rgba(245,158,11,0.3);border-radius:11px;margin-bottom:18px;"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-top-expertise-areas-svg-viewbox-0"></use></svg><div style="flex:1;"><span style="font-size:12.5px;font-weight:700;color:#92400E;">{{ $rankLabel }}</span><span style="font-size:12.5px;font-weight:500;color:#B45309;"> - self-assessment ceiling: </span><span style="font-size:12.5px;font-weight:800;color:#92400E;" id="rankCeilingValue">{{ $rankCeiling }}%</span><span style="font-size:12px;color:#B45309;font-weight:500;"> per area (100% unlocked only by passing that area's quiz)</span></div></div><div id="expertiseAreasList" style="display:flex;flex-direction:column;gap:14px;">@foreach ($topAreaRows as $areaRow)<div class="expertise-area-row" data-domain-id="{{ $areaRow['domain_id'] }}" data-plant-type-id="{{ $areaRow['plant_type_id'] }}" data-quiz-passed="{{ $areaRow['quiz_passed'] ? 'true' : 'false' }}" data-is-quiz-unlocked="{{ $areaRow['is_quiz_unlocked'] ? 'true' : 'false' }}"><div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;"><select class="form-select js-top-area-select" style="flex:1;" data-current-value="{{ e($areaRow['name']) }}"><option value="">- Select section -</option>@if ($areaRow['name'] !== '')<option value="{{ e($areaRow['name']) }}" data-domain-id="{{ $areaRow['domain_id'] }}" data-plant-type-id="{{ $areaRow['plant_type_id'] }}" data-quiz-passed="{{ $areaRow['quiz_passed'] ? 'true' : 'false' }}" data-is-quiz-unlocked="{{ $areaRow['is_quiz_unlocked'] ? 'true' : 'false' }}" selected>{{ $areaRow['name'] }}</option>@endif</select><span class="quiz-badge" style="font-size:12px;font-weight:700;padding:4px 10px;border-radius:20px;white-space:nowrap;background:#F1F5F9;color:var(--ink-faint);border:1px solid var(--border);">Quiz not taken</span><button type="button" class="js-remove-area" style="width:28px;height:28px;border-radius:8px;border:1px solid var(--border);background:#fff;color:var(--ink-faint);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-button-class-btn-btn-ghost-style-flex-1-onclick-"></use></svg></button></div><div style="display:flex;align-items:center;gap:12px;"><span class="js-slider-value" style="font-size:12px;font-weight:600;color:var(--ink-faint);width:28px;text-align:right;">{{ $areaRow['value'] }}%</span><div style="flex:1;position:relative;padding-top:26px;"><input type="range" min="0" max="100" value="{{ $areaRow['value'] }}" data-ceiling="{{ $rankCeiling }}" class="expertise-slider" style="width:100%;"><div class="slider-ceiling-marker" style="left:{{ $rankCeiling }}%;" title="Rank ceiling: {{ $rankCeiling }}%"></div></div><span style="font-size:12px;color:var(--ink-faint);width:80px;font-weight:500;">Max: {{ $rankCeiling }}%</span></div></div>@endforeach</div><div style="margin-top:16px;"><button id="addAreaBtn" type="button" style="display:flex;align-items:center;gap:7px;padding:9px 16px;border:1.5px dashed #93C5FD;border-radius:10px;background:#EFF6FF;color:#2563EB;font-size:12.5px;font-weight:600;transition:.15s;width:100%;justify-content:center;"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-add-note"></use></svg>Add Expertise Area <span style="font-size:12px;color:#60A5FA;" id="areaCountLabel">({{ min(5, max(1, $topAreaRows->count())) }} of 5 used)</span></button></div><div class="form-hint" style="margin-top:10px;">Max 5 areas. Options follow the selected Primary Plant Focus. Stored field remains the existing industry specialization list.</div></div></div>
                <div class="form-card" id="sec-account"><div class="form-card-head"><div class="form-accent amber"></div><div class="form-head-inner"><div class="form-icon amber"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-penalty-history-3-actions-recorded"></use></svg></div><div><div class="form-title">Account &amp; Role</div><div class="form-sub">Account type, status, and platform access level</div></div></div></div><div class="form-body"><div class="row row-cols-1 row-cols-md-2 g-3"><div class="form-group"><label class="form-label">Account Type <span class="required">*</span></label><select class="form-select @error('account_type') is-invalid @enderror" name="account_type"><option value="member" @selected(! $isProfessional)>Unverified Member</option><option value="professional" @selected($isProfessional)>Verified Professional</option></select>@error('account_type')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-hint">Changing this affects library, AI, and messaging access.</div></div><div class="form-group"><label class="form-label">Account Status <span class="required">*</span></label><select class="form-select @error('status') is-invalid @enderror" name="status"><option value="active" @selected(old('status', $user->status) === 'active')>Active</option><option value="suspended" @selected(old('status', $user->status) === 'suspended')>Suspended</option><option value="frozen" @selected(old('status', $user->status) === 'frozen')>Frozen</option></select>@error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div><div class="row row-cols-1 row-cols-md-3 g-3"><div class="form-group"><label class="form-label">Registration Date</label><input class="form-input" type="text" value="{{ $user->created_at?->format('d M Y') ?? '-' }}" disabled></div><div class="form-group"><label class="form-label">Last Login</label><input class="form-input" type="text" value="{{ $user->last_login_at?->format('d M Y H:i') ?? 'Never' }}" disabled></div><div class="form-group"><label class="form-label">User ID</label><input class="form-input" type="text" value="#{{ str_pad((string) $user->id, 5, '0', STR_PAD_LEFT) }}" disabled></div></div></div></div>

                <div class="form-card" id="sec-verification"><div class="form-card-head"><div class="form-accent cyan"></div><div class="form-head-inner"><div class="form-icon cyan"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-profile-verification-queue-5-svg"></use></svg></div><div><div class="form-title">Verification Status</div><div class="form-sub">Professional identity verification and renewal cycle</div></div></div></div><div class="form-body"><div class="verif-status-block verified"><div class="verif-icon-lg"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-profile-verification-queue-5-svg"></use></svg></div><div class="verif-status-text"><div class="verif-status-label">{{ $user->is_verified ? 'Verified - Professional' : 'Pending - Registered Member' }}</div><div class="verif-status-meta">Verified {{ $user->verified_at?->format('d M Y') ?? 'not yet' }} - Renewal due {{ $user->verification_expires_at?->format('d M Y') ?? 'not scheduled' }}</div></div><button class="verif-action-btn renew" type="button">Renew Now</button></div><div class="row row-cols-1 row-cols-md-2 g-3"><div class="form-group"><label class="form-label">Verification Method</label><select class="form-select" disabled><option @selected($verificationMethod === 'work_email_linkedin')>Work Email + LinkedIn</option><option @selected($verificationMethod === 'company_letter')>Company Letter</option><option @selected($verificationMethod === 'manual_admin_review')>Manual Admin Review</option></select></div><div class="form-group"><label class="form-label">Renewal Cycle</label><select class="form-select"><option>12 months (standard)</option><option>24 months (extended)</option><option>Permanent (admin override)</option></select></div></div><div class="form-row"><div class="form-group"><label class="form-label">Verification Notes <span class="hint">(internal only)</span></label><textarea class="form-textarea">{{ $latestVerificationRequest->admin_notes ?? $latestVerificationRequest->notes ?? 'TODO-safe static note until editable verification note contract is supplied.' }}</textarea></div></div></div></div>

                <div class="form-card" id="sec-privacy"><div class="form-card-head"><div class="form-accent rose"></div><div class="form-head-inner"><div class="form-icon rose"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-account-penalty-and-freeze-3"></use></svg></div><div><div class="form-title">Privacy &amp; Visibility</div><div class="form-sub">Profile discoverability and data preferences</div></div></div></div><div class="form-body"><div class="form-row"><div class="form-group"><label class="form-label">Profile Visibility Settings</label><div class="switch-row"><div class="switch-info"><div class="sw-label">Appear in Expert Directory</div><div class="sw-desc">Profile is searchable by other professionals by name, company, or expertise</div></div><label class="switch"><input type="checkbox" name="is_discoverable" value="1" @checked(old('is_discoverable', $profile->is_discoverable ?? true))><span class="slider"></span></label></div><div class="switch-row"><div class="switch-info"><div class="sw-label">Show on Public Leaderboard</div><div class="sw-desc">Name and score appear on the platform's top contributor leaderboard</div></div><label class="switch"><input type="checkbox" checked><span class="slider"></span></label></div></div></div></div></div>

                <div class="form-card" id="sec-audit"><div class="form-card-head"><div class="form-accent indigo"></div><div class="form-head-inner"><div class="form-icon indigo"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-ai-usage-monitor-charley-calculator"></use></svg></div><div><div class="form-title">Edit Audit Log</div><div class="form-sub">Recent changes made to this profile by admins</div></div></div></div><div class="form-body"><div class="audit-row"><div class="audit-dot blue"></div><div class="audit-text">Profile edit screen opened by Super Admin</div><div class="audit-time">{{ now()->format('d M Y') }} - System</div></div><div class="audit-row"><div class="audit-dot green"></div><div class="audit-text">Profile created, initial role set to {{ str_replace('_', ' ', $user->role) }}</div><div class="audit-time">{{ $user->created_at?->format('d M Y') ?? 'Unknown' }} - System</div></div></div></div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    const knowledgeDomains = {{ \Illuminate\Support\Js::from($domainPayload) }};
    const markDirty = () => document.getElementById('unsavedPill')?.classList.add('show');

    document.querySelectorAll('.edit-nav-item[data-section]').forEach((item) => {
        item.addEventListener('click', () => {
            const target = document.getElementById(item.dataset.section);
            if (! target) return;
            document.querySelectorAll('.edit-nav-item').forEach((nav) => nav.classList.remove('active'));
            item.classList.add('active');
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    const syncTagInput = (wrap) => {
        const target = document.getElementById(wrap.dataset.target);
        if (! target) return;
        target.value = Array.from(wrap.querySelectorAll('.tag-chip')).map((chip) => chip.childNodes[0]?.textContent.trim()).filter(Boolean).join(', ');
    };

    document.querySelectorAll('.tag-input-wrap').forEach((wrap) => {
        const input = wrap.querySelector('.tag-inline-input');
        wrap.addEventListener('click', () => input?.focus());
        wrap.addEventListener('click', (event) => {
            const button = event.target.closest('.tag-chip button');
            if (! button) return;
            button.closest('.tag-chip')?.remove();
            syncTagInput(wrap);
            markDirty();
        });
        input?.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            const value = input.value.trim();
            if (! value) return;
            const chip = document.createElement('span');
            chip.className = 'tag-chip';
            if (wrap.id === 'keywordTags') {
                chip.style.background = '#F0FDF4';
                chip.style.borderColor = '#BBF7D0';
                chip.style.color = '#065F46';
            }
            chip.append(document.createTextNode(value + ' '));
            const button = document.createElement('button');
            button.type = 'button';
            button.setAttribute('aria-label', 'Remove tag');
            button.innerHTML = '<svg class="icon"><use href="/assets/icons/sprite.svg#icon-button-class-btn-btn-ghost-style-flex-1-onclick-"></use></svg>';
            chip.append(button);
            wrap.insertBefore(chip, input);
            input.value = '';
            syncTagInput(wrap);
            markDirty();
        });
        syncTagInput(wrap);
    });

    // Plant focus filters knowledge-domain options, while rank and per-domain quiz unlocks control each slider ceiling.
    const getSelectedPlantIds = () => Array.from(document.querySelectorAll('#plantGroup input[name="plant_type_ids[]"]:checked')).map((input) => String(input.value));

    const isDomainValidForPlants = (domain, plantIds = getSelectedPlantIds()) => {
        if (! domain) return false;
        if (! domain.plant_type_id) return true;
        return plantIds.length === 0 || plantIds.includes(String(domain.plant_type_id));
    };

    const getAvailableDomains = () => {
        const plantIds = getSelectedPlantIds();
        return knowledgeDomains.filter((domain) => isDomainValidForPlants(domain, plantIds));
    };

    const findDomainByName = (name) => knowledgeDomains.find((domain) => domain.name === name) || null;
    const findDomainById = (id) => knowledgeDomains.find((domain) => String(domain.id) === String(id)) || null;

    const setOptionDataset = (option, domain) => {
        option.dataset.domainId = domain?.id ?? '';
        option.dataset.plantTypeId = domain?.plant_type_id ?? '';
        option.dataset.quizPassed = domain?.quiz_passed ? 'true' : 'false';
        option.dataset.isQuizUnlocked = domain?.is_quiz_unlocked ? 'true' : 'false';
    };

    const applyRowState = (row, state, domain = null) => {
        const badge = row.querySelector('.quiz-badge');
        row.dataset.domainId = domain?.id ?? '';
        row.dataset.plantTypeId = domain?.plant_type_id ?? '';
        row.dataset.quizPassed = domain?.quiz_passed ? 'true' : 'false';
        row.dataset.isQuizUnlocked = domain?.is_quiz_unlocked ? 'true' : 'false';
        row.dataset.invalidDomain = state === 'invalid' ? 'true' : 'false';
        if (! badge) return;

        if (state === 'invalid') {
            badge.textContent = 'Invalid for plant focus';
            badge.style.background = '#FEF2F2';
            badge.style.color = '#B91C1C';
            badge.style.borderColor = '#FECACA';
            return;
        }

        if (domain?.quiz_passed || domain?.is_quiz_unlocked) {
            badge.textContent = 'Quiz unlocked';
            badge.style.background = '#ECFDF5';
            badge.style.color = '#047857';
            badge.style.borderColor = '#A7F3D0';
            return;
        }

        badge.textContent = 'Quiz not taken';
        badge.style.background = '#F1F5F9';
        badge.style.color = 'var(--ink-faint)';
        badge.style.borderColor = 'var(--border)';
    };

    const refreshDomainSelect = (select) => {
        const row = select.closest('.expertise-area-row');
        const currentValue = select.value || select.dataset.currentValue || '';
        const currentDomain = findDomainById(row?.dataset.domainId) || findDomainByName(currentValue);
        const availableDomains = getAvailableDomains();
        const validCurrent = currentValue === '' || availableDomains.some((domain) => domain.name === currentValue);
        select.innerHTML = '';

        const placeholder = new Option('- Select section -', '');
        select.append(placeholder);
        availableDomains.forEach((domain) => {
            const option = new Option(domain.name, domain.name);
            setOptionDataset(option, domain);
            select.append(option);
        });

        if (currentValue && ! validCurrent) {
            const legacy = new Option(currentValue + ' (not valid for selected plant focus)', currentValue, true, true);
            legacy.dataset.invalidDomain = 'true';
            setOptionDataset(legacy, currentDomain);
            select.append(legacy);
            select.value = currentValue;
            applyRowState(row, 'invalid', currentDomain);
        } else {
            select.value = currentValue;
            const selectedDomain = findDomainByName(select.value);
            applyRowState(row, select.value ? 'valid' : 'empty', selectedDomain);
        }

        select.dataset.currentValue = select.value;
    };

    const refreshAllDomainSelects = () => {
        document.querySelectorAll('.js-top-area-select').forEach(refreshDomainSelect);
    };

    const getExperienceRank = (value) => {
        const normalized = String(value ?? '').trim();
        if (normalized === '') return { label: 'Registered Member', ceiling: 0 };

        const years = Number(normalized);
        if (! Number.isFinite(years) || years < 0) return { label: 'Registered Member', ceiling: 0 };
        if (years >= 15) return { label: 'Senior Industry Expert', ceiling: 70 };
        if (years >= 8) return { label: 'Experienced Professional', ceiling: 50 };
        return { label: 'Industry Professional', ceiling: 30 };
    };

    const toBoolean = (value) => ['1', 'true', 'yes', 'passed', 'unlocked'].includes(String(value ?? '').toLowerCase());

    const isAreaUnlocked = (row) => {
        const select = row.querySelector('.js-top-area-select');
        const option = select?.selectedOptions?.[0];
        return toBoolean(row.dataset.quizPassed)
            || toBoolean(row.dataset.isQuizUnlocked)
            || toBoolean(option?.dataset.quizPassed)
            || toBoolean(option?.dataset.isQuizUnlocked);
    };

    const getRowMax = (row, rankCeiling) => isAreaUnlocked(row) ? 100 : rankCeiling;

    const syncSliderRow = (row, rankCeiling, clampValue = true) => {
        const slider = row?.querySelector('.expertise-slider');
        const value = row?.querySelector('.js-slider-value');
        const maxLabel = slider?.closest('div')?.nextElementSibling;
        const marker = row?.querySelector('.slider-ceiling-marker');
        if (! slider) return;

        const rowMax = getRowMax(row, rankCeiling);
        if (clampValue && Number(slider.value) > rowMax) slider.value = rowMax;
        slider.max = 100;
        slider.dataset.ceiling = String(rowMax);

        if (value) value.textContent = slider.value + '%';
        if (maxLabel) maxLabel.textContent = 'Max: ' + rowMax + '%';
        if (marker) {
            marker.style.left = rowMax + '%';
            marker.title = (rowMax === 100 ? 'Quiz unlocked ceiling: ' : 'Rank ceiling: ') + rowMax + '%';
        }
    };

    const syncTopAreas = () => {
        const target = document.getElementById('topAreasInput');
        if (! target) return;
        target.value = Array.from(document.querySelectorAll('.js-top-area-select')).map((select) => select.value.trim()).filter(Boolean).join(', ');
        const label = document.getElementById('areaCountLabel');
        if (label) label.textContent = '(' + document.querySelectorAll('.expertise-area-row').length + ' of 5 used)';
    };

    const syncRankUi = (markAsDirty = false) => {
        const input = document.querySelector('[name="experience_years"]');
        const rank = getExperienceRank(input?.value);
        const banner = document.getElementById('rankCeilingBanner');
        const bannerRank = banner?.querySelector('span:first-child');
        const bannerValue = document.getElementById('rankCeilingValue');

        document.querySelectorAll('#levelGroup .radio-chip').forEach((chip) => {
            chip.classList.toggle('selected', chip.textContent.includes(rank.label));
        });

        if (bannerRank) bannerRank.textContent = rank.label;
        document.querySelector('.edit-title-badges .badge.senior')?.replaceChildren(document.createTextNode(rank.label));
        if (bannerValue) bannerValue.textContent = rank.ceiling + '%';
        document.querySelectorAll('.expertise-area-row').forEach((row) => syncSliderRow(row, rank.ceiling));
        syncTopAreas();
        if (markAsDirty) markDirty();
    };

    document.querySelector('[name="experience_years"]')?.addEventListener('input', () => syncRankUi(true));
    document.querySelector('[name="experience_years"]')?.addEventListener('change', () => syncRankUi(true));

    document.getElementById('plantGroup')?.addEventListener('change', (event) => {
        if (! event.target.matches('input[name="plant_type_ids[]"]')) return;
        refreshAllDomainSelects();
        syncRankUi(true);
    });

    document.getElementById('expertiseAreasList')?.addEventListener('input', (event) => {
        if (event.target.matches('.expertise-slider')) {
            const rank = getExperienceRank(document.querySelector('[name="experience_years"]')?.value);
            syncSliderRow(event.target.closest('.expertise-area-row'), rank.ceiling);
        }
        syncTopAreas();
        markDirty();
    });

    document.getElementById('expertiseAreasList')?.addEventListener('change', (event) => {
        if (event.target.matches('.js-top-area-select')) {
            const select = event.target;
            const row = select.closest('.expertise-area-row');
            const domain = findDomainByName(select.value);
            select.dataset.currentValue = select.value;
            applyRowState(row, select.selectedOptions[0]?.dataset.invalidDomain === 'true' ? 'invalid' : (select.value ? 'valid' : 'empty'), domain);
            const rank = getExperienceRank(document.querySelector('[name="experience_years"]')?.value);
            syncSliderRow(row, rank.ceiling);
        }
        syncTopAreas();
        markDirty();
    });

    document.getElementById('expertiseAreasList')?.addEventListener('click', (event) => {
        const button = event.target.closest('.js-remove-area');
        if (! button) return;
        button.closest('.expertise-area-row')?.remove();
        syncTopAreas();
        markDirty();
    });

    document.getElementById('addAreaBtn')?.addEventListener('click', () => {
        const list = document.getElementById('expertiseAreasList');
        const first = list?.querySelector('.expertise-area-row');
        if (! list || ! first || list.querySelectorAll('.expertise-area-row').length >= 5) return;
        const clone = first.cloneNode(true);
        const select = clone.querySelector('.js-top-area-select');
        const slider = clone.querySelector('.expertise-slider');
        const value = clone.querySelector('.js-slider-value');
        clone.dataset.domainId = '';
        clone.dataset.plantTypeId = '';
        clone.dataset.quizPassed = 'false';
        clone.dataset.isQuizUnlocked = 'false';
        clone.dataset.invalidDomain = 'false';
        if (select) {
            select.dataset.currentValue = '';
            select.value = '';
            refreshDomainSelect(select);
        }
        if (slider) slider.value = 0;
        if (value) value.textContent = '0%';
        list.appendChild(clone);
        syncRankUi();
        syncTopAreas();
        markDirty();
    });

    document.getElementById('editEngineerForm')?.addEventListener('input', markDirty);
    document.getElementById('editEngineerForm')?.addEventListener('change', markDirty);
    refreshAllDomainSelects();
    syncRankUi();
    syncTopAreas();
</script>
@endpush

