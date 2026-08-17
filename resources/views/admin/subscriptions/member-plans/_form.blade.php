@php($errors = $errors ?? new \Illuminate\Support\ViewErrorBag)
<div class="form-grid">
    <div class="field-block">
        <label for="name">Name</label>
        <input class="form-input @error('name') is-invalid @enderror" type="text" id="name" name="name" value="{{ old('name', $memberSubscriptionPlan?->name) }}" required>
        @error('name')<div class="field-error">{{ $message }}</div>@enderror
    </div>

    <div class="field-block">
        <label for="display_name">Display name</label>
        <input class="form-input @error('display_name') is-invalid @enderror" type="text" id="display_name" name="display_name" value="{{ old('display_name', $memberSubscriptionPlan?->display_name) }}" required>
        @error('display_name')<div class="field-error">{{ $message }}</div>@enderror
    </div>

    <div class="field-block">
        <label for="monthly_price">Monthly price</label>
        <input class="form-input @error('monthly_price') is-invalid @enderror" type="number" id="monthly_price" name="monthly_price" value="{{ old('monthly_price', $memberSubscriptionPlan?->monthly_price) }}" min="0" step="0.01" required>
        @error('monthly_price')<div class="field-error">{{ $message }}</div>@enderror
    </div>

    <div class="field-block">
        <label for="ai_monthly_limit">AI monthly limit</label>
        <input class="form-input @error('ai_monthly_limit') is-invalid @enderror" type="number" id="ai_monthly_limit" name="ai_monthly_limit" value="{{ old('ai_monthly_limit', $memberSubscriptionPlan?->ai_monthly_limit ?? -1) }}" min="-1" required>
        @error('ai_monthly_limit')<div class="field-error">{{ $message }}</div>@enderror
    </div>

    <div class="field-block">
        <label for="is_active">Status</label>
        <select class="form-input @error('is_active') is-invalid @enderror" id="is_active" name="is_active" required>
            <option value="1" @selected((string) old('is_active', (int) ($memberSubscriptionPlan?->is_active ?? true)) === '1')>Active</option>
            <option value="0" @selected((string) old('is_active', (int) ($memberSubscriptionPlan?->is_active ?? true)) === '0')>Inactive</option>
        </select>
        @error('is_active')<div class="field-error">{{ $message }}</div>@enderror
    </div>

    <div class="field-block span-2">
        <label for="features">Features</label>
        <textarea class="form-input @error('features') is-invalid @enderror" id="features" name="features" rows="4" placeholder="Comma separated features">{{ old('features', is_array($memberSubscriptionPlan?->features) ? implode(', ', $memberSubscriptionPlan->features) : '') }}</textarea>
        @error('features')<div class="field-error">{{ $message }}</div>@enderror
    </div>
</div>