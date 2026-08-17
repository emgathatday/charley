@props([
    'label',
    'description' => null,
    'name' => null,
    'value' => '1',
    'checked' => false,
])

<div class="switch-row">
    <div>
        <div class="sw-label">{{ $label }}</div>
        @if ($description)
            <div class="sw-desc">{{ $description }}</div>
        @endif
    </div>
    <label class="switch">
        <input type="checkbox" @if ($name) name="{{ $name }}" @endif value="{{ $value }}" @checked($checked) {{ $attributes }}>
        <span class="slider"></span>
    </label>
</div>
