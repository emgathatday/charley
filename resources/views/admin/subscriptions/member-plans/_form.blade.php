<div class="card-body">
    <div class="row g-3">
        <div class="col-md-6">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $memberSubscriptionPlan?->name) }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="display_name" class="form-label">Display Name</label>
            <input type="text" class="form-control @error('display_name') is-invalid @enderror" id="display_name" name="display_name" value="{{ old('display_name', $memberSubscriptionPlan?->display_name) }}" required>
            @error('display_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label for="monthly_price" class="form-label">Monthly Price</label>
            <input type="number" class="form-control @error('monthly_price') is-invalid @enderror" id="monthly_price" name="monthly_price" value="{{ old('monthly_price', $memberSubscriptionPlan?->monthly_price) }}" min="0" step="0.01" required>
            @error('monthly_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label for="ai_monthly_limit" class="form-label">AI Monthly Limit</label>
            <input type="number" class="form-control @error('ai_monthly_limit') is-invalid @enderror" id="ai_monthly_limit" name="ai_monthly_limit" value="{{ old('ai_monthly_limit', $memberSubscriptionPlan?->ai_monthly_limit ?? -1) }}" min="-1" required>
            @error('ai_monthly_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label for="is_active" class="form-label">Status</label>
            <select class="form-select @error('is_active') is-invalid @enderror" id="is_active" name="is_active" required>
                <option value="1" @selected((string) old('is_active', (int) ($memberSubscriptionPlan?->is_active ?? true)) === '1')>Active</option>
                <option value="0" @selected((string) old('is_active', (int) ($memberSubscriptionPlan?->is_active ?? true)) === '0')>Inactive</option>
            </select>
            @error('is_active')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label for="features" class="form-label">Features</label>
            <input type="text" class="form-control @error('features') is-invalid @enderror" id="features" name="features" value="{{ old('features', is_array($memberSubscriptionPlan?->features) ? implode(', ', $memberSubscriptionPlan->features) : '') }}" placeholder="Comma separated features">
            @error('features')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
