@extends('layouts.rebuild-dashboard')

@section('title', 'Create New Partner')

@php
    $selectedTierId = (string) old('subscription_tier_id', optional($subscriptionTiers->first())->id);
    $defaultStartDate = old('subscription_starts_at', now()->toDateString());
    $visualTierClasses = ['diamond', 'gold', 'platinum'];
    $tierPermissionValue = function ($tierPermission) {
        $value = $tierPermission?->value;

        if (is_array($value) && array_key_exists('value', $value)) {
            return $value['value'];
        }

        return $value;
    };
    $permissionEnabled = function ($tierPermission) use ($tierPermissionValue): bool {
        if (! $tierPermission) {
            return false;
        }

        $value = $tierPermissionValue($tierPermission);
        $valueType = $tierPermission->permission?->value_type;

        if ($valueType === 'boolean') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if ($valueType === 'integer') {
            return (int) $value !== 0;
        }

        if (is_array($value)) {
            return ! empty(array_filter($value, fn ($item) => filled($item)));
        }

        return filled($value);
    };
    $permissionValueLabel = function ($tierPermission) use ($tierPermissionValue, $permissionEnabled): string {
        if (! $tierPermission) {
            return 'Not included';
        }

        $permission = $tierPermission->permission;
        $value = $tierPermissionValue($tierPermission);

        if ($permission?->value_type === 'boolean') {
            return $permissionEnabled($tierPermission) ? 'Included' : 'Not included';
        }

        if ($permission?->value_type === 'integer') {
            return (int) $value === -1 ? 'Unlimited' : number_format((int) $value);
        }

        if (is_array($value)) {
            return collect($value)->map(fn ($item, $key) => is_string($key) ? str($key)->headline().': '.$item : $item)->implode(', ');
        }

        return filled($value) ? (string) $value : 'Not included';
    };
    $tierPermissionSummaries = $subscriptionTiers->mapWithKeys(fn ($tier) => [
        $tier->id => $tier->tierPermissions
            ->filter(fn ($tierPermission) => $tierPermission->permission)
            ->sortBy(fn ($tierPermission) => $tierPermission->permission->name)
            ->values(),
    ]);
    $permissionRows = $tierPermissionSummaries
        ->flatMap(fn ($items) => $items)
        ->map(fn ($tierPermission) => $tierPermission->permission)
        ->filter()
        ->unique('id')
        ->sortBy('name')
        ->values();
    $oldKeywordValue = old('keywords');
    $oldKeywords = collect(is_array($oldKeywordValue) ? $oldKeywordValue : json_decode((string) $oldKeywordValue, true))
        ->whenEmpty(fn ($items) => collect(explode(',', (string) $oldKeywordValue)))
        ->map(fn ($keyword) => trim((string) $keyword))
        ->filter()
        ->unique()
        ->values();
@endphp

