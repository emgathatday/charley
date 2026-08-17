@props([
    'items' => [],
    'barClass' => null,
])

@php
    $tabs = $items['tabs'] ?? $items;
    $wrapperClass = $barClass ?? ($items['bar_class'] ?? 'tab-bar');
@endphp

<div class="{{ $wrapperClass }}">
    @foreach ($tabs as $tab)
        @php
            $tabClass = trim(($tab['class'] ?? 'tab-btn') . (($tab['active'] ?? false) ? ' active' : ''));
            $countClass = $tab['count_class'] ?? 'tab-count';
            $extraAttributes = $tab['attributes'] ?? [];
        @endphp

        @if (($tab['type'] ?? 'link') === 'button')
            <button class="{{ $tabClass }}" type="{{ $tab['button_type'] ?? 'button' }}"
                @foreach ($extraAttributes as $name => $value)
                    @if ($value !== null && $value !== false) {{ $name }}="{{ $value }}" @endif
                @endforeach
            >{{ $tab['label'] ?? '' }}@if (array_key_exists('count', $tab)) <span class="{{ $countClass }}">{{ number_format((float) $tab['count']) }}</span>@endif</button>
        @else
            <a class="{{ $tabClass }}" href="{{ $tab['href'] ?? '#' }}"
                @foreach ($extraAttributes as $name => $value)
                    @if ($value !== null && $value !== false) {{ $name }}="{{ $value }}" @endif
                @endforeach
            >{{ $tab['label'] ?? '' }}@if (array_key_exists('count', $tab)) <span class="{{ $countClass }}">{{ number_format((float) $tab['count']) }}</span>@endif</a>
        @endif
    @endforeach
</div>
