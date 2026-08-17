@props([
    'label' => null,
    'name',
    'items' => [],
    'selected' => null,
    'variant' => 'choice-chip',
    'id' => null,
    'hint' => null,
])

@php
    $groupClass = match ($variant) {
        'checkbox-chip' => 'checkbox-chip-group',
        'choice-chip' => 'choice-chip-group',
        default => 'radio-group',
    };
    $itemClass = match ($variant) {
        'checkbox-chip' => 'checkbox-chip',
        'choice-chip' => 'choice-chip',
        default => 'radio-item',
    };
@endphp

<div class="field">
    @if ($label)
        <label>{{ $label }}</label>
    @endif
    <div class="{{ $groupClass }}" @if ($id) id="{{ $id }}" @endif>
        @foreach ($items as $value => $text)
            @php
                $itemValue = is_array($text) ? ($text['value'] ?? $value) : $value;
                $itemLabel = is_array($text) ? ($text['label'] ?? $itemValue) : $text;
                $itemAttributes = is_array($text) ? ($text['attributes'] ?? []) : [];
            @endphp
            <label class="{{ $itemClass }}">
                <input type="radio" name="{{ $name }}" value="{{ $itemValue }}" @checked((string) $selected === (string) $itemValue)
                    @foreach ($itemAttributes as $attr => $attrValue)
                        @if ($attrValue !== null && $attrValue !== false) {{ $attr }}="{{ $attrValue }}" @endif
                    @endforeach
                    {{ $attributes }}
                >
                @if ($variant === 'checkbox-chip')
                    <span class="checkbox-chip-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                @elseif ($variant === 'choice-chip')
                    <span class="choice-chip-dot"></span>
                @endif
                {{ $itemLabel }}
            </label>
        @endforeach
    </div>
    @if ($hint)
        <div class="field-hint">{{ $hint }}</div>
    @endif
</div>
