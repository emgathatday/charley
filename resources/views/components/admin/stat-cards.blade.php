@props([
    'items' => [],
    'rowClass' => null,
])

@php
    $cards = $items['cards'] ?? $items;
    $wrapperClass = $rowClass ?? ($items['row_class'] ?? 'row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-3');
@endphp

<div class="{{ $wrapperClass }}">
    @foreach ($cards as $card)
        <div class="col">
            <div class="stat-card {{ $card['class'] ?? '' }}">
                @if (! empty($card['icon']))
                    @php
                        $iconClass = trim('stat-icon ' . ($card['icon_class'] ?? ''));
                    @endphp
                    @if (! empty($card['icon_wrap']))
                        <div class="{{ $card['icon_wrap'] }}"><div class="{{ $iconClass }}"><x-admin.icon :name="$card['icon']" /></div></div>
                    @else
                        <div class="{{ $iconClass }}"><x-admin.icon :name="$card['icon']" /></div>
                    @endif
                    <div class="stat-value">{{ $card['value'] ?? '' }}</div>
                    <div class="stat-label">{{ $card['label'] ?? '' }}</div>
                    <div class="stat-sub">{{ $card['sub'] ?? '' }}</div>
                @else
                    <div class="stat-label">{{ $card['label'] ?? '' }}</div>
                    <div class="stat-value">{{ $card['value'] ?? '' }}</div>
                    <div class="stat-sub">{{ $card['sub'] ?? '' }}</div>
                @endif

                @if (($card['trend'] ?? null) !== null && ($card['trend'] ?? '') !== '')
                    <div class="stat-trend">{{ $card['trend'] }}</div>
                @endif

                @if (! empty($card['chip']))
                    <div class="stat-chip {{ $card['chip']['class'] ?? '' }}">
                        <x-admin.icon :name="$card['chip']['icon'] ?? ''" />{{ $card['chip']['label'] ?? '' }}
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>
