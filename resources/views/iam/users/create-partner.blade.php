@extends('layouts.rebuild-dashboard')

@section('title', 'Create New Partner')


@section('content')
    <div class="page-head">
        <div>
            <div class="page-title">Create New Partner</div>
            <div class="page-sub">Register a new company and assign a subscription tier. All fields marked <span class="req">*</span> are required.</div>
        </div>
        <div class="page-head-actions">
            <a class="btn-secondary" href="{{ route('admin.dashboard.iam.users.partners') }}">
                <x-admin.icon name="back-account-penalty" />
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
                @forelse ($tierCards as $tierCard)
                    <div class="col">
                        <label class="tier-card tier-card-radio tier-card-{{ $tierCard['visualClass'] }}" data-tier-code="{{ $tierCard['visualClass'] }}">
                            <input class="tier-radio-input" type="radio" name="subscription_tier_id" value="{{ $tierCard['id'] }}" required @checked($tierCard['id'] === $selectedTierId)>
                            <div class="tier-check"><x-admin.icon name="diamond-diamond-partner-licensor" /></div>
                            <div class="tier-badge {{ $tierCard['visualClass'] }}">{{ $tierCard['tier']->display_name }}</div>
                            <div class="tier-name">{{ $tierCard['tier']->display_name }}</div>
                            <div class="tier-desc">{{ $tierCard['tier']->description ?: 'Subscription tier managed by Module 04.' }}</div>
                            <div class="tier-price">{{ $tierCard['price'] }} / {{ $tierCard['billingCycleLabel'] }}</div>
                            <div class="tier-features">
                                @foreach ($tierCard['features'] as $feature)
                                    <div class="tier-feat yes"><x-admin.icon name="diamond-diamond-partner-licensor" />{{ $feature }}</div>
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
                <div class="form-card-icon indigo"><x-admin.icon name="tier-permission-comparison-selec" /></div>
                <div><div class="form-card-title">Tier Permission Comparison</div><div class="form-card-sub">Selected tier is highlighted. Permissions are enforced automatically upon activation.</div></div>
            </div>
            <div class="form-card-body" style="overflow-x:auto;">
                <table class="perm-table">
                    <thead>
                        <tr>
                            <th style="width:46%;">Permission</th>
                            @foreach ($permissionHeaders as $permissionHeader)
                                <th id="col-{{ $permissionHeader['visualClass'] }}" data-tier-column="{{ $permissionHeader['id'] }}" style="color:{{ $permissionHeader['color'] }};">{{ $permissionHeader['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($permissionRows as $permissionRow)
                            <tr>
                                <td>{{ $permissionRow['name'] }}</td>
                                @foreach ($permissionRow['cells'] as $permissionCell)
                                    <td data-tier-column="{{ $permissionCell['tier_id'] }}"><span class="{{ $permissionCell['class'] }}" title="{{ $permissionCell['value_label'] }}"><x-admin.icon :name="$permissionCell['icon']" /></span></td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td>No Module 04 permission records configured</td>
                                @foreach ($emptyPermissionCells as $permissionCell)
                                    <td data-tier-column="{{ $permissionCell['tier_id'] }}"><span class="{{ $permissionCell['class'] }}"><x-admin.icon :name="$permissionCell['icon']" /></span></td>
                                @endforeach
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <!-- STEP 2: COMPANY INFORMATION -->
        <div class="form-card">
            <div class="form-card-header">
                <div class="form-card-icon blue"><x-admin.icon name="partners" /></div>
                <div><div class="form-card-title">Company Information</div><div class="form-card-sub">Core details about the partner organization</div></div>
            </div>
            <div class="form-card-body">
                <div class="row row-cols-1 row-cols-md-2 g-3">
                    <div class="col"><x-admin.input field-class="field-full" label="Company Name" name="company_name" :value="old('company_name')" placeholder="e.g. Synvex Catalysts GmbH" required /></div>
                    <div class="col"><x-admin.select label="Company Type" name="company_type" :selected="old('company_type')" placeholder="Select company type..." :options="$companyTypeOptions" required /></div>
                    <div class="col"><x-admin.select label="Industry Segment" name="industry_segment" :selected="old('industry_segment')" placeholder="Select segment..." :options="$industrySegmentOptions" required /></div>
                    <div class="col"><x-admin.input label="Country" name="country" :value="old('country')" placeholder="Country" required /></div>
                    <div class="col"><x-admin.input label="City / Region" name="city" :value="old('city')" placeholder="e.g. Frankfurt" /></div>
                    <div class="col"><x-admin.input type="url" label="Company Website" name="website" :value="old('website')" placeholder="https://www.example.com" /></div>
                    <div class="col"><x-admin.input type="url" label="LinkedIn Company Page" name="linkedin_company_page" :value="old('linkedin_company_page')" placeholder="https://linkedin.com/company/..." /></div>
                    <div class="col"><x-admin.textarea field-class="field-full" label="Company Overview" name="company_overview" :value="old('company_overview')" placeholder="Briefly describe the company, its focus, industry role, and key offerings." hint="This appears on the public partner profile page." required /></div>
                    <div class="col"><x-admin.textarea field-class="field-full" label="Products & Services Summary" name="products" :value="old('products')" placeholder="List key products, technologies, or services offered." /></div>
                </div>
            </div>
        </div>

        <!-- LOGO UPLOAD -->
        <div class="form-card">
            <div class="form-card-header">
                <div class="form-card-icon amber"><x-admin.icon name="image-2" /></div>
                <div><div class="form-card-title">Company Logo &amp; Branding</div><div class="form-card-sub">Displayed on the partner profile, announcements, and content cards</div></div>
            </div>
            <div class="form-card-body">
                <div class="row row-cols-1 row-cols-md-2 g-3">
                    <div class="col"><div class="field"><label>Company Logo</label><div class="upload-zone" id="logoUploadZone" onclick="document.getElementById('logoInput').click()"><div class="upload-icon" id="logoUploadIcon"><x-admin.icon name="click-upload-drag" /></div><div class="upload-title" id="logoUploadTitle">Upload Logo</div><div class="upload-sub" id="logoUploadSub">PNG or SVG recommended. Max 2 MB.</div><span class="upload-btn-label">Choose File</span><input type="file" id="logoInput" name="logo_file" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="hidden-section" onchange="previewLogo(event)"></div><div class="field-hint" id="logoUploadStatus">No logo selected.</div>@error('logo_file')<div class="field-hint">{{ $message }}</div>@enderror</div></div>
                    <div class="col"><div class="field"><label>Cover / Banner Image</label><div class="upload-zone" id="bannerUploadZone" onclick="document.getElementById('bannerInput').click()"><div class="upload-icon"><x-admin.icon name="image-2" /></div><div class="upload-title" id="bannerUploadTitle">Upload Banner</div><div class="upload-sub" id="bannerUploadSub">1200x400 px recommended. Max 5 MB.</div><span class="upload-btn-label">Choose File</span><input type="file" id="bannerInput" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="hidden-section" onchange="previewBanner(event)"></div><div class="field-hint" id="bannerUploadStatus">No banner selected.</div>{{-- Banner persistence awaits a confirmed partner profile media field; do not submit raw paths. --}}</div></div>
                </div>
            </div>
        </div>

        <!-- STEP 3: CONTACT & ACCESS -->
        <div class="form-card">
            <div class="form-card-header">
                <div class="form-card-icon emerald"><x-admin.icon name="users-5" /></div>
                <div><div class="form-card-title">Primary Contact &amp; Admin Account</div><div class="form-card-sub">This person will manage the partner account and receive platform notifications</div></div>
            </div>
            <div class="form-card-body">
                <div class="row row-cols-1 row-cols-md-2 g-3">
                    <div class="col"><x-admin.input label="First Name" name="first_name" :value="old('first_name')" placeholder="First name" required /></div>
                    <div class="col"><x-admin.input label="Last Name" name="last_name" :value="old('last_name')" placeholder="Last name" required /></div>
                    <div class="col"><x-admin.input label="Job Title" name="job_title" :value="old('job_title')" placeholder="e.g. Marketing Manager" required /></div>
                    <div class="col"><x-admin.input type="email" label="Work Email" name="email" :value="old('email')" placeholder="contact@company.com" hint="Used as the login email for the partner account." required /></div>
                    <div class="col"><x-admin.input type="tel" label="Phone Number" name="phone" :value="old('phone')" placeholder="+49 69 ..." /></div>
                    <div class="col"><x-admin.input type="url" label="Direct LinkedIn Profile" name="linkedin_profile" :value="old('linkedin_profile')" placeholder="https://linkedin.com/in/..." /></div>
                    <div class="col"><x-admin.input type="email" field-class="field-full" label="Public Contact Email" name="public_contact_email" :value="old('public_contact_email')" placeholder="info@company.com" required /></div>
                    <div class="col"><x-admin.input label="Username" name="username" :value="old('username')" placeholder="Generated from email when empty" /></div>
                    <div class="col"><x-admin.input label="Temporary Password" name="temporary_password" :value="old('temporary_password')" placeholder="Generated when empty" /></div>
                </div>
            </div>
        </div>

        <!-- KEYWORDS -->
        <div class="form-card">
            <div class="form-card-header">
                <div class="form-card-icon purple"><x-admin.icon name="search-3" /></div>
                <div><div class="form-card-title">Search Keywords &amp; Specializations</div><div class="form-card-sub">Help professionals find this partner when searching the platform</div></div>
            </div>
            <div class="form-card-body">
                <div class="field"><label>Keywords <span class="req">*</span></label><div class="tag-input-wrap" id="tagWrap" onclick="document.getElementById('tagInput').focus()">@foreach ($oldKeywords as $keyword)<span class="tag-chip keyword" data-keyword-chip>{{ $keyword }}<span class="tag-chip-remove" onclick="removeKeywordTag(event,this)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></span></span>@endforeach<input type="text" id="tagInput" class="tag-input" placeholder="Type a keyword and press Enter..." onkeydown="handleKeywordInput(event)"><input type="hidden" id="keywordsInput" name="keywords" value="{{ $oldKeywords->toJson() }}"></div>@error('keywords')<div class="field-hint">{{ $message }}</div>@enderror<div class="field-hint">Examples: Catalyst, Reformer, CO2 removal, Compressors, Heat exchangers, Simulation tools.</div></div>
                <x-admin.radio-group label="Plant Type Served" name="plant_type_id" id="plantTypes" variant="checkbox-chip" :items="$plantTypeItems->isNotEmpty() ? $plantTypeItems : [['value' => '', 'label' => 'No plant type available', 'attributes' => ['disabled' => 'disabled']]]" :selected="old('plant_type_id')" hint="Partner profile currently stores one primary plant type." />
            </div>
        </div>

        <!-- STEP 4: SUBSCRIPTION & SETTINGS -->
        <div class="form-card">
            <div class="form-card-header">
                <div class="form-card-icon blue"><x-admin.icon name="billing" /></div>
                <div><div class="form-card-title">Subscription &amp; Activation</div><div class="form-card-sub">Configure subscription period and account status</div></div>
            </div>
            <div class="form-card-body">
                <div class="row row-cols-1 row-cols-md-2 g-3">
                    <div class="col"><x-admin.input type="date" label="Subscription Start Date" name="subscription_starts_at" :value="$defaultStartDate" required /></div>
                    <div class="col"><x-admin.input type="date" label="Subscription End Date" name="subscription_ends_at" :value="old('subscription_ends_at')" hint="Leave blank to calculate from the selected tier." /></div>
                    <div class="col"><x-admin.select label="Billing Method" name="payment_method" :selected="old('payment_method')" :options="$billingMethodOptions" /></div>
                    <div class="col"><x-admin.input label="Invoice Reference / PO Number" name="transaction_code" :value="old('transaction_code')" placeholder="e.g. INV-2026-0047" /></div>
                    <div class="col"><x-admin.input type="number" label="Payment Amount" name="payment_amount" :value="old('payment_amount')" placeholder="0.00" step="0.01" min="0.01" /></div>
                    <div class="col"><x-admin.select label="Payment Status" name="payment_status" :selected="old('payment_status')" :options="$paymentStatusOptions" /></div>
                    <div class="col"><x-admin.textarea field-class="field-full" label="Internal Admin Notes" name="subscription_admin_notes" :value="old('subscription_admin_notes')" placeholder="Any internal notes about this partner, onboarding context, special arrangements..." hint="Not visible to the partner." /></div>
                </div>
                <input type="hidden" name="activate_account" value="0"><x-admin.switch label="Activate account immediately" description="Partner can log in and access the platform as soon as account is created" name="activate_account" :checked="old('activate_account', '1') === '1'" />
                <input type="hidden" name="auto_renew" value="0"><x-admin.switch label="Auto-renew subscription" description="Store renewal preference on the partner subscription." name="auto_renew" :checked="old('auto_renew') === '1'" />
                <input type="hidden" name="require_email_verification" value="0"><x-admin.switch label="Require email verification before first login" description="Primary contact must verify email address before accessing the account." name="require_email_verification" :checked="old('require_email_verification', '1') === '1'" />
                <x-admin.switch label="Show partner on public directory" description="Partner profile is visible to members through current profile visibility rules" checked />
                <div class="info-banner"><x-admin.icon name="user-k-habib-flagged" /><p>Partner content uploads will require admin approval before publication, regardless of tier.</p></div>
            </div>
        </div>

        <!-- ACTION BAR -->
        <div class="action-bar">
            <div class="action-bar-left">All required fields must be completed before submitting.</div>
            <div class="action-bar-right">
                <button class="btn-secondary" type="button" onclick="saveDraft()"><x-admin.icon name="save-2" />Save Draft</button>
                <button class="btn-primary" type="submit"><x-admin.icon name="users-5" />Create Partner Account</button>
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
