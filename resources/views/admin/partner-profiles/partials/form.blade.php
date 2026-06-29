@php($selectedUser = old('user_id', $partnerProfile?->user_id))
@php($selectedPlantType = old('plant_type_id', $partnerProfile?->plant_type_id))
@php($selectedLogo = old('logo_media_id', $partnerProfile?->logo_media_id))
<div class="row">
    <div class="col-md-6">
        <div class="form-group"><label>User</label><select class="form-control @error('user_id') is-invalid @enderror" name="user_id"><option value="">Select user</option>@foreach ($users as $user)<option value="{{ $user->id }}" @selected((string) $selectedUser === (string) $user->id)>{{ $user->name }} ({{ $user->email }})</option>@endforeach</select>@error('user_id')<span class="invalid-feedback">{{ $message }}</span>@enderror</div>
    </div>
    <div class="col-md-6">
        <div class="form-group"><label>Company Name</label><input class="form-control @error('company_name') is-invalid @enderror" name="company_name" value="{{ old('company_name', $partnerProfile?->company_name) }}">@error('company_name')<span class="invalid-feedback">{{ $message }}</span>@enderror</div>
    </div>
</div>
<div class="row">
    <div class="col-md-4"><div class="form-group"><label>Partner Tier</label><select class="form-control @error('partner_tier') is-invalid @enderror" name="partner_tier"><option value="">None</option>@foreach (['gold','diamond','platinum'] as $tier)<option value="{{ $tier }}" @selected(old('partner_tier', $partnerProfile?->partner_tier) === $tier)>{{ ucfirst($tier) }}</option>@endforeach</select>@error('partner_tier')<span class="invalid-feedback">{{ $message }}</span>@enderror</div></div>
    <div class="col-md-4"><div class="form-group"><label>Plant Type</label><select class="form-control @error('plant_type_id') is-invalid @enderror" name="plant_type_id"><option value="">None</option>@foreach ($plantTypes as $plantType)<option value="{{ $plantType->id }}" @selected((string) $selectedPlantType === (string) $plantType->id)>{{ $plantType->name }}</option>@endforeach</select>@error('plant_type_id')<span class="invalid-feedback">{{ $message }}</span>@enderror</div></div>
    <div class="col-md-4"><div class="form-group"><label>Logo Media</label><select class="form-control @error('logo_media_id') is-invalid @enderror" name="logo_media_id"><option value="">None</option>@foreach ($mediaFiles as $mediaFile)<option value="{{ $mediaFile->id }}" @selected((string) $selectedLogo === (string) $mediaFile->id)>#{{ $mediaFile->id }} {{ $mediaFile->original_name }}</option>@endforeach</select>@error('logo_media_id')<span class="invalid-feedback">{{ $message }}</span>@enderror</div></div>
</div>
<div class="form-group"><label>Overview</label><textarea class="form-control @error('overview') is-invalid @enderror" name="overview" rows="4">{{ old('overview', $partnerProfile?->overview) }}</textarea>@error('overview')<span class="invalid-feedback">{{ $message }}</span>@enderror</div>
<div class="row">
    <div class="col-md-4"><div class="form-group"><label>Contact Email</label><input class="form-control @error('contact_email') is-invalid @enderror" name="contact_email" value="{{ old('contact_email', $partnerProfile?->contact_email) }}">@error('contact_email')<span class="invalid-feedback">{{ $message }}</span>@enderror</div></div>
    <div class="col-md-4"><div class="form-group"><label>Phone</label><input class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $partnerProfile?->phone) }}">@error('phone')<span class="invalid-feedback">{{ $message }}</span>@enderror</div></div>
    <div class="col-md-4"><div class="form-group"><label>Country</label><input class="form-control @error('country') is-invalid @enderror" name="country" value="{{ old('country', $partnerProfile?->country) }}">@error('country')<span class="invalid-feedback">{{ $message }}</span>@enderror</div></div>
</div>
<div class="row">
    <div class="col-md-4"><div class="form-group"><label>Website</label><input class="form-control @error('website') is-invalid @enderror" name="website" value="{{ old('website', $partnerProfile?->website) }}">@error('website')<span class="invalid-feedback">{{ $message }}</span>@enderror</div></div>
    <div class="col-md-4"><div class="form-group"><label>Layout</label><select class="form-control" name="layout_template">@foreach (['layout_1','layout_2','layout_3'] as $layout)<option value="{{ $layout }}" @selected(old('layout_template', $partnerProfile?->layout_template ?? 'layout_1') === $layout)>{{ $layout }}</option>@endforeach</select></div></div>
    <div class="col-md-4"><div class="form-group"><label>Status</label><select class="form-control" name="approval_status">@foreach (['pending','approved','rejected','suspended'] as $status)<option value="{{ $status }}" @selected(old('approval_status', $partnerProfile?->approval_status ?? 'pending') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div></div>
</div>
<div class="form-group"><label>Feed Highlight</label><select class="form-control" name="feed_highlight_enabled"><option value="1" @selected((string) old('feed_highlight_enabled', (int) ($partnerProfile?->feed_highlight_enabled ?? true)) === '1')>Enabled</option><option value="0" @selected((string) old('feed_highlight_enabled', (int) ($partnerProfile?->feed_highlight_enabled ?? true)) === '0')>Disabled</option></select></div>
