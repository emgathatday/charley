@extends('layouts.rebuild-dashboard')

@section('title', 'Edit Partner Profile')


@section('content')
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <a href="{{ route('admin.dashboard.iam.users.show', $user) }}" class="back-link">
            <svg class="icon"><use href="/assets/icons/sprite.svg#icon-back-to-account-penalty-and"></use></svg>
            Back to {{ $company }}
        </a>
        <a href="{{ route('admin.dashboard.iam.users.partners') }}" class="back-link">Partner Management</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please review the highlighted fields.</strong>
        </div>
    @endif

    <form id="partnerEditForm" method="POST" action="{{ route('admin.dashboard.iam.users.update-partner', $user) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="save-bar">
            <div class="save-bar-left">
                <h1>Edit Partner Profile</h1>
                <div class="sub">{{ $company }} - {{ $partnerId }}</div>
            </div>
            <div class="save-bar-actions">
                <span class="unsaved-pill"><span class="dot"></span>Unsaved changes</span>
                <a class="btn-secondary" href="{{ route('admin.dashboard.iam.users.show', $user) }}">Cancel</a>
                <button class="btn-primary" type="submit">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-save-as-draft-svg-viewbox-0"></use></svg>
                    Save Changes
                </button>
            </div>
        </div>

        <div class="row g-4 edit-content-sidebar">
            <div class="col-12 col-xl-8">
                <div class="edit-card">
                    <div class="edit-card-head">
                        <div class="edit-card-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-partner-management-path-d-m9-12"></use></svg></div>
                        <div><h3>Company Identity</h3><div class="sub">Logo, name, and category shown across the platform</div></div>
                    </div>

                    <div class="logo-upload-row">
                        <div class="logo-upload-preview" id="logoPreview">@if ($partnerLogoUrl)<img src="{{ $partnerLogoUrl }}" alt="{{ $company }} logo" style="width: 100%;height: 100%;object-fit: cover;">@else{{ $initials }}@endif</div>
                        <div>
                            <div class="logo-upload-actions">
                                <label class="logo-upload-btn" for="logoFileInput"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-click-to-upload-or-drag"></use></svg>Upload logo</label>
                                <button class="logo-upload-btn remove" type="button" data-clear-file="true">Remove</button>
                            </div>
                            <input class="visually-hidden @error('logo_file') is-invalid @enderror" id="logoFileInput" name="logo_file" type="file" accept="image/png,image/jpeg,image/svg+xml,image/webp">
                            @error('logo_file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <div class="logo-upload-hint">PNG or SVG, square, at least 256x256px. Max 2MB.</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="company_name">Company (Display) Name</label>
                        <input id="company_name" name="company_name" type="text" class="form-input @error('company_name') is-invalid @enderror" value="{{ $company }}" required>
                        @error('company_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="legal_name">Legal Name</label>
                        <input id="legal_name" name="legal_name" type="text" class="form-input @error('legal_name') is-invalid @enderror" value="{{ old('legal_name', $profile->company_name ?? $company) }}">
                        @error('legal_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Plant Type</label>
                        <div class="checkbox-chip-group">
                            @forelse ($plantTypeOptions as $plantTypeId => $plantTypeName)
                                <label class="checkbox-chip">
                                    <input type="radio" name="plant_type_id" value="{{ $plantTypeId }}" @checked((string) $selectedPlantType === (string) $plantTypeId)>
                                    <span class="checkbox-chip-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                                    {{ $plantTypeName }}
                                </label>
                            @empty
                                <label class="checkbox-chip"><input type="radio" disabled><span class="checkbox-chip-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>No plant type available</label>
                            @endforelse
                        </div>
                        @error('plant_type_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <div class="form-hint">Partner profile supports one primary plant type through the current data contract.</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="company_overview">Company Description</label>
                        <textarea id="company_overview" name="company_overview" class="form-textarea @error('company_overview') is-invalid @enderror" maxlength="400">{{ old('company_overview', $profile->overview ?? '') }}</textarea>
                        @error('company_overview')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <div class="char-count">Shown on the partner's public profile page</div>
                    </div>

                    <div class="form-row cols-3">
                        <div class="form-group"><label class="form-label" for="country">Country</label><input id="country" name="country" type="text" class="form-input @error('country') is-invalid @enderror" value="{{ old('country', $profile->country ?? '') }}">@error('country')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                        <div class="form-group"><label class="form-label" for="website">Website</label><input id="website" name="website" type="url" class="form-input @error('website') is-invalid @enderror" value="{{ old('website', $profile->website ?? '') }}">@error('website')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                        <div class="form-group"><label class="form-label" for="founded_year">Founded Year</label><input id="founded_year" name="founded_year" type="number" class="form-input @error('founded_year') is-invalid @enderror" value="{{ old('founded_year', $profile->founded_year ?? '') }}">@error('founded_year')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                    </div>
                </div>

                <div class="edit-card">
                    <div class="edit-card-head">
                        <div class="edit-card-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-profile-verification-queue-5-svg"></use></svg></div>
                        <div><h3>Verification &amp; Documents</h3><div class="sub">Company identity, verification status, and supporting documents</div></div>
                    </div>
                    <div class="row row-cols-1 row-cols-md-3 g-3">
                        <div class="col"><div class="vsr-box ok"><div class="status-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-profile-verification-queue-5-svg"></use></svg></div><div><div class="k">Account Status</div><div class="v">{{ str_replace('_', ' ', ucfirst((string) $approvalStatus)) }}</div></div></div></div>
                        <div class="col"><div class="vsr-box ok"><div class="status-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-business-email-domain-verification-confirmed"></use></svg></div><div><div class="k">Business Email</div><div class="v">{{ old('public_contact_email', $profile->contact_email ?? $user->email) }}</div></div></div></div>
                        <div class="col"><div class="vsr-box due"><div class="status-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-schedule-publish-automatically-at-a"></use></svg></div><div><div class="k">Renewal Due</div><div class="v">{{ $renewalLabel ?: 'Not scheduled' }}</div></div></div></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="approval_status">Approval Status</label>
                        <select id="approval_status" name="approval_status" class="form-select @error('approval_status') is-invalid @enderror">
                            <option value="pending" @selected($approvalStatus === 'pending')>Pending</option>
                            <option value="approved" @selected($approvalStatus === 'approved')>Approved</option>
                            <option value="rejected" @selected($approvalStatus === 'rejected')>Rejected</option>
                            <option value="suspended" @selected($approvalStatus === 'suspended')>Suspended</option>
                        </select>
                        @error('approval_status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="field-section-label">Supporting Documents</div>
                    <div class="doc-row-edit"><div class="doc-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-company-registration-certificate-verified-10"></use></svg></div><div class="doc-text"><div class="doc-name">Company Registration Certificate</div><div class="doc-meta">TODO-safe static document until media IDs are exposed.</div></div></div>
                    <div class="doc-row-edit"><div class="doc-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-business-email-domain-verification-confirmed"></use></svg></div><div class="doc-text"><div class="doc-name">Business Email Domain Verification</div><div class="doc-meta">Confirmed - {{ old('public_contact_email', $profile->contact_email ?? $user->email) }}</div></div></div>
                </div>

                <div class="edit-card">
                    <div class="edit-card-head">
                        <div class="edit-card-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-k-overview-a-href-admin-d"></use></svg></div>
                        <div><h3>Primary Contact</h3><div class="sub">Person admins and members should contact for this partner</div></div>
                    </div>
                    <div class="form-row cols-2">
                        <div class="form-group"><label class="form-label" for="first_name">First Name</label><input id="first_name" name="first_name" type="text" class="form-input @error('first_name') is-invalid @enderror" value="{{ old('first_name', $user->first_name) }}" required>@error('first_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                        <div class="form-group"><label class="form-label" for="last_name">Last Name</label><input id="last_name" name="last_name" type="text" class="form-input @error('last_name') is-invalid @enderror" value="{{ old('last_name', $user->last_name) }}">@error('last_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                    </div>
                    <div class="form-row cols-2">
                        <div class="form-group"><label class="form-label" for="email">Login Email</label><input id="email" name="email" type="email" class="form-input @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>@error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                        <div class="form-group"><label class="form-label" for="username">Username</label><input id="username" name="username" type="text" class="form-input @error('username') is-invalid @enderror" value="{{ old('username', $user->username) }}">@error('username')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                    </div>
                    <div class="form-row cols-2">
                        <div class="form-group"><label class="form-label" for="public_contact_email">Public Contact Email</label><input id="public_contact_email" name="public_contact_email" type="email" class="form-input @error('public_contact_email') is-invalid @enderror" value="{{ old('public_contact_email', $profile->contact_email ?? $user->email) }}">@error('public_contact_email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                        <div class="form-group"><label class="form-label" for="phone">Phone</label><input id="phone" name="phone" type="text" class="form-input @error('phone') is-invalid @enderror" value="{{ old('phone', $profile->phone ?? '') }}">@error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                    </div>
                </div>

                <div class="edit-card">
                    <div class="edit-card-head"><div class="edit-card-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-ai-search-training-keywords"></use></svg></div><div><h3>Searchable Keywords</h3><div class="sub">Terms used by directory and AI matching</div></div></div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" for="keywordsInput">Keywords</label>
                        <div class="tag-input-box" id="keywordsBox" data-tag-box="true" data-tag-target="keywords">
                            @forelse ($keywordChipItems as $keyword)
                                <span class="tag-chip keyword">{{ $keyword }}<span class="tag-chip-remove" data-tag-remove="true"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-change-password-choose-a-strong"></use></svg></span></span>
                            @empty
                                <span class="tag-chip keyword">catalyst<span class="tag-chip-remove" data-tag-remove="true"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-change-password-choose-a-strong"></use></svg></span></span>
                            @endforelse
                            <input type="text" id="keywordsInput" placeholder="Type and press Enter to add..." data-tag-input="true">
                        </div>
                        <input id="keywords" name="keywords" type="hidden" value="{{ old('keywords', $keywordChipItems->implode(', ')) }}">
                        <div class="form-hint">Editable anytime - helps members find this partner in search.</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="edit-card">
                    <div class="edit-card-head"><div class="edit-card-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-5-jul-2026-14-32-circle"></use></svg></div><div><h3>Account Summary</h3><div class="sub">Read-only reference</div></div></div>
                    <div class="info-list">
                        <div class="info-row"><span class="k">Partner ID</span><span class="v">{{ $partnerId }}</span></div>
                        <div class="info-row"><span class="k">Tier</span><span class="v">{{ $tierName }}</span></div>
                        <div class="info-row"><span class="k">Status</span><span class="v">{{ str_replace('_', ' ', ucfirst((string) $approvalStatus)) }}</span></div>
                        <div class="info-row"><span class="k">Registered</span><span class="v">{{ $joined }}</span></div>
                    </div>
                </div>

                <div class="edit-card">
                    <div class="edit-card-head"><div class="edit-card-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-platform-settings-path-d-m19-4"></use></svg></div><div><h3>Partner Directory Settings</h3><div class="sub">Control where this profile appears</div></div></div>
                    <div class="switch-row"><div class="toggle-row-text"><div class="t">Show in Partner Directory</div><div class="s">Members can find this partner via search</div></div><label class="switch"><input type="checkbox" name="feed_highlight_enabled" value="1" @checked($feedHighlightEnabled)><span class="slider"></span></label></div>
                    <div class="switch-row"><div class="toggle-row-text"><div class="t">Allow Direct Messaging</div><div class="s">Partner may initiate messages to professionals</div></div><label class="switch"><input type="checkbox" checked disabled><span class="slider"></span></label></div>
                    <div class="form-group">
                        <label class="form-label" for="layout_template">Public Layout</label>
                        <select id="layout_template" name="layout_template" class="form-select @error('layout_template') is-invalid @enderror">
                            <option value="layout_1" @selected($layoutTemplate === 'layout_1')>Layout 1</option>
                            <option value="layout_2" @selected($layoutTemplate === 'layout_2')>Layout 2</option>
                            <option value="layout_3" @selected($layoutTemplate === 'layout_3')>Layout 3</option>
                        </select>
                        @error('layout_template')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="edit-card">
                    <div class="edit-card-head"><div class="edit-card-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-subscription-and-billing"></use></svg></div><div><h3>Subscription Settings</h3><div class="sub">Tier and lifecycle controls</div></div></div>
                    <div class="form-group">
                        <label class="form-label" for="subscription_tier_id">Subscription Tier</label>
                        <select id="subscription_tier_id" name="subscription_tier_id" class="form-select @error('subscription_tier_id') is-invalid @enderror">
                            <option value="">No active tier</option>
                            @forelse ($subscriptionTiers as $tier)
                                <option value="{{ $tier->id }}" @selected((string) $selectedTier === (string) $tier->id)>{{ $tier->display_name }}</option>
                            @empty
                                <option value="" disabled>No active tiers available</option>
                            @endforelse
                        </select>
                        @error('subscription_tier_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="subscription_status">Subscription Status</label>
                        <select id="subscription_status" name="subscription_status" class="form-select @error('subscription_status') is-invalid @enderror">
                            <option value="inactive" @selected($selectedSubscriptionStatus === 'inactive')>Inactive</option>
                            <option value="pending_approval" @selected($selectedSubscriptionStatus === 'pending_approval')>Pending Approval</option>
                            <option value="active" @selected($selectedSubscriptionStatus === 'active')>Active</option>
                            <option value="suspended" @selected($selectedSubscriptionStatus === 'suspended')>Suspended</option>
                            <option value="expired" @selected($selectedSubscriptionStatus === 'expired')>Expired</option>
                            <option value="cancelled" @selected($selectedSubscriptionStatus === 'cancelled')>Cancelled</option>
                        </select>
                        @error('subscription_status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="danger-zone"><h3>Freeze Account</h3><div class="sub">Temporarily disables this partner's ability to publish content or message members.</div><button class="btn-danger-outline" type="button"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-account-penalty-and-freeze-3"></use></svg>Freeze Account</button></div>

                <div class="edit-card">
                    <div class="edit-card-head"><div class="edit-card-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-account-penalty-and-freeze-3"></use></svg></div><div><h3>Account Access</h3><div class="sub">User login state</div></div></div>
                    <div class="form-group">
                        <label class="form-label" for="status">Account Status</label>
                        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="active" @selected(old('status', $user->status) === 'active')>Active</option>
                            <option value="suspended" @selected(old('status', $user->status) === 'suspended')>Suspended</option>
                            <option value="frozen" @selected(old('status', $user->status) === 'frozen')>Frozen</option>
                        </select>
                        @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/edit-partner.js') }}"></script>
@endpush