@section('content')
    <div class="page-head">
        <div>
            <div class="page-title">Create New Partner</div>
            <div class="page-sub">Register a new company and assign a subscription tier. All fields marked <span class="req">*</span> are required.</div>
        </div>
        <div class="page-head-actions">
            <a class="btn-secondary" href="{{ route('admin.dashboard.iam.users.partners') }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-back-to-account-penalty-and"></use></svg>
                Back to Partners
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger"><strong>Please review the highlighted fields.</strong></div>
    @endif

    <form id="createPartnerForm" method="POST" action="{{ route('admin.dashboard.iam.users.store-partner') }}" enctype="multipart/form-data">
        @csrf

        <!-- STEP INDICATOR -->
        <div class="steps">
            <div class="step active"><div class="step-circle">1</div><div class="step-label">Select Tier</div></div>
            <div class="step-connector" id="conn1"></div>
            <div class="step active" id="step2el"><div class="step-circle" id="step2circle">2</div><div class="step-label">Company Info</div></div>
            <div class="step-connector" id="conn2"></div>
            <div class="step pending" id="step3el"><div class="step-circle" id="step3circle">3</div><div class="step-label">Contact &amp; Access</div></div>
            <div class="step-connector" id="conn3"></div>
            <div class="step pending" id="step4el"><div class="step-circle" id="step4circle">4</div><div class="step-label">Subscription</div></div>
        </div>

        <!-- STEP 1: TIER SELECTION -->
        <div class="mb-2">
            <div class="form-label mb-3">Select Partner Tier <span class="req">*</span></div>
            <div class="row row-cols-1 row-cols-md-3 g-3 tier-choice-row">
                @forelse ($subscriptionTiers as $tierIndex => $tier)
                    @php
                        $visualClass = $visualTierClasses[$tierIndex % count($visualTierClasses)];
                        $isSelected = (string) $tier->id === $selectedTierId;
                        $price = number_format((float) $tier->monthly_price, 2);
                        $featureItems = ($tierPermissionSummaries[$tier->id] ?? collect())
                            ->map(fn ($tierPermission) => $tierPermission->permission->name.': '.$permissionValueLabel($tierPermission))
                            ->values();
                    @endphp
                    <div class="col">
                        <label class="tier-card tier-card-radio tier-card-{{ $visualClass }}" data-tier-code="{{ $visualClass }}">
                            <input class="tier-radio-input" type="radio" name="subscription_tier_id" value="{{ $tier->id }}" required @checked($isSelected)>
                            <div class="tier-check"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-diamond-diamond-partner-licensors"></use></svg></div>
                            <div class="tier-badge {{ $visualClass }}">{{ $tier->display_name }}</div>
                            <div class="tier-name">{{ $tier->display_name }}</div>
                            <div class="tier-desc">{{ $tier->description ?: 'Subscription tier managed by Module 04.' }}</div>
                            <div class="tier-price">{{ $price }} / {{ ucfirst(str_replace('_', ' ', $tier->billing_cycle)) }}</div>
                            <div class="tier-features">
                                @foreach ($featureItems as $feature)
                                    <div class="tier-feat yes"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-diamond-diamond-partner-licensors"></use></svg>{{ $feature }}</div>
                                @endforeach
                            </div>
                        </label>
                    </div>
                @empty
                    <div class="col"><div class="tier-card"><div class="tier-name">No active subscription tiers</div><div class="tier-desc">Create an active tier before adding a partner account.</div></div></div>
                @endforelse
            </div>
            @error('subscription_tier_id')<div class="field-hint">{{ $message }}</div>@enderror
        </div>

        <!-- PERMISSION MATRIX -->
        <div class="form-card" style="margin-bottom:28px;">
            <div class="form-card-header">
                <div class="form-card-icon indigo"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-tier-permission-comparison-selected-tier"></use></svg></div>
                <div><div class="form-card-title">Tier Permission Comparison</div><div class="form-card-sub">Selected tier is highlighted. Permissions are enforced automatically upon activation.</div></div>
            </div>
            <div class="form-card-body" style="overflow-x:auto;">
                <table class="perm-table">
                    <thead>
                        <tr>
                            <th style="width:46%;">Permission</th>
                            @foreach ($subscriptionTiers as $tierIndex => $tier)
                                @php
                                    $visualClass = $visualTierClasses[$tierIndex % count($visualTierClasses)];
                                    $tierColor = match ($visualClass) {
                                        'diamond' => '#1D4ED8',
                                        'gold' => '#92400E',
                                        default => '#6D28D9',
                                    };
                                    $tierIcon = match ($visualClass) {
                                        'diamond' => 'Diamond',
                                        'gold' => 'Gold',
                                        default => 'Platinum',
                                    };
                                @endphp
                                <th id="col-{{ $visualClass }}" data-tier-column="{{ $tier->id }}" style="color:{{ $tierColor }};">{{ $tierIcon }} {{ $tier->display_name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($permissionRows as $permission)
                            <tr>
                                <td>{{ $permission->name }}</td>
                                @foreach ($subscriptionTiers as $tier)
                                    @php
                                        $tierPermission = $tier->tierPermissions->firstWhere('permission_id', $permission->id);
                                        $enabled = $permissionEnabled($tierPermission);
                                        $valueLabel = $permissionValueLabel($tierPermission);
                                    @endphp
                                    <td data-tier-column="{{ $tier->id }}"><span class="{{ $enabled ? 'perm-yes' : 'perm-no' }}" title="{{ $valueLabel }}"><svg class="icon"><use href="/assets/icons/sprite.svg#{{ $enabled ? 'icon-diamond-diamond-partner-licensors' : 'icon-change-password-choose-a-strong' }}"></use></svg></span></td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td>No Module 04 permission records configured</td>@foreach ($subscriptionTiers as $tier)<td><span class="perm-no"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-change-password-choose-a-strong"></use></svg></span></td>@endforeach</tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <!-- STEP 2: COMPANY INFORMATION -->
        <div class="form-card">
            <div class="form-card-header">
                <div class="form-card-icon blue"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-partner-management-path-d-m9-12"></use></svg></div>
                <div><div class="form-card-title">Company Information</div><div class="form-card-sub">Core details about the partner organization</div></div>
            </div>
            <div class="form-card-body">
                <div class="row row-cols-1 row-cols-md-2 g-3">
                    <div class="col"><div class="field field-full"><label>Company Name <span class="req">*</span></label><input type="text" name="company_name" required value="{{ old('company_name') }}" placeholder="e.g. Synvex Catalysts GmbH"></div></div>
                    <div class="col"><div class="field"><label>Company Type <span class="req">*</span></label><select name="company_type"><option value="">Select company type...</option><option>Licensor</option><option>Catalyst Supplier</option><option>Vendor / Equipment Supplier</option><option>Consulting Company</option><option>Engineering Company</option><option>Manufacturing Facility</option><option>Technology Provider</option><option>Service Provider</option></select></div></div>
                    <div class="col"><div class="field"><label>Industry Segment <span class="req">*</span></label><select name="industry_segment"><option value="">Select segment...</option><option>Ammonia</option><option>Methanol</option><option>Hydrogen</option><option>SNG</option><option>GTL</option><option>Multi-segment</option></select></div></div>
                    <div class="col"><div class="field"><label>Country <span class="req">*</span></label><input type="text" name="country" value="{{ old('country') }}" placeholder="Country"></div></div>
                    <div class="col"><div class="field"><label>City / Region</label><input type="text" name="city" value="{{ old('city') }}" placeholder="e.g. Frankfurt"></div></div>
                    <div class="col"><div class="field"><label>Company Website</label><input type="url" name="website" value="{{ old('website') }}" placeholder="https://www.example.com"></div></div>
                    <div class="col"><div class="field"><label>LinkedIn Company Page</label><input type="url" name="linkedin_company_page" value="{{ old('linkedin_company_page') }}" placeholder="https://linkedin.com/company/..."></div></div>
                    <div class="col"><div class="field field-full"><label>Company Overview <span class="req">*</span></label><textarea name="company_overview" placeholder="Briefly describe the company, its focus, industry role, and key offerings.">{{ old('company_overview') }}</textarea><div class="field-hint">This appears on the public partner profile page.</div></div></div>
                    <div class="col"><div class="field field-full"><label>Products &amp; Services Summary</label><textarea name="products" placeholder="List key products, technologies, or services offered.">{{ old('products') }}</textarea></div></div>
                </div>
            </div>
        </div>

        <!-- LOGO UPLOAD -->
        <div class="form-card">
            <div class="form-card-header">
                <div class="form-card-icon amber"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-company-logo-and-branding-displayed"></use></svg></div>
                <div><div class="form-card-title">Company Logo &amp; Branding</div><div class="form-card-sub">Displayed on the partner profile, announcements, and content cards</div></div>
            </div>
            <div class="form-card-body">
                <div class="row row-cols-1 row-cols-md-2 g-3">
                    <div class="col"><div class="field"><label>Company Logo</label><div class="upload-zone" id="logoUploadZone" onclick="document.getElementById('logoInput').click()"><div class="upload-icon" id="logoUploadIcon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-click-to-upload-or-drag"></use></svg></div><div class="upload-title" id="logoUploadTitle">Upload Logo</div><div class="upload-sub" id="logoUploadSub">PNG or SVG recommended. Max 2 MB.</div><span class="upload-btn-label">Choose File</span><input type="file" id="logoInput" name="logo_file" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="hidden-section" onchange="previewLogo(event)"></div><div class="field-hint" id="logoUploadStatus">No logo selected.</div>@error('logo_file')<div class="field-hint">{{ $message }}</div>@enderror</div></div>
                    <div class="col"><div class="field"><label>Cover / Banner Image</label><div class="upload-zone" id="bannerUploadZone" onclick="document.getElementById('bannerInput').click()"><div class="upload-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-company-logo-and-branding-displayed"></use></svg></div><div class="upload-title" id="bannerUploadTitle">Upload Banner</div><div class="upload-sub" id="bannerUploadSub">1200x400 px recommended. Max 5 MB.</div><span class="upload-btn-label">Choose File</span><input type="file" id="bannerInput" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="hidden-section" onchange="previewBanner(event)"></div><div class="field-hint" id="bannerUploadStatus">No banner selected.</div>{{-- Banner persistence awaits a confirmed partner profile media field; do not submit raw paths. --}}</div></div>
                </div>
            </div>
        </div>

        <!-- STEP 3: CONTACT & ACCESS -->
        <div class="form-card">
            <div class="form-card-header">
                <div class="form-card-icon emerald"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-user-management-12-svg-viewbox-0"></use></svg></div>
                <div><div class="form-card-title">Primary Contact &amp; Admin Account</div><div class="form-card-sub">This person will manage the partner account and receive platform notifications</div></div>
            </div>
            <div class="form-card-body">
                <div class="row row-cols-1 row-cols-md-2 g-3">
                    <div class="col"><div class="field"><label>First Name <span class="req">*</span></label><input type="text" name="first_name" required value="{{ old('first_name') }}" placeholder="First name"></div></div>
                    <div class="col"><div class="field"><label>Last Name <span class="req">*</span></label><input type="text" name="last_name" required value="{{ old('last_name') }}" placeholder="Last name"></div></div>
                    <div class="col"><div class="field"><label>Job Title <span class="req">*</span></label><input type="text" name="job_title" value="{{ old('job_title') }}" placeholder="e.g. Marketing Manager"></div></div>
                    <div class="col"><div class="field"><label>Work Email <span class="req">*</span></label><input type="email" name="email" required value="{{ old('email') }}" placeholder="contact@company.com"><div class="field-hint">Used as the login email for the partner account.</div></div></div>
                    <div class="col"><div class="field"><label>Phone Number</label><input type="tel" name="phone" value="{{ old('phone') }}" placeholder="+49 69 ..."></div></div>
                    <div class="col"><div class="field"><label>Direct LinkedIn Profile</label><input type="url" name="linkedin_profile" value="{{ old('linkedin_profile') }}" placeholder="https://linkedin.com/in/..."></div></div>
                    <div class="col"><div class="field field-full"><label>Public Contact Email <span class="req">*</span></label><input type="email" name="public_contact_email" value="{{ old('public_contact_email') }}" placeholder="info@company.com"></div></div>
                    <div class="col"><div class="field"><label>Username</label><input type="text" name="username" value="{{ old('username') }}" placeholder="Generated from email when empty"></div></div>
                    <div class="col"><div class="field"><label>Temporary Password</label><input type="text" name="temporary_password" value="{{ old('temporary_password') }}" placeholder="Generated when empty"></div></div>
                </div>
            </div>
        </div>

        <!-- KEYWORDS -->
        <div class="form-card">
            <div class="form-card-header">
                <div class="form-card-icon purple"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-search-keywords-and-specializations-help"></use></svg></div>
                <div><div class="form-card-title">Search Keywords &amp; Specializations</div><div class="form-card-sub">Help professionals find this partner when searching the platform</div></div>
            </div>
            <div class="form-card-body">
                <div class="field"><label>Keywords <span class="req">*</span></label><div class="tag-input-wrap" id="tagWrap" onclick="document.getElementById('tagInput').focus()">@foreach ($oldKeywords as $keyword)<span class="tag-chip keyword" data-keyword-chip>{{ $keyword }}<span class="tag-chip-remove" onclick="removeKeywordTag(event,this)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></span></span>@endforeach<input type="text" id="tagInput" class="tag-input" placeholder="Type a keyword and press Enter..." onkeydown="handleKeywordInput(event)"><input type="hidden" id="keywordsInput" name="keywords" value="{{ $oldKeywords->toJson() }}"></div>@error('keywords')<div class="field-hint">{{ $message }}</div>@enderror<div class="field-hint">Examples: Catalyst, Reformer, CO2 removal, Compressors, Heat exchangers, Simulation tools.</div></div>
                <div class="field"><label>Plant Type Served</label><div class="checkbox-chip-group" id="plantTypes">
                    @forelse ($plantTypeOptions as $plantTypeId => $plantTypeName)
                        <label class="checkbox-chip"><input type="radio" name="plant_type_id" value="{{ $plantTypeId }}" @checked((string) old('plant_type_id') === (string) $plantTypeId)><span class="checkbox-chip-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>{{ $plantTypeName }}</label>
                    @empty
                        <label class="checkbox-chip"><input type="radio" disabled><span class="checkbox-chip-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>No plant type available</label>
                    @endforelse
                </div><div class="field-hint">Partner profile currently stores one primary plant type.</div></div>
            </div>
        </div>

        <!-- STEP 4: SUBSCRIPTION & SETTINGS -->
        <div class="form-card">
            <div class="form-card-header">
                <div class="form-card-icon blue"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-subscription-and-billing"></use></svg></div>
                <div><div class="form-card-title">Subscription &amp; Activation</div><div class="form-card-sub">Configure subscription period and account status</div></div>
            </div>
            <div class="form-card-body">
                <div class="row row-cols-1 row-cols-md-2 g-3">
                    <div class="col"><div class="field"><label>Subscription Start Date <span class="req">*</span></label><input type="date" name="subscription_starts_at" value="{{ $defaultStartDate }}"></div></div>
                    <div class="col"><div class="field"><label>Subscription End Date</label><input type="date" name="subscription_ends_at" value="{{ old('subscription_ends_at') }}"><div class="field-hint">Leave blank to calculate from the selected tier.</div></div></div>
                    <div class="col"><div class="field"><label>Billing Method</label><select name="payment_method"><option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>Bank Transfer</option><option value="manual_invoice" @selected(old('payment_method') === 'manual_invoice')>Manual Invoice</option><option value="other" @selected(old('payment_method') === 'other')>Other</option></select></div></div>
                    <div class="col"><div class="field"><label>Invoice Reference / PO Number</label><input type="text" name="transaction_code" value="{{ old('transaction_code') }}" placeholder="e.g. INV-2026-0047"></div></div>
                    <div class="col"><div class="field"><label>Payment Amount</label><input type="number" name="payment_amount" step="0.01" min="0.01" value="{{ old('payment_amount') }}" placeholder="0.00"></div></div>
                    <div class="col"><div class="field"><label>Payment Status</label><select name="payment_status"><option value="pending" @selected(old('payment_status') === 'pending')>Pending</option><option value="approved" @selected(old('payment_status') === 'approved')>Approved</option><option value="rejected" @selected(old('payment_status') === 'rejected')>Rejected</option><option value="refunded" @selected(old('payment_status') === 'refunded')>Refunded</option></select></div></div>
                    <div class="col"><div class="field field-full"><label>Internal Admin Notes</label><textarea name="subscription_admin_notes" placeholder="Any internal notes about this partner, onboarding context, special arrangements...">{{ old('subscription_admin_notes') }}</textarea><div class="field-hint">Not visible to the partner.</div></div></div>
                </div>
                <div class="switch-row"><div><div class="sw-label">Activate account immediately</div><div class="sw-desc">Partner can log in and access the platform as soon as account is created</div></div><label class="switch"><input type="hidden" name="activate_account" value="0"><input type="checkbox" name="activate_account" value="1" @checked(old('activate_account', '1') === '1')><span class="slider"></span></label></div>
                <div class="switch-row"><div><div class="sw-label">Auto-renew subscription</div><div class="sw-desc">Store renewal preference on the partner subscription.</div></div><label class="switch"><input type="hidden" name="auto_renew" value="0"><input type="checkbox" name="auto_renew" value="1" @checked(old('auto_renew') === '1')><span class="slider"></span></label></div>
                <div class="switch-row"><div><div class="sw-label">Require email verification before first login</div><div class="sw-desc">Primary contact must verify email address before accessing the account.</div></div><label class="switch"><input type="hidden" name="require_email_verification" value="0"><input type="checkbox" name="require_email_verification" value="1" @checked(old('require_email_verification', '1') === '1')><span class="slider"></span></label></div>
                <div class="switch-row"><div><div class="sw-label">Show partner on public directory</div><div class="sw-desc">Partner profile is visible to members through current profile visibility rules</div></div><label class="switch"><input type="checkbox" checked><span class="slider"></span></label></div>
                <div class="info-banner"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-user-k-habib-flagged-for"></use></svg><p>Partner content uploads will require admin approval before publication, regardless of tier.</p></div>
            </div>
        </div>

        <!-- ACTION BAR -->
        <div class="action-bar">
            <div class="action-bar-left">All required fields must be completed before submitting.</div>
            <div class="action-bar-right">
                <button class="btn-secondary" type="button" onclick="saveDraft()"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-save-as-draft-svg-viewbox-0"></use></svg>Save Draft</button>
                <button class="btn-primary" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-user-management-12-svg-viewbox-0"></use></svg>Create Partner Account</button>
            </div>
        </div>
    </form>

    <div class="toast-container" id="partnerToastContainer"></div>
@endsection

@push('scripts')
    <script>
        (function () {
            const page = {
                init() {
                    document.querySelectorAll('input[name="subscription_tier_id"]').forEach((radio) => {
                        radio.addEventListener('change', () => this.selectTier(radio.value));
                    });
                    const selected = document.querySelector('input[name="subscription_tier_id"]:checked');
                    if (selected) this.selectTier(selected.value);
                    this.bindLogoDropZone();
                    this.bindBannerDropZone();
                    this.bindKeywordTags();
                },
                bindLogoDropZone() {
                    const zone = document.getElementById('logoUploadZone');
                    const input = document.getElementById('logoInput');
                    if (!zone || !input) return;

                    ['dragenter', 'dragover'].forEach((eventName) => {
                        zone.addEventListener(eventName, (event) => {
                            event.preventDefault();
                            zone.classList.add('drag-active');
                        });
                    });
                    ['dragleave', 'drop'].forEach((eventName) => {
                        zone.addEventListener(eventName, (event) => {
                            event.preventDefault();
                            zone.classList.remove('drag-active');
                        });
                    });
                    zone.addEventListener('drop', (event) => {
                        const file = event.dataTransfer.files && event.dataTransfer.files[0];
                        if (!file) return;
                        const files = new DataTransfer();
                        files.items.add(file);
                        if (this.previewLogoFile(file)) input.files = files.files;
                    });
                },
                bindBannerDropZone() {
                    const zone = document.getElementById('bannerUploadZone');
                    const input = document.getElementById('bannerInput');
                    if (!zone || !input) return;

                    ['dragenter', 'dragover'].forEach((eventName) => {
                        zone.addEventListener(eventName, (event) => {
                            event.preventDefault();
                            zone.classList.add('drag-active');
                        });
                    });
                    ['dragleave', 'drop'].forEach((eventName) => {
                        zone.addEventListener(eventName, (event) => {
                            event.preventDefault();
                            zone.classList.remove('drag-active');
                        });
                    });
                    zone.addEventListener('drop', (event) => {
                        const file = event.dataTransfer.files && event.dataTransfer.files[0];
                        if (!file) return;
                        const files = new DataTransfer();
                        files.items.add(file);
                        if (this.previewBannerFile(file)) input.files = files.files;
                    });
                },
                bindKeywordTags() {
                    const form = document.getElementById('createPartnerForm');
                    const input = document.getElementById('tagInput');
                    if (form) form.addEventListener('submit', () => this.syncKeywords());
                    if (input) input.addEventListener('blur', () => this.addKeywordTag(input.value));
                    this.syncKeywords();
                },
                addKeywordTag(value) {
                    const input = document.getElementById('tagInput');
                    const wrap = document.getElementById('tagWrap');
                    const keyword = String(value || '').trim().replace(/,$/, '');
                    if (!keyword || !input || !wrap) return;
                    const exists = this.keywordValues().some((item) => item.toLowerCase() === keyword.toLowerCase());
                    input.value = '';
                    if (exists) return;

                    const chip = document.createElement('span');
                    chip.className = 'tag-chip keyword';
                    chip.dataset.keywordChip = '';
                    chip.appendChild(document.createTextNode(keyword));
                    chip.insertAdjacentHTML('beforeend', '<span class="tag-chip-remove" onclick="removeKeywordTag(event,this)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></span>');
                    wrap.insertBefore(chip, input);
                    this.syncKeywords();
                },
                keywordValues() {
                    return Array.from(document.querySelectorAll('[data-keyword-chip]'))
                        .map((chip) => (chip.firstChild ? chip.firstChild.textContent : chip.textContent).trim())
                        .filter(Boolean);
                },
                removeKeywordTag(removeButton) {
                    removeButton.closest('[data-keyword-chip]')?.remove();
                    this.syncKeywords();
                },
                syncKeywords() {
                    const hidden = document.getElementById('keywordsInput');
                    if (hidden) hidden.value = JSON.stringify(this.keywordValues());
                },
                selectTier(tierId) {
                    const radio = document.querySelector('input[name="subscription_tier_id"][value="' + tierId + '"]');
                    if (radio) radio.checked = true;

                    document.querySelectorAll('th[data-tier-column]').forEach((cell) => {
                        const selected = cell.dataset.tierColumn === String(tierId);
                        cell.style.fontSize = selected ? '13px' : '';
                        cell.style.fontWeight = selected ? '800' : '';
                    });
                },
                showToast(message) {
                    const wrap = document.getElementById('partnerToastContainer');
                    if (!wrap) return;
                    const toast = document.createElement('div');
                    toast.className = 'toast';
                    toast.textContent = message;
                    wrap.appendChild(toast);
                    window.setTimeout(() => toast.remove(), 3000);
                }
            };
            window.selectTier = (tierId) => page.selectTier(tierId);
            window.handleKeywordInput = (event) => {
                if (event.key === 'Enter' || event.key === ',') {
                    event.preventDefault();
                    page.addKeywordTag(event.target.value);
                }
            };
            window.removeKeywordTag = (event, el) => {
                event.stopPropagation();
                page.removeKeywordTag(el);
            };
            page.previewLogoFile = (file) => {
                const title = document.getElementById('logoUploadTitle');
                const subtitle = document.getElementById('logoUploadSub');
                const status = document.getElementById('logoUploadStatus');
                const isValidType = file.type.startsWith('image/');
                const isValidSize = file.size <= 2 * 1024 * 1024;

                if (!isValidType || !isValidSize) {
                    if (status) status.textContent = !isValidType ? 'Please select an image file.' : 'Logo must be 2 MB or smaller.';
                    page.showToast(status ? status.textContent : 'Logo file is invalid.');
                    return false;
                }

                if (title) title.textContent = file.name;
                if (subtitle) subtitle.textContent = 'Ready to upload. Max 2 MB.';
                if (status) status.textContent = file.name + ' selected.';
                page.showToast(file.name + ' selected.');
                return true;
            };
            page.previewBannerFile = (file) => {
                const title = document.getElementById('bannerUploadTitle');
                const subtitle = document.getElementById('bannerUploadSub');
                const status = document.getElementById('bannerUploadStatus');
                const isValidType = file.type.startsWith('image/');
                const isValidSize = file.size <= 5 * 1024 * 1024;

                if (!isValidType || !isValidSize) {
                    if (status) status.textContent = !isValidType ? 'Please select an image file.' : 'Banner must be 5 MB or smaller.';
                    page.showToast(status ? status.textContent : 'Banner file is invalid.');
                    return false;
                }

                if (title) title.textContent = file.name;
                if (subtitle) subtitle.textContent = 'Ready for future banner persistence. Max 5 MB.';
                if (status) status.textContent = file.name + ' selected.';
                page.showToast(file.name + ' selected.');
                return true;
            };
            window.previewLogo = (event) => {
                if (event.target.files && event.target.files[0] && !page.previewLogoFile(event.target.files[0])) event.target.value = '';
            };
            window.previewBanner = (event) => {
                if (event.target.files && event.target.files[0] && !page.previewBannerFile(event.target.files[0])) event.target.value = '';
            };
            window.saveDraft = () => page.showToast('Draft saved.');
            document.addEventListener('DOMContentLoaded', () => page.init());
        })();
    </script>
@endpush
