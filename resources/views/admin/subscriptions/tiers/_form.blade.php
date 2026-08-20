@php
    $subscriptionTier ??= null;
    $subscriptionPermissions = $subscriptionPermissions ?? collect();
    $assignedTierPermissions = $assignedTierPermissions ?? collect();
    $tierFormFields = $tierFormFields ?? [];
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
        <div class="form-card-icon blue"><x-admin.icon name="billing" /></div>
        <div>
            <div class="form-card-title">Tier details</div>
            <div class="form-card-sub">Subscription tiers are dynamic records; labels are stored data, not fixed enums.</div>
        </div>
    </div>
    <div class="form-card-body">
        <div class="row row-cols-1 row-cols-md-2 g-3">
            @foreach ($tierFormFields as $field)
                @php
                    $fieldValue = old($field['name'], data_get($subscriptionTier, $field['name'], $field['default'] ?? null));
                    $fieldType = $field['type'] ?? 'text';
                    $fieldAttributes = $field['attributes'] ?? [];
                @endphp
                <div class="col">
                    @if ($fieldType === 'select')
                        <x-admin.select
                            :label="$field['label']"
                            :name="$field['name']"
                            :options="$field['options']"
                            :selected="$fieldValue"
                            :required="$field['required'] ?? false"
                            :hint="$field['hint'] ?? null"
                        />
                    @else
                        <x-admin.input
                            :label="$field['label']"
                            :name="$field['name']"
                            :type="$fieldType"
                            :value="$fieldValue"
                            :placeholder="$field['placeholder'] ?? null"
                            :required="$field['required'] ?? false"
                            :hint="$field['hint'] ?? null"
                            min="{{ $fieldAttributes['min'] ?? '' }}"
                            step="{{ $fieldAttributes['step'] ?? '' }}"
                        />
                    @endif
                </div>
            @endforeach

            <div class="col">
                <div class="field">
                    <label>Visibility and status</label>
                    <div class="subscription-switch-stack">
                        <div class="switch-row">
                            <div><div class="sw-label">Public tier</div><div class="sw-desc">Partners can select this tier when subscribing.</div></div>
                            <input type="hidden" name="is_public" value="0">
                            <label class="switch" for="is_public_switch"><input id="is_public_switch" type="checkbox" name="is_public" value="1" @checked((string) old('is_public', (int) ($subscriptionTier?->is_public ?? true)) === '1')><span class="slider"></span></label>
                        </div>
                        <div class="switch-row">
                            <div><div class="sw-label">Active tier</div><div class="sw-desc">Admins can assign this tier to partner subscriptions.</div></div>
                            <input type="hidden" name="is_active" value="0">
                            <label class="switch" for="is_active_switch"><input id="is_active_switch" type="checkbox" name="is_active" value="1" @checked((string) old('is_active', (int) ($subscriptionTier?->is_active ?? true)) === '1')><span class="slider"></span></label>
                        </div>
                    </div>
                    @error('is_public')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @error('is_active')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col">
                <x-admin.textarea
                    label="Description"
                    name="description"
                    :value="old('description', $subscriptionTier?->description)"
                    field-class="field field-full"
                />
            </div>
        </div>
    </div>
</div>

<div class="form-card">
    <div class="form-card-header">
        <div class="form-card-icon purple"><x-admin.icon name="settings-2" /></div>
        <div><div class="form-card-title">Permissions</div><div class="form-card-sub">Each row maps a subscription permission to this tier's stored value.</div></div>
    </div>
    <div class="form-card-body">
        @if ($subscriptionPermissions->isEmpty())
            <div class="text-muted py-3">No active subscription permissions are available.</div>
        @else
            <div class="table-scroll">
                <table class="subscription-permission-table">
                    <thead><tr><th>Enabled</th><th>Permission</th><th>Module</th><th>Type</th><th>Value</th></tr></thead>
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
                                <td><strong>{{ $permission->key }}</strong><span>{{ $permission->name }}{{ $permission->description ? ' / '.Str::limit($permission->description, 80) : '' }}</span></td>
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
