@props([
    'label' => null,
    'name',
    'items' => [],
    'selected' => [],
    'variant' => 'chip',
    'id' => null,
    'hint' => null,
    'fieldClass' => null,
    'labelClass' => null,
    'hintClass' => 'field-hint',
])

@php
    $groupClass = $variant === 'chip' ? 'checkbox-chip-group' : 'checkbox-group';
    $itemClass = $variant === 'chip' ? 'checkbox-chip' : 'checkbox-item';
    $selectedValues = collect($selected)->map(fn ($value) => (string) $value)->all();
@endphp

<div @class(['field', $fieldClass])>
    @if ($label)
        <label @class([$labelClass])>{{ $label }}</label>
    @endif
    <div class="{{ $groupClass }}" @if ($id) id="{{ $id }}" @endif>
        @foreach ($items as $value => $text)
            @php
                $itemValue = is_array($text) ? ($text['value'] ?? $value) : $value;
                $itemLabel = is_array($text) ? ($text['label'] ?? $itemValue) : $text;
            @endphp
            <label class="{{ $itemClass }}">
                <input type="checkbox" name="{{ $name }}" value="{{ $itemValue }}" @checked(in_array((string) $itemValue, $selectedValues, true)) {{ $attributes }}>
                @if ($variant === 'chip')
                    <span class="checkbox-chip-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                @endif
                {{ $itemLabel }}
            </label>
        @endforeach
    </div>
    @if ($hint)
        <div class="{{ $hintClass }}">{{ $hint }}</div>
    @endif
</div>
