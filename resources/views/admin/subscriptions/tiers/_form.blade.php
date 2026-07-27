@php
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
@endphp
<div class="form-grid">
    <div class="field-block">
        <label for="code">Code</label>
        <input class="form-input @error('code') is-invalid @enderror" type="text" id="code" name="code" value="{{ old('code', $subscriptionTier?->code) }}" placeholder="enterprise_custom" required>
        @error('code')<div class="field-error">{{ $message }}</div>@enderror
    </div>

    <div class="field-block">
        <label for="name">Internal name</label>
        <input class="form-input @error('name') is-invalid @enderror" type="text" id="name" name="name" value="{{ old('name', $subscriptionTier?->name) }}" placeholder="Enterprise Custom" required>
        @error('name')<div class="field-error">{{ $message }}</div>@enderror
    </div>

    <div class="field-block">
        <label for="display_name">Display name</label>
        <input class="form-input @error('display_name') is-invalid @enderror" type="text" id="display_name" name="display_name" value="{{ old('display_name', $subscriptionTier?->display_name) }}" placeholder="Enterprise Custom" required>
        @error('display_name')<div class="field-error">{{ $message }}</div>@enderror
    </div>

    <div class="field-block">
        <label for="monthly_price">Monthly price</label>
        <input class="form-input @error('monthly_price') is-invalid @enderror" type="number" id="monthly_price" name="monthly_price" value="{{ old('monthly_price', $subscriptionTier?->monthly_price) }}" min="0" step="0.01" required>
        @error('monthly_price')<div class="field-error">{{ $message }}</div>@enderror
    </div>

    <div class="field-block">
        <label for="billing_cycle">Billing cycle</label>
        <select class="form-input @error('billing_cycle') is-invalid @enderror" id="billing_cycle" name="billing_cycle" required>
            @foreach (['monthly' => 'Monthly', 'yearly' => 'Yearly', 'custom' => 'Custom'] as $value => $label)
                <option value="{{ $value }}" @selected(old('billing_cycle', $subscriptionTier?->billing_cycle ?? 'monthly') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('billing_cycle')<div class="field-error">{{ $message }}</div>@enderror
    </div>

    <div class="field-block">
        <label for="duration_days">Duration days</label>
        <input class="form-input @error('duration_days') is-invalid @enderror" type="number" id="duration_days" name="duration_days" value="{{ old('duration_days', $subscriptionTier?->duration_days) }}" min="1" placeholder="Auto">
        @error('duration_days')<div class="field-error">{{ $message }}</div>@enderror
    </div>

    <div class="field-block">
        <label for="sort_order">Sort order</label>
        <input class="form-input @error('sort_order') is-invalid @enderror" type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $subscriptionTier?->sort_order ?? 0) }}" min="0" required>
        @error('sort_order')<div class="field-error">{{ $message }}</div>@enderror
    </div>

    <div class="field-block">
        <label for="is_public">Visibility</label>
        <select class="form-input @error('is_public') is-invalid @enderror" id="is_public" name="is_public" required>
            <option value="1" @selected((string) old('is_public', (int) ($subscriptionTier?->is_public ?? true)) === '1')>Public</option>
            <option value="0" @selected((string) old('is_public', (int) ($subscriptionTier?->is_public ?? true)) === '0')>Private</option>
        </select>
        @error('is_public')<div class="field-error">{{ $message }}</div>@enderror
    </div>

    <div class="field-block">
        <label for="is_active">Status</label>
        <select class="form-input @error('is_active') is-invalid @enderror" id="is_active" name="is_active" required>
            <option value="1" @selected((string) old('is_active', (int) ($subscriptionTier?->is_active ?? true)) === '1')>Active</option>
            <option value="0" @selected((string) old('is_active', (int) ($subscriptionTier?->is_active ?? true)) === '0')>Inactive</option>
        </select>
        @error('is_active')<div class="field-error">{{ $message }}</div>@enderror
    </div>

    <div class="field-block span-2">
        <label for="description">Description</label>
        <textarea class="form-input @error('description') is-invalid @enderror" id="description" name="description" rows="4" placeholder="Benefits, approval notes, and partner-facing package description">{{ old('description', $subscriptionTier?->description) }}</textarea>
        @error('description')<div class="field-error">{{ $message }}</div>@enderror
    </div>
</div>
@php
    $subscriptionPermissions = $subscriptionPermissions ?? collect();
    $assignedTierPermissions = $assignedTierPermissions ?? collect();
    $formatPermissionValue = function ($value) {
        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return $value;
    };
@endphp

