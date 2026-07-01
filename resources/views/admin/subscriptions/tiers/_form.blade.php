<div class="card-body">
    <div class="row g-3">
        <div class="col-md-6">
            <label for="name" class="form-label">Name</label>
            <select class="form-select @error('name') is-invalid @enderror" id="name" name="name" required>
                @foreach (['gold', 'diamond', 'platinum'] as $name)
                    <option value="{{ $name }}" @selected(old('name', $subscriptionTier?->name) === $name)>{{ ucfirst($name) }}</option>
                @endforeach
            </select>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="monthly_price" class="form-label">Monthly Price</label>
            <input type="number" class="form-control @error('monthly_price') is-invalid @enderror" id="monthly_price" name="monthly_price" value="{{ old('monthly_price', $subscriptionTier?->monthly_price) }}" min="0" step="0.01" required>
            @error('monthly_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label for="ai_monthly_limit" class="form-label">AI Monthly Limit</label>
            <input type="number" class="form-control @error('ai_monthly_limit') is-invalid @enderror" id="ai_monthly_limit" name="ai_monthly_limit" value="{{ old('ai_monthly_limit', $subscriptionTier?->ai_monthly_limit ?? -1) }}" min="-1" required>
            @error('ai_monthly_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label for="announcement_frequency" class="form-label">Announcement Frequency</label>
            <select class="form-select @error('announcement_frequency') is-invalid @enderror" id="announcement_frequency" name="announcement_frequency" required>
                <option value="weekly" @selected(old('announcement_frequency', $subscriptionTier?->announcement_frequency) === 'weekly')>Weekly</option>
                <option value="monthly" @selected(old('announcement_frequency', $subscriptionTier?->announcement_frequency ?? 'monthly') === 'monthly')>Monthly</option>
            </select>
            @error('announcement_frequency')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label for="announcement_limit" class="form-label">Announcement Limit</label>
            <input type="number" class="form-control @error('announcement_limit') is-invalid @enderror" id="announcement_limit" name="announcement_limit" value="{{ old('announcement_limit', $subscriptionTier?->announcement_limit ?? 0) }}" min="0" required>
            @error('announcement_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        @foreach (['can_host_webinar' => 'Can Host Webinar', 'can_initiate_message' => 'Can Initiate Message', 'can_create_poll' => 'Can Create Poll', 'can_publish_events' => 'Can Publish Events'] as $field => $label)
            <div class="col-md-3">
                <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                <select class="form-select @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" required>
                    <option value="0" @selected((string) old($field, (int) ($subscriptionTier?->{$field} ?? false)) === '0')>No</option>
                    <option value="1" @selected((string) old($field, (int) ($subscriptionTier?->{$field} ?? false)) === '1')>Yes</option>
                </select>
                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        @endforeach
        <div class="col-md-4">
            <label for="is_active" class="form-label">Status</label>
            <select class="form-select @error('is_active') is-invalid @enderror" id="is_active" name="is_active" required>
                <option value="1" @selected((string) old('is_active', (int) ($subscriptionTier?->is_active ?? true)) === '1')>Active</option>
                <option value="0" @selected((string) old('is_active', (int) ($subscriptionTier?->is_active ?? true)) === '0')>Inactive</option>
            </select>
            @error('is_active')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
