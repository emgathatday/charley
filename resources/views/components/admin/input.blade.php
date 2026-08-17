@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'hint' => null,
    'fieldClass' => null,
    'labelClass' => null,
    'requiredClass' => 'req',
    'hintClass' => 'field-hint',
    'disabled' => false,
])

<div @class(['field', $fieldClass])>
    @if ($label)
        <label @class([$labelClass])>{{ $label }}@if ($required)<span class="{{ $requiredClass }}">*</span>@endif</label>
    @endif
    <input
        type="{{ $type }}"
        @if ($id) id="{{ $id }}" @endif
        @if ($name) name="{{ $name }}" @endif
        value="{{ $value }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($required) required @endif
        @if ($disabled) disabled @endif
        {{ $attributes }}
    >
    @if ($hint)
        <div class="{{ $hintClass }}">{{ $hint }}</div>
    @endif
</div>
