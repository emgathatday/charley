@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'options' => [],
    'selected' => null,
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
    <select @if ($id) id="{{ $id }}" @endif @if ($name) name="{{ $name }}" @endif @if ($required) required @endif @if ($disabled) disabled @endif {{ $attributes }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $text }}</option>
        @endforeach
    </select>
    @if ($hint)
        <div class="{{ $hintClass }}">{{ $hint }}</div>
    @endif
</div>
