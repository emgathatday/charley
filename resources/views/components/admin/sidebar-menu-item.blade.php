@if (($item['visible'] ?? true) != false)
<a href="{{ $hrefFor($item) }}" class="nav-item{{ $isActive($item) ? ' active' : '' }}">
    <x-admin.icon :name="$item['icon'] ?? ''" />
    {{ $item['label'] ?? '' }}
    @if (! empty($item['badge']['label']))
        <span class="nav-badge {{ $item['badge']['class'] ?? '' }}">{{ $item['badge']['label'] }}</span>
    @endif
</a>

@foreach ($item['children'] ?? [] as $child)
    @include('components.admin.sidebar-menu-item', ['item' => $child, 'hrefFor' => $hrefFor, 'isActive' => $isActive])
@endforeach
@endif
