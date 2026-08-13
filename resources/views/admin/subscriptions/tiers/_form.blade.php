@php
    $subscriptionTier ??= null;
    $isEdit = $subscriptionTier !== null;
    $subscriptionPermissions = $subscriptionPermissions ?? collect();
    $assignedTierPermissions = $assignedTierPermissions ?? collect();
    $formatPermissionValue = function ($value) {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES);
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        return $value;
    };
    $permissionBadgeClass = fn (string $type): string => match ($type) {
        'integer' => 'sub-badge-warning',
        'json' => 'sub-badge-muted',
        default => 'sub-badge-success',
    };
@endphp

<div class="form-card">
    <div class="form-card-header">
        <div class="form-card-icon blue"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-subscription-and-billing"></use></svg></div>
        <div>
            <div class="form-card-title">Thông tin gói thành viên</div>
            <div class="form-card-sub">Source: `subscription_tiers`; Golden/Diamond/Platinum là data record, không phải enum hardcode.</div>
        </div>
    </div>
    <div class="form-card-body">
        <div class="row row-cols-1 row-cols-md-2 g-3">
            <div class="col"><div class="field"><label for="code">Code <span class="req">*</span></label><input type="text" id="code" name="code" value="{{ old('code', $subscriptionTier?->code) }}" placeholder="diamond" @class(['is-invalid' => $errors->has('code')]) required>@error('code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div></div>
            <div class="col"><div class="field"><label for="name">Name <span class="req">*</span></label><input type="text" id="name" name="name" value="{{ old('name', $subscriptionTier?->name) }}" placeholder="Diamond" @class(['is-invalid' => $errors->has('name')]) required>@error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div></div>
            <div class="col"><div class="field"><label for="display_name">Display name <span class="req">*</span></label><input type="text" id="display_name" name="display_name" value="{{ old('display_name', $subscriptionTier?->display_name) }}" placeholder="Diamond Membership" @class(['is-invalid' => $errors->has('display_name')]) required>@error('display_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div></div>
            <div class="col"><div class="field"><label for="monthly_price">Giá theo tháng <span class="req">*</span></label><input type="number" id="monthly_price" name="monthly_price" value="{{ old('monthly_price', $subscriptionTier?->monthly_price) }}" min="0" step="0.01" @class(['is-invalid' => $errors->has('monthly_price')]) required>@error('monthly_price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div></div>
            <div class="col"><div class="field"><label for="billing_cycle">Chu kỳ thanh toán</label><select id="billing_cycle" name="billing_cycle" @class(['is-invalid' => $errors->has('billing_cycle')]) required>@foreach (['monthly', 'yearly', 'custom'] as $cycle)<option value="{{ $cycle }}" @selected(old('billing_cycle', $subscriptionTier?->billing_cycle ?? 'monthly') === $cycle)>{{ $cycle }}</option>@endforeach</select>@error('billing_cycle')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div></div>
            <div class="col"><div class="field"><label for="duration_days">Thời hạn hiệu lực</label><input type="number" id="duration_days" name="duration_days" value="{{ old('duration_days', $subscriptionTier?->duration_days) }}" min="1" placeholder="Auto" @class(['is-invalid' => $errors->has('duration_days')])><div class="field-hint">Có thể để trống nếu cycle quyết định thời hạn.</div>@error('duration_days')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div></div>
            <div class="col"><div class="field"><label for="sort_order">Thứ tự hiển thị</label><input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $subscriptionTier?->sort_order ?? 0) }}" min="0" @class(['is-invalid' => $errors->has('sort_order')]) required>@error('sort_order')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div></div>
            <div class="col">
                <div class="field">
                    <label>Trạng thái hiển thị</label>
                    <div class="subscription-switch-stack">
                        <div class="switch-row">
                            <div><div class="sw-label">is_public</div><div class="sw-desc">Partner có thể tự đăng ký gói này</div></div>
                            <input type="hidden" name="is_public" value="0">
                            <label class="switch" for="is_public_switch"><input id="is_public_switch" type="checkbox" name="is_public" value="1" @checked((string) old('is_public', (int) ($subscriptionTier?->is_public ?? true)) === '1')><span class="slider"></span></label>
                        </div>
                        <div class="switch-row">
                            <div><div class="sw-label">is_active</div><div class="sw-desc">Gói có thể được chọn trong admin</div></div>
                            <input type="hidden" name="is_active" value="0">
                            <label class="switch" for="is_active_switch"><input id="is_active_switch" type="checkbox" name="is_active" value="1" @checked((string) old('is_active', (int) ($subscriptionTier?->is_active ?? true)) === '1')><span class="slider"></span></label>
                        </div>
                    </div>
                    @error('is_public')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @error('is_active')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col"><div class="field field-full"><label for="description">Description</label><textarea id="description" name="description" @class(['is-invalid' => $errors->has('description')])>{{ old('description', $subscriptionTier?->description) }}</textarea>@error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div></div>
        </div>
    </div>
