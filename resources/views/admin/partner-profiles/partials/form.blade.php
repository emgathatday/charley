@php
    $selectedUser = old('user_id', $partnerProfile?->user_id);
    $selectedPlantType = old('plant_type_id', $partnerProfile?->plant_type_id);
    $selectedLogo = old('logo_media_id', $partnerProfile?->logo_media_id);
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="user_id" class="form-label">User</label>
        <select id="user_id" class="form-select @error('user_id') is-invalid @enderror" name="user_id" required>
            <option value="">Select user</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((string) $selectedUser === (string) $user->id)>
                    {{ trim($user->first_name.' '.$user->last_name) ?: $user->username }} ({{ $user->email }})
                </option>
            @endforeach
        </select>
        @error('user_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        @if ($users->isEmpty())
            <div class="form-text text-warning">No available users without a partner profile.</div>
        @endif
    </div>

    <div class="col-md-6">
        <label for="company_name" class="form-label">Company Name</label>
        <input id="company_name" class="form-control @error('company_name') is-invalid @enderror" name="company_name" value="{{ old('company_name', $partnerProfile?->company_name) }}" required>
        @error('company_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="partner_tier" class="form-label">Partner Tier</label>
        <select id="partner_tier" class="form-select @error('partner_tier') is-invalid @enderror" name="partner_tier">
            <option value="">None</option>
            @foreach (['gold','diamond','platinum'] as $tier)
                <option value="{{ $tier }}" @selected(old('partner_tier', $partnerProfile?->partner_tier) === $tier)>{{ ucfirst($tier) }}</option>
            @endforeach
        </select>
        @error('partner_tier')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="plant_type_id" class="form-label">Plant Type</label>
        <select id="plant_type_id" class="form-select @error('plant_type_id') is-invalid @enderror" name="plant_type_id">
            <option value="">None</option>
            @foreach ($plantTypes as $plantType)
                <option value="{{ $plantType->id }}" @selected((string) $selectedPlantType === (string) $plantType->id)>{{ $plantType->name }}</option>
            @endforeach
        </select>
        @error('plant_type_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="logo_media_id" class="form-label">Logo Media</label>
        <select id="logo_media_id" class="form-select @error('logo_media_id') is-invalid @enderror" name="logo_media_id">
            <option value="">None</option>
            @foreach ($mediaFiles as $mediaFile)
                <option value="{{ $mediaFile->id }}" @selected((string) $selectedLogo === (string) $mediaFile->id)>#{{ $mediaFile->id }} {{ $mediaFile->original_name }}</option>
            @endforeach
        </select>
        @error('logo_media_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="overview" class="form-label">Overview</label>
        <textarea id="overview" class="form-control @error('overview') is-invalid @enderror" name="overview" rows="4">{{ old('overview', $partnerProfile?->overview) }}</textarea>
        @error('overview')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="contact_email" class="form-label">Contact Email</label>
        <input id="contact_email" type="email" class="form-control @error('contact_email') is-invalid @enderror" name="contact_email" value="{{ old('contact_email', $partnerProfile?->contact_email) }}">
        @error('contact_email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="phone" class="form-label">Phone</label>
        <input id="phone" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $partnerProfile?->phone) }}">
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="country" class="form-label">Country</label>
        <input id="country" class="form-control @error('country') is-invalid @enderror" name="country" value="{{ old('country', $partnerProfile?->country) }}">
        @error('country')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="website" class="form-label">Website</label>
        <input id="website" type="url" class="form-control @error('website') is-invalid @enderror" name="website" value="{{ old('website', $partnerProfile?->website) }}" placeholder="https://example.com">
        @error('website')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="layout_template" class="form-label">Layout</label>
        <select id="layout_template" class="form-select @error('layout_template') is-invalid @enderror" name="layout_template" required>
            @foreach (['layout_1','layout_2','layout_3'] as $layout)
                <option value="{{ $layout }}" @selected(old('layout_template', $partnerProfile?->layout_template ?? 'layout_1') === $layout)>{{ $layout }}</option>
            @endforeach
        </select>
        @error('layout_template')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="approval_status" class="form-label">Status</label>
        <select id="approval_status" class="form-select @error('approval_status') is-invalid @enderror" name="approval_status" required>
            @foreach (['pending','approved','rejected','suspended'] as $status)
                <option value="{{ $status }}" @selected(old('approval_status', $partnerProfile?->approval_status ?? 'pending') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        @error('approval_status')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="feed_highlight_enabled" class="form-label">Feed Highlight</label>
        <select id="feed_highlight_enabled" class="form-select @error('feed_highlight_enabled') is-invalid @enderror" name="feed_highlight_enabled" required>
            <option value="1" @selected((string) old('feed_highlight_enabled', (int) ($partnerProfile?->feed_highlight_enabled ?? true)) === '1')>Enabled</option>
            <option value="0" @selected((string) old('feed_highlight_enabled', (int) ($partnerProfile?->feed_highlight_enabled ?? true)) === '0')>Disabled</option>
        </select>
        @error('feed_highlight_enabled')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
