@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'hint' => null,
    'fieldClass' => null,
    'labelClass' => null,
    'requiredClass' => 'req',
    'hintClass' => 'field-hint',
])

<div @class(['field', $fieldClass])>
    @if ($label)
        <label @class([$labelClass])>{{ $label }}@if ($required)<span class="{{ $requiredClass }}">*</span>@endif</label>
    @endif
    <textarea
        @if ($id) id="{{ $id }}" @endif
        @if ($name) name="{{ $name }}" @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        {{ $attributes }}
    >{{ $value }}</textarea>
    @if ($hint)
        <div class="{{ $hintClass }}">{{ $hint }}</div>
    @endif
</div>
