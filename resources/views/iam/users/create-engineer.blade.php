@extends('layouts.rebuild-dashboard')

@section('title', 'Create New User')

@section('content')
    <form id="createEngineerForm" method="POST" action="{{ route('admin.dashboard.iam.users.store-engineer') }}">
        @csrf

        <div class="page-head">
            <div>
                <h1>Create New User</h1>
                <p>Manually add a registered member or professional engineer account. Partner accounts are created separately from Partner Management.</p>
            </div>
            <div class="page-head-actions">
                <button class="btn-ghost" type="button" onclick="history.back()">Cancel</button>
                <button class="btn-primary" id="saveBtn" type="button" onclick="createUser()">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-create-user-svg-viewbox-0-0"></use></svg>
                    Create User
                </button>
            </div>
        </div>

        @if ($errors->any())
            <div class="success-banner show" id="validationBanner">
                <div class="s-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-danger-zone-div-class-set"></use></svg></div>
                <div>
                    <b>Account was not created.</b>
                    <span>{{ $errors->first() }}</span>
                </div>
            </div>
        @endif

        <div class="success-banner" id="successBanner">
            <div class="s-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-create-user-svg-viewbox-0-0"></use></svg></div>
            <div>
                <b>Account created.</b>
                <span id="successDetail">The user will receive an email invitation to set up their account.</span>
            </div>
        </div>

        <div class="form-col">

            <!-- Account type -->
            <div class="card">
                <div class="card-head">
                    <span class="step-tag">Step 1</span>
                    <h2>Account type</h2>
                    <p>Choose one of the two supported engineer account variants before entering identity details.</p>
                </div>
                <div class="card-body">
                    <div class="row" id="typeGrid">
                        <div class="col-md-6">
                            <label class="type-card {{ old('account_type', 'member') === 'member' ? 'checked' : '' }}" data-type="member">
                                <input type="radio" name="account_type" value="member" @checked(old('account_type', 'member') === 'member')>
                                <div class="t-top">
                                    <div class="t-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-user-k-habib-flagged-for"></use></svg></div>
                                    <span class="t-check"></span>
                                </div>
                                <div class="t-name">Registered Member</div>
                                <div class="t-desc">Unverified engineer/member account for free registration and pending verification.</div>
                                <ul>
                                    <li>Maps to users.role = unverified_member</li>
                                    <li>Verification remains pending until promoted</li>
                                </ul>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="type-card {{ old('account_type') === 'professional' ? 'checked' : '' }}" data-type="professional">
                                <input type="radio" name="account_type" value="professional" @checked(old('account_type') === 'professional')>
                                <div class="t-top">
                                    <div class="t-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-profile-verification-queue-5-svg"></use></svg></div>
                                    <span class="t-check"></span>
                                </div>
                                <div class="t-name">Professional</div>
                                <div class="t-desc">Verified engineer account via work email, LinkedIn, or company/university letter.</div>
                                <ul>
                                    <li>Maps to users.role = professional</li>
                                    <li>Shows Professional-only setup placeholders</li>
                                </ul>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Basic info -->
            <div class="card">
                <div class="card-head">
                    <span class="step-tag">Step 2</span>
                    <h2>Basic information</h2>
                    <p>This becomes the public-facing profile once the account is active.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3 form-grid-row">
                        <div class="col-md-4">
                            <div class="field">
                                <label>First name<span class="req">*</span></label>
                                <input type="text" id="firstName" name="first_name" value="{{ old('first_name') }}" placeholder="e.g. Ahmed">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="field">
                                <label>Last name<span class="req">*</span></label>
                                <input type="text" id="lastName" name="last_name" value="{{ old('last_name') }}" placeholder="e.g. Ghani">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="field">
                                <label>Email address<span class="req">*</span></label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="name@company.com">
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 form-grid-row">
                        <div class="col-md-6">
                            <div class="field">
                                <label>Username</label>
                                <input type="text" id="username" name="username" value="{{ old('username') }}" placeholder="Auto-generated from name or email if left blank">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="field">
                                <label>Account status</label>
                                <select id="status" name="status">
                                    <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                                    <option value="suspended" @selected(old('status') === 'suspended')>Suspended</option>
                                    <option value="frozen" @selected(old('status') === 'frozen')>Frozen</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 form-grid-row">
                        <div class="col-md-4">
                            <div class="field">
                                <label>Position / job title</label>
                                <input type="text" id="position" name="position" value="{{ old('position') }}" placeholder="e.g. Process Technology Manager">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="field">
                                <label>Company / plant name</label>
                                <input type="text" id="company" name="company" value="{{ old('company') }}" placeholder="e.g. Northgate Ammonia Plant">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="field">
                                <label>Phone number</label>
                                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" placeholder="+1 555 000 0000">
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 form-grid-row">
                        <div class="col">
                            <div class="field">
                                <label>Industry background</label>
                                <div class="checkbox-chip-group" id="plantChips">
                                    @foreach ($plantTypeOptions as $plantTypeId => $plantTypeName)
                                        <label class="checkbox-chip">
                                            <input type="checkbox" name="plant_type_ids[]" value="{{ $plantTypeId }}" @checked(in_array((string) $plantTypeId, old('plant_type_ids', []), true))>
                                            <span class="checkbox-chip-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                                            {{ $plantTypeName }}
                                        </label>
                                    @endforeach
                                </div>
                                <input type="hidden" name="primary_plant_type_id" id="primaryPlantTypeId" value="{{ old('primary_plant_type_id') }}">
                                <div class="hint">Select all that apply - drives Charley AI and Q&amp;A personalization.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Verification & Expertise (Professional only) -->
            <div class="card professional-only {{ old('account_type', 'member') === 'professional' ? '' : 'hidden-section' }}" id="verificationCard">
                <div class="card-head">
                    <span class="step-tag">Step 3</span>
                    <h2>Verification &amp; expertise rank</h2>
                    <p>Required to grant Professional-level access per the platform's verification workflow.</p>
                </div>
                <div class="card-body">
                    <div class="row row-cols-1 row-cols-md-2 g-3">
                        <div class="col">
                            <div class="field">
                                <label>Verification method</label>
                                <select id="verifyMethod" name="verification_method">
                                    @php($verificationMethod = old('verification_method', 'Professional work email'))
                                    @foreach(['Professional work email', 'LinkedIn profile', 'Company verification letter', 'University letter', 'Equivalent professional verification'] as $method)
                                        <option value="{{ $method }}" @selected($verificationMethod === $method)>{{ $method }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <div class="field">
                                <label>Years of industry experience</label>
                                <input type="number" id="yearsExp" name="years_experience" min="0" max="60" value="{{ old('years_experience') }}" placeholder="e.g. 12" oninput="updateRank()">
                            </div>
                        </div>
                    </div>
                    <div class="row row-cols-1 g-3">
                        <div class="col">
                            <div class="field">
                                <label>Resulting expertise rank</label>
                                <div class="rank-readout">
                                    <div class="r-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-monthly-expert-recognition-svg-viewbox-0"></use></svg></div>
                                    <div>
                                        <div class="r-text">Assigned automatically from years of experience</div>
                                        <div class="r-value" id="rankValue">Registered Member - no rank</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row row-cols-1 g-3">
                        <div class="col">
                            <div class="field">
                                <label>Supporting document</label>
                                <div class="upload-well">
                                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-click-to-upload-cv-company"></use></svg>
                                    <div><b>Click to upload</b> CV, company letter, or LinkedIn export - PDF or image, max 10MB</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Expertise Areas (Professional only) -->
            <div class="card professional-only {{ old('account_type', 'member') === 'professional' ? '' : 'hidden-section' }}" id="expertiseAreasCard">
                <div class="card-head">
                    <span class="step-tag">Step 4</span>
                    <h2>Top expertise areas</h2>
                    <p>Self-assessed technical areas shown on the profile - up to 5 areas. Per Charley's rules, the self-rating ceiling is capped by the user's expertise rank (100% unlocks only after passing that area's quiz, done later by the user).</p>
                </div>
                <div class="card-body">
                    <div class="ceiling-note" id="ceilingNote">
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-set-years-of-industry-experience"></use></svg>
                        <span id="ceilingNoteText">Set years of industry experience above to unlock a self-rating ceiling.</span>
                    </div>
                    <div id="expertiseAreaList"></div>
                    <div class="ea-empty" id="eaEmptyMsg">No expertise areas added yet.</div>
                    <button type="button" class="ea-add-btn" id="addAreaBtn" onclick="addExpertiseArea()">
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-add-note"></use></svg>
                        Add expertise area
                    </button>
                </div>
            </div>

            <!-- Access & security -->
            <div class="card">
                <div class="card-head">
                    <span class="step-tag">Step 5</span>
                    <h2>Access &amp; security</h2>
                    <p>Choose how the user receives their initial credentials.</p>
                </div>
                <div class="card-body">
                    <div class="row row-cols-1 g-3">
                        <div class="col">
                            <div class="field">
                                <label>Sign-in method</label>
                                <div class="choice-chip-group">
                                    <label class="choice-chip">
                                        <input type="radio" name="signin" value="invite" @checked(old('signin', 'invite') === 'invite') onchange="toggleTempPasswordField()">
                                        <span class="choice-chip-dot"></span>
                                        Send email invitation
                                    </label>
                                    <label class="choice-chip">
                                        <input type="radio" name="signin" value="password" @checked(old('signin') === 'password') onchange="toggleTempPasswordField()">
                                        <span class="choice-chip-dot"></span>
                                        Set temporary password
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row row-cols-1 g-3 hidden-section" id="tempPasswordField">
                        <div class="col">
                            <div class="field">
                                <label>Temporary password<span class="req">*</span></label>
                                <div class="pw-input-wrap">
                                    <input type="text" id="tempPassword" name="temporary_password" value="{{ old('temporary_password') }}" placeholder="e.g. Charley#2026Xk">
                                    <button type="button" class="pw-gen-btn" onclick="generateTempPassword()">
                                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-generate-share-this-with-the"></use></svg>
                                        Generate
                                    </button>
                                </div>
                                <div class="hint">Share this with the user through a secure channel - do not send it by unencrypted email alongside their username.</div>
                            </div>
                        </div>
                    </div>
                    <div class="switch-row">
                        <div>
                            <div class="sw-label">Force password reset on first login</div>
                            <div class="sw-desc">Recommended when setting a temporary password directly.</div>
                        </div>
                        <label class="switch"><input type="checkbox" name="force_password_reset" value="1" checked><span class="slider"></span></label>
                    </div>
                    <div class="switch-row">
                        <div>
                            <div class="sw-label">Skip re-verification reminder for 12 months</div>
                            <div class="sw-desc">Use for accounts verified offline by admin, outside the normal flow.</div>
                        </div>
                        <label class="switch"><input type="checkbox" name="verification_intent" value="1"><span class="slider"></span></label>
                    </div>
                    <div class="switch-row">
                        <div>
                            <div class="sw-label">Notify user by email once created</div>
                            <div class="sw-desc">Sends a welcome email with next steps.</div>
                        </div>
                        <label class="switch"><input type="checkbox" name="notify_user" value="1" checked><span class="slider"></span></label>
                    </div>
                </div>
            </div>

            <!-- Admin notes -->
            <div class="card">
                <div class="card-head">
                    <span class="step-tag">Step 6</span>
                    <h2>Internal admin notes</h2>
                    <p>Visible only to admins - not shown on the user's public profile.</p>
                </div>
                <div class="card-body">
                    <div class="row row-cols-1 g-3">
                        <div class="col">
                            <div class="field">
                                <textarea id="adminNotes" name="admin_notes" placeholder="e.g. Verified via LinkedIn + company letter received by email on..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom action bar -->
            <div class="bottom-actions">
                <span class="ba-note">Double-check details above - you can still edit the account after creation.</span>
                <button type="button" class="btn-ghost" onclick="history.back()">Cancel</button>
                <button type="button" class="btn-primary" id="saveBtnBottom" onclick="createUser()">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-create-user-svg-viewbox-0-0"></use></svg>
                    Create User
                </button>
            </div>

        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            const page = {
                knowledgeDomainsByPlantType: @json($knowledgeDomainsByPlantType ?? []),
                expertiseAreas: [],
                eaIdCounter: 0,
                init() {
                    this.bindAccountTypeCards();
                    this.bindPlantTypeChips();
                    this.syncPrimaryPlantType();
                    this.updateRank();
                    this.toggleTempPasswordField();
                    this.renderExpertiseAreas();
                },
                getTypeCards() {
                    return document.querySelectorAll('#typeGrid .type-card');
                },
                bindAccountTypeCards() {
                    this.getTypeCards().forEach((card) => {
                        card.addEventListener('click', () => this.selectAccountType(card));
                        const input = card.querySelector('input[name="account_type"]');
                        if (input) input.addEventListener('change', () => this.selectAccountType(card));
                    });
                    const checked = document.querySelector('#typeGrid input[name="account_type"]:checked');
                    if (checked) this.toggleProfessionalSections(checked.value);
                },
                selectAccountType(selectedCard) {
                    this.getTypeCards().forEach((card) => {
                        const input = card.querySelector('input[name="account_type"]');
                        card.classList.remove('checked');
                        if (input) input.checked = false;
                    });
                    selectedCard.classList.add('checked');
                    const input = selectedCard.querySelector('input[name="account_type"]');
                    if (input) input.checked = true;
                    this.toggleProfessionalSections(selectedCard.dataset.type);
                },
                toggleProfessionalSections(accountType) {
                    ['verificationCard', 'expertiseAreasCard'].forEach((id) => {
                        const section = document.getElementById(id);
                        if (section) section.classList.toggle('hidden-section', accountType !== 'professional');
                    });
                },
                bindPlantTypeChips() {
                    document.querySelectorAll('#plantChips input[name="plant_type_ids[]"]').forEach((box) => {
                        box.addEventListener('change', () => {
                            this.syncPrimaryPlantType();
                            this.resetExpertiseAreas();
                        });
                    });
                },
                syncPrimaryPlantType() {
                    const boxes = Array.from(document.querySelectorAll('#plantChips input[name="plant_type_ids[]"]'));
                    const primary = document.getElementById('primaryPlantTypeId');
                    if (!primary) return;
                    const selected = boxes.find((box) => box.checked);
                    primary.value = selected ? selected.value : '';
                },
                updateRank() {
                    const yearsInput = document.getElementById('yearsExp');
                    const rankValue = document.getElementById('rankValue');
                    if (!yearsInput || !rankValue) return;
                    const years = parseFloat(yearsInput.value);
                    rankValue.textContent = this.getRankLabel(years);
                    this.updateExpertiseCeiling();
                },
                getExpertiseCeiling(years) {
                    if (Number.isNaN(years)) return { label: 'Registered Member', max: 0 };
                    if (years < 8) return { label: 'Industry Professional', max: 30 };
                    if (years < 15) return { label: 'Experienced Professional', max: 50 };
                    return { label: 'Senior Industry Expert', max: 70 };
                },
                getRankLabel(years) {
                    if (Number.isNaN(years)) return 'Registered Member - no rank';
                    if (years < 8) return 'Industry Professional (0-7 yrs)';
                    if (years < 15) return 'Experienced Professional (8-15 yrs)';
                    return 'Senior Industry Expert (15+ yrs)';
                },
                updateExpertiseCeiling() {
                    const yearsInput = document.getElementById('yearsExp');
                    const noteText = document.getElementById('ceilingNoteText');
                    const years = yearsInput ? parseFloat(yearsInput.value) : NaN;
                    const ceiling = this.getExpertiseCeiling(years);
                    if (noteText) {
                        noteText.textContent = ceiling.max === 0
                            ? 'Registered Member has no expertise rank yet - self-ratings will unlock once verified with years of experience.'
                            : 'Current rank: ' + ceiling.label + ' - self-rating ceiling is ' + ceiling.max + "% per area (100% only unlocks per-area after the user later passes that area's quiz).";
                    }
                    this.expertiseAreas.forEach((area) => {
                        if (area.rate > ceiling.max) area.rate = ceiling.max;
                    });
                    this.renderExpertiseAreas();
                },
                resetExpertiseAreas() {
                    this.expertiseAreas = [];
                    this.renderExpertiseAreas();
                },
                technicalAreas() {
                    const primary = document.getElementById('primaryPlantTypeId');
                    if (!primary || !primary.value) return [];

                    return this.knowledgeDomainsByPlantType[primary.value] || [];
                },
                addExpertiseArea() {
                    if (this.expertiseAreas.length >= 5) return;
                    const technicalAreas = this.technicalAreas();
                    if (technicalAreas.length === 0) return;
                    const yearsInput = document.getElementById('yearsExp');
                    const years = yearsInput ? parseFloat(yearsInput.value) : NaN;
                    const ceiling = this.getExpertiseCeiling(years);
                    this.eaIdCounter += 1;
                    this.expertiseAreas.push({
                        id: this.eaIdCounter,
                        domainId: '',
                        rate: Math.min(20, ceiling.max)
                    });
                    this.renderExpertiseAreas();
                },
                removeExpertiseArea(id) {
                    this.expertiseAreas = this.expertiseAreas.filter((area) => area.id !== id);
                    this.renderExpertiseAreas();
                },
                setAreaName(id, value) {
                    const area = this.findExpertiseArea(id);
                    if (area) area.domainId = value;
                },
                setAreaRate(id, value) {
                    const area = this.findExpertiseArea(id);
                    const yearsInput = document.getElementById('yearsExp');
                    const years = yearsInput ? parseFloat(yearsInput.value) : NaN;
                    const ceiling = this.getExpertiseCeiling(years);
                    if (!area) return;
                    area.rate = Math.min(parseInt(value, 10) || 0, ceiling.max);
                    this.updateAreaRateUi(area, ceiling.max);
                },
                findExpertiseArea(id) {
                    return this.expertiseAreas.find((area) => area.id === id);
                },
                renderExpertiseAreas() {
                    const list = document.getElementById('expertiseAreaList');
                    const emptyMsg = document.getElementById('eaEmptyMsg');
                    const addBtn = document.getElementById('addAreaBtn');
                    const yearsInput = document.getElementById('yearsExp');
                    const years = yearsInput ? parseFloat(yearsInput.value) : NaN;
                    const ceiling = this.getExpertiseCeiling(years);
                    if (!list) return;
                    list.innerHTML = this.expertiseAreas.map((area, index) => this.renderExpertiseArea(area, index, ceiling.max)).join('');
                    this.expertiseAreas.forEach((area) => this.updateAreaRateUi(area, ceiling.max));
                    if (emptyMsg) {
                        emptyMsg.textContent = this.technicalAreas().length === 0
                            ? 'Select an Industry background Plant Type to show active technical areas.'
                            : 'No expertise areas added yet.';
                        emptyMsg.style.display = this.expertiseAreas.length === 0 ? 'block' : 'none';
                    }
                    if (addBtn) {
                        const hasTechnicalAreas = this.technicalAreas().length > 0;
                        addBtn.disabled = this.expertiseAreas.length >= 5 || !hasTechnicalAreas;
                        addBtn.innerHTML = this.expertiseAreas.length >= 5
                            ? '<svg class="icon"><use href="/assets/icons/sprite.svg#icon-delete"></use></svg> Maximum of 5 areas reached'
                            : !hasTechnicalAreas
                                ? '<svg class="icon"><use href="/assets/icons/sprite.svg#icon-add-note"></use></svg> Select Industry background first'
                                : '<svg class="icon"><use href="/assets/icons/sprite.svg#icon-add-note"></use></svg> Add expertise area';
                    }
                },
                renderExpertiseArea(area, index, max) {
                    const selectedIds = this.expertiseAreas.map((item) => String(item.domainId));
                    const options = this.technicalAreas()
                        .filter((domain) => String(domain.id) === String(area.domainId) || selectedIds.indexOf(String(domain.id)) === -1)
                        .map((domain) => '<option value="' + this.escapeHtml(domain.id) + '"' + (String(domain.id) === String(area.domainId) ? ' selected' : '') + '>' + this.escapeHtml(domain.name) + '</option>')
                        .join('');
                    return [
                        '<div class="ea-card">',
                        '<div class="ea-num">' + (index + 1) + '</div>',
                        '<select class="ea-select" onchange="setAreaName(' + area.id + ', this.value)">',
                        '<option value="">Select a technical area...</option>',
                        options,
                        '</select>',
                        '<div class="ea-rate">',
                        '<input class="ea-range-input" type="range" id="eaRange' + area.id + '" min="0" max="' + max + '" value="' + area.rate + '" oninput="setAreaRate(' + area.id + ', this.value)">',
                        '<span class="ea-rate-val" id="eaVal' + area.id + '">' + area.rate + '%</span>',
                        '</div>',
                        '<button type="button" class="ea-remove" onclick="removeExpertiseArea(' + area.id + ')" aria-label="Remove area">',
                        '<svg class="icon"><use href="/assets/icons/sprite.svg#icon-remove-partner-account"></use></svg>',
                        '</button>',
                        '</div>'
                    ].join('');
                },
                updateAreaRateUi(area, max) {
                    const valEl = document.getElementById('eaVal' + area.id);
                    const rangeEl = document.getElementById('eaRange' + area.id);
                    const fillPct = max > 0 ? (area.rate / max) * 100 : 0;
                    if (valEl) valEl.textContent = area.rate + '%';
                    if (rangeEl) {
                        rangeEl.value = area.rate;
                        rangeEl.max = max;
                        rangeEl.style.setProperty('--fill', fillPct + '%');
                    }
                },
                toggleTempPasswordField() {
                    const field = document.getElementById('tempPasswordField');
                    const selected = document.querySelector('input[name="signin"]:checked');
                    if (field) field.classList.toggle('hidden-section', !selected || selected.value !== 'password');
                },
                generateTempPassword() {
                    const words = ['Charley', 'Syngas', 'Reformer', 'Catalyst', 'Synloop', 'Ammonia', 'Methanol', 'Hydrogen'];
                    const symbols = '!@#$%*?';
                    const word = words[Math.floor(Math.random() * words.length)];
                    const symbol = symbols[Math.floor(Math.random() * symbols.length)];
                    const digits = Math.floor(1000 + Math.random() * 9000);
                    const input = document.getElementById('tempPassword');
                    if (input) input.value = word + symbol + digits + 'Xk';
                },
                createUser() {
                    const firstName = this.getInputValue('firstName');
                    const lastName = this.getInputValue('lastName');
                    const email = this.getInputValue('email');
                    if (!firstName) { alert('Please enter a first name before creating the account.'); return; }
                    if (!lastName) { alert('Please enter a last name before creating the account.'); return; }
                    if (!email) { alert('Please enter an email address before creating the account.'); return; }
                    document.getElementById('createEngineerForm').submit();
                },
                getInputValue(id) {
                    const input = document.getElementById(id);
                    return input ? input.value.trim() : '';
                },
                escapeHtml(value) {
                    return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                }
            };
            window.updateRank = () => page.updateRank();
            window.addExpertiseArea = () => page.addExpertiseArea();
            window.removeExpertiseArea = (id) => page.removeExpertiseArea(id);
            window.setAreaName = (id, value) => page.setAreaName(id, value);
            window.setAreaRate = (id, value) => page.setAreaRate(id, value);
            window.createUser = () => page.createUser();
            window.toggleTempPasswordField = () => page.toggleTempPasswordField();
            window.generateTempPassword = () => page.generateTempPassword();
            document.addEventListener('DOMContentLoaded', () => page.init());
        })();
    </script>
@endpush