</div>

<div class="form-card">
    <div class="form-card-header">
        <div class="form-card-icon purple"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-platform-settings-ai-assistant"></use></svg></div>
        <div><div class="form-card-title">Permissions</div><div class="form-card-sub">Mỗi dòng map `subscription_permissions` sang `subscription_tier_permissions.value`.</div></div>
    </div>
    <div class="form-card-body">
        @if ($subscriptionPermissions->isEmpty())
            <div class="text-muted py-3">No active subscription permissions are available.</div>
        @else
            <div class="table-scroll">
                <table class="subscription-permission-table">
                    <thead><tr><th>On/Off</th><th>Name</th><th>Module</th><th>Type</th><th>Value</th></tr></thead>
                    <tbody>
                        @foreach ($subscriptionPermissions as $permission)
                            @php
                                $assigned = $assignedTierPermissions->get($permission->id);
                                $enabled = (string) old("permissions.{$permission->id}.enabled", $assigned ? '1' : '0') === '1';
                                $storedValue = $assigned ? $assigned->value : $permission->default_value;
                                $value = old("permissions.{$permission->id}.value", $formatPermissionValue($storedValue));
                                $permissionEnabledErrorKey = "permissions.{$permission->id}.enabled";
                                $permissionValueErrorKey = "permissions.{$permission->id}.value";
                            @endphp
                            <tr>
                                <td>
                                    <input type="hidden" name="permissions[{{ $permission->id }}][enabled]" value="0">
                                    <label class="switch" for="permission_{{ $permission->id }}_enabled"><input type="checkbox" id="permission_{{ $permission->id }}_enabled" name="permissions[{{ $permission->id }}][enabled]" value="1" @checked($enabled)><span class="slider"></span></label>
                                    @if ($errors->has($permissionEnabledErrorKey))<div class="invalid-feedback d-block">{{ $errors->first($permissionEnabledErrorKey) }}</div>@endif
                                </td>
                                <td><strong>{{ $permission->key }}</strong><span>{{ $permission->name }}{{ $permission->description ? ' · '.Str::limit($permission->description, 80) : '' }}</span></td>
                                <td>{{ $permission->module ?: 'General' }}</td>
                                <td><span class="badge {{ $permissionBadgeClass($permission->value_type) }}">{{ $permission->value_type }}</span></td>
                                <td>
                                    @if ($permission->value_type === 'boolean')
                                        <input type="hidden" name="permissions[{{ $permission->id }}][value]" value="1">
                                        <select class="subscription-value-select" disabled><option selected>true</option><option>false</option></select>
                                    @elseif ($permission->value_type === 'integer')
                                        <input class="subscription-quota-input @if($errors->has($permissionValueErrorKey)) is-invalid @endif" type="number" name="permissions[{{ $permission->id }}][value]" value="{{ $value }}" step="1">
                                    @elseif ($permission->value_type === 'json')
                                        <input class="subscription-json-input @if($errors->has($permissionValueErrorKey)) is-invalid @endif" type="text" name="permissions[{{ $permission->id }}][value]" value="{{ $value }}" placeholder='{"monthly_limit":8}'>
                                    @else
                                        <input class="subscription-json-input @if($errors->has($permissionValueErrorKey)) is-invalid @endif" type="text" name="permissions[{{ $permission->id }}][value]" value="{{ $value }}">
                                    @endif
                                    @if ($errors->has($permissionValueErrorKey))<div class="invalid-feedback d-block">{{ $errors->first($permissionValueErrorKey) }}</div>@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>