<div class="permission-matrix">
    <div class="permission-matrix-head">
        <div>
            <h3>Permission assignment</h3>
            <p>Enable active subscription permissions for this tier and set values by permission type.</p>
        </div>
        <span>{{ number_format($subscriptionPermissions->count()) }} active permissions</span>
    </div>

    @if ($subscriptionPermissions->isEmpty())
        <div class="permission-empty">No active subscription permissions are available.</div>
    @else
        <div class="permission-table-wrap">
            <table class="permission-table">
                <thead>
                    <tr>
                        <th>Enable</th>
                        <th>Permission</th>
                        <th>Module</th>
                        <th>Type</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($subscriptionPermissions as $permission)
                        @php
                            $assigned = $assignedTierPermissions->get($permission->id);
                            $enabled = (string) old("permissions.{$permission->id}.enabled", $assigned ? '1' : '0') === '1';
                            $storedValue = $assigned ? $assigned->value : $permission->default_value;
                            $value = old("permissions.{$permission->id}.value", $formatPermissionValue($storedValue));
                            $permissionEnabledErrorKey = "permissions.{$permission->id}.enabled";
                            $permissionValueErrorKey = "permissions.{$permission->id}.value";
                            $permissionValueInvalid = $errors->has($permissionValueErrorKey) ? 'is-invalid' : '';
                        @endphp
                        <tr>
                            <td>
                                <input type="hidden" name="permissions[{{ $permission->id }}][enabled]" value="0">
                                <label class="switch-control" for="permission_{{ $permission->id }}_enabled">
                                    <input type="checkbox" id="permission_{{ $permission->id }}_enabled" name="permissions[{{ $permission->id }}][enabled]" value="1" @checked($enabled)>
                                    <span></span>
                                </label>
                                @if ($errors->has($permissionEnabledErrorKey))<div class="field-error">{{ $errors->first($permissionEnabledErrorKey) }}</div>@endif
                            </td>
                            <td>
                                <div class="permission-name">{{ $permission->name }}</div>
                                <div class="permission-key"><code>{{ $permission->key }}</code></div>
                                @if ($permission->description)
                                    <div class="permission-description">{{ $permission->description }}</div>
                                @endif
                            </td>
                            <td>{{ $permission->module ?: 'General' }}</td>
                            <td><span class="permission-type">{{ $permission->value_type }}</span></td>
                            <td>
                                @if ($permission->value_type === 'boolean')
                                    <input type="hidden" name="permissions[{{ $permission->id }}][value]" value="1">
                                    <span class="permission-static-value">Enabled means true</span>
                                @elseif ($permission->value_type === 'integer')
                                    <input class="form-input permission-value-input {{ $permissionValueInvalid }}" type="number" name="permissions[{{ $permission->id }}][value]" value="{{ $value }}" step="1">
                                @elseif ($permission->value_type === 'json')
                                    <textarea class="form-input permission-value-input {{ $permissionValueInvalid }}" name="permissions[{{ $permission->id }}][value]" rows="3" placeholder='{"limit": 10}'>{{ $value }}</textarea>
                                @else
                                    <input class="form-input permission-value-input {{ $permissionValueInvalid }}" type="text" name="permissions[{{ $permission->id }}][value]" value="{{ $value }}">
                                @endif
                                @if ($errors->has($permissionValueErrorKey))<div class="field-error">{{ $errors->first($permissionValueErrorKey) }}</div>@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<style>
.subscription-form-page .permission-matrix { margin: 0 24px 24px; border: 1px solid var(--line, #E2E8F0); border-radius: 14px; overflow: hidden; }
.subscription-form-page .permission-matrix-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; padding: 18px 20px; background: #F8FAFC; border-bottom: 1px solid var(--line, #E2E8F0); }
.subscription-form-page .permission-matrix-head h3 { margin: 0; font-size: 16px; color: var(--ink, #0F172A); }
.subscription-form-page .permission-matrix-head p, .subscription-form-page .permission-matrix-head span { margin: 4px 0 0; font-size: 13px; color: var(--ink-faint, #64748B); }
.subscription-form-page .permission-empty { padding: 22px; color: var(--ink-faint, #64748B); }
.subscription-form-page .permission-table-wrap { overflow-x: auto; }
.subscription-form-page .permission-table { width: 100%; min-width: 900px; border-collapse: collapse; }
.subscription-form-page .permission-table th { text-align: left; padding: 12px 14px; font-size: 11px; text-transform: uppercase; color: var(--ink-faint, #64748B); border-bottom: 1px solid var(--line, #E2E8F0); background: #fff; }
.subscription-form-page .permission-table td { vertical-align: top; padding: 14px; border-bottom: 1px solid var(--line, #E2E8F0); font-size: 13px; color: var(--ink-soft, #334155); }
.subscription-form-page .permission-table tr:last-child td { border-bottom: 0; }
.subscription-form-page .permission-name { font-weight: 700; color: var(--ink, #0F172A); }
.subscription-form-page .permission-key, .subscription-form-page .permission-description { margin-top: 4px; color: var(--ink-faint, #64748B); }
.subscription-form-page .permission-type { display: inline-flex; align-items: center; min-height: 24px; padding: 2px 8px; border-radius: 999px; background: #F1F5F9; color: #475569; font-size: 12px; font-weight: 700; }
.subscription-form-page .permission-value-input { min-width: 220px; }
.subscription-form-page .permission-static-value { color: var(--ink-faint, #64748B); font-size: 12px; }
.subscription-form-page .switch-control { display: inline-flex; cursor: pointer; }
.subscription-form-page .switch-control input { position: absolute; opacity: 0; pointer-events: none; }
.subscription-form-page .switch-control span { width: 42px; height: 24px; border-radius: 999px; background: #CBD5E1; position: relative; transition: background .18s ease; }
.subscription-form-page .switch-control span::after { content: ''; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(15, 23, 42, .25); transition: transform .18s ease; }
.subscription-form-page .switch-control input:checked + span { background: #4F8DFD; }
.subscription-form-page .switch-control input:checked + span::after { transform: translateX(18px); }
@media (max-width: 768px) { .subscription-form-page .permission-matrix { margin: 0 18px 18px; } }
</style>