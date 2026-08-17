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
                <button class="btn-ghost js-back" type="button">Cancel</button>
                <button class="btn-primary" id="saveBtn" type="button">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-check-2"></use></svg>
                    Create User
                </button>
            </div>
        </div>

        @if ($errors->any())
            <div class="success-banner show" id="validationBanner">
                <div class="s-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-danger-zone"></use></svg></div>
                <div>
                    <b>Account was not created.</b>
                    <span>{{ $errors->first() }}</span>
                </div>
            </div>
        @endif

        <div class="success-banner" id="successBanner">
            <div class="s-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-check-2"></use></svg></div>
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
                        @foreach ($accountTypeCards as $accountTypeCard)
                            <div class="col-md-6">
                                <label class="type-card {{ old('account_type', $accountTypeCard['default'] ?? null) === $accountTypeCard['value'] ? 'checked' : '' }}" data-type="{{ $accountTypeCard['value'] }}">
                                    <input type="radio" name="account_type" value="{{ $accountTypeCard['value'] }}" @checked(old('account_type', 'member') === $accountTypeCard['value'])>
                                    <div class="t-top">
                                        <div class="t-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#{{ $accountTypeCard['icon'] }}"></use></svg></div>
                                        <span class="t-check"></span>
                                    </div>
                                    <div class="t-name">{{ $accountTypeCard['name'] }}</div>
                                    <div class="t-desc">{{ $accountTypeCard['description'] }}</div>
                                    <ul>
                                        @foreach ($accountTypeCard['details'] as $detail)
                                            <li>{{ $detail }}</li>
                                        @endforeach
                                    </ul>
                                </label>
                            </div>
                        @endforeach
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
                    @foreach ($basicInfoRows as $basicInfoRow)
                        <div class="row g-3 form-grid-row">
                            @foreach ($basicInfoRow as $field)
                                <div class="{{ $field['column'] }}">
                                    @if (($field['component'] ?? 'input') === 'select')
                                        <x-admin.select
                                            :label="$field['label']"
                                            :name="$field['name']"
                                            :id="$field['id']"
                                            :selected="old($field['name'], $field['default'] ?? null)"
                                            :options="$field['options']"
                                            :required="$field['required'] ?? false"
                                        />
                                    @else
                                        <x-admin.input
                                            :type="$field['type']"
                                            :label="$field['label']"
                                            :name="$field['name']"
                                            :id="$field['id']"
                                            :value="old($field['name'])"
                                            :placeholder="$field['placeholder']"
                                            :required="$field['required'] ?? false"
                                        />
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                    <div class="row g-3 form-grid-row">
                        <div class="col">
                            <x-admin.checkbox-group label="Industry background" name="plant_type_ids[]" id="plantChips" :items="$plantTypeOptions" :selected="old('plant_type_ids', [])" hint="Select all that apply - drives Charley AI and Q&A personalization." />
                            <input type="hidden" name="primary_plant_type_id" id="primaryPlantTypeId" value="{{ old('primary_plant_type_id') }}">
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
                        @foreach ($verificationFields as $field)
                            <div class="{{ $field['column'] }}">
                                @if (($field['component'] ?? 'input') === 'select')
                                    <x-admin.select
                                        :label="$field['label']"
                                        :name="$field['name']"
                                        :id="$field['id']"
                                        :selected="old($field['name'], $field['default'] ?? null)"
                                        :options="$field['options']"
                                    />
                                @else
                                    <x-admin.input
                                        :type="$field['type']"
                                        :label="$field['label']"
                                        :name="$field['name']"
                                        :id="$field['id']"
                                        :value="old($field['name'])"
                                        :placeholder="$field['placeholder']"
                                        :min="$field['attributes']['min'] ?? null"
                                        :max="$field['attributes']['max'] ?? null"
                                    />
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="row row-cols-1 g-3">
                        <div class="col">
                            <div class="field">
                                <label>Resulting expertise rank</label>
                                <div class="rank-readout">
                                    <div class="r-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-expert-recognition"></use></svg></div>
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
                                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-upload"></use></svg>
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
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-set-years-industry-experience"></use></svg>
                        <span id="ceilingNoteText">Set years of industry experience above to unlock a self-rating ceiling.</span>
                    </div>
                    <div id="expertiseAreaList"></div>
                    <div class="ea-empty" id="eaEmptyMsg">No expertise areas added yet.</div>
                    <button type="button" class="ea-add-btn" id="addAreaBtn">
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-plus"></use></svg>
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
                            <x-admin.radio-group label="Sign-in method" name="signin" :selected="old('signin', 'invite')" :items="[
                                'invite' => 'Send email invitation',
                                'password' => 'Set temporary password',
                            ]" />
                        </div>
                    </div>
                    <div class="row row-cols-1 g-3 hidden-section" id="tempPasswordField">
                        <div class="col">
                            <div class="field">
                                <label>Temporary password<span class="req">*</span></label>
                                <div class="pw-input-wrap">
                                    <input type="text" id="tempPassword" name="temporary_password" value="{{ old('temporary_password') }}" placeholder="e.g. Charley#2026Xk">
                                    <button type="button" class="pw-gen-btn" id="generateTempPasswordBtn">
                                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-generate-share"></use></svg>
                                        Generate
                                    </button>
                                </div>
                                <div class="hint">Share this with the user through a secure channel - do not send it by unencrypted email alongside their username.</div>
                            </div>
                        </div>
                    </div>
                    <x-admin.switch label="Force password reset on first login" description="Recommended when setting a temporary password directly." name="force_password_reset" checked />
                    <x-admin.switch label="Skip re-verification reminder for 12 months" description="Use for accounts verified offline by admin, outside the normal flow." name="verification_intent" />
                    <x-admin.switch label="Notify user by email once created" description="Sends a welcome email with next steps." name="notify_user" checked />
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
                            <x-admin.textarea name="admin_notes" id="adminNotes" placeholder="e.g. Verified via LinkedIn + company letter received by email on..." />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom action bar -->
            <div class="bottom-actions">
                <span class="ba-note">Double-check details above - you can still edit the account after creation.</span>
                <button type="button" class="btn-ghost js-back">Cancel</button>
                <button type="button" class="btn-primary" id="saveBtnBottom">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-check-2"></use></svg>
                    Create User
                </button>
            </div>

        </div>
    </form>
@endsection

@push('scripts')
    <script type="application/json" id="createEngineerConfig">@json(['knowledgeDomainsByPlantType' => $knowledgeDomainsByPlantType ?? []])</script>
    <script src="{{ asset('assets/js/pages/create-engineer.js') }}"></script>
@endpush