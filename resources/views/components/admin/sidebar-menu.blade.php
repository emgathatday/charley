@props(['items' => []])

@php
    $brand = $items['brand'] ?? [];
    $search = $items['search'] ?? [];
    $groups = $items['groups'] ?? [];
    $footer = $items['footer'] ?? [];

    $hrefFor = fn (array $item) => empty($item['route']) === false ? route($item['route'], $item['params'] ?? []) : ($item['url'] ?? '#');
    $isActive = function (array $item) use (&$isActive): bool {
        $active = $item['active'] ?? false;
        if (is_bool($active)) { return $active; }
        if (is_array($active)) {
            if (empty($active['except_route']) === false && request()->routeIs($active['except_route'])) { return false; }
            if (empty($active['route']) === false && request()->routeIs($active['route'])) { return true; }
            if (empty($active['url']) === false && request()->fullUrlIs($active['url'])) { return true; }
        }
        foreach ($item['children'] ?? [] as $child) {
            if ($isActive($child)) { return true; }
        }
        return false;
    };
@endphp

<aside class="sidebar" id="appSidebar">
    <div class="brand">
        <a href="{{ $hrefFor($brand) }}" class="brand-mark" aria-label="Charley Admin">
            <svg class="icon"><use href="/assets/icons/sprite.svg#{{ $brand['icon'] ?? 'icon-charley-logo' }}"></use></svg>
        </a>
        <div>
            <a href="{{ $hrefFor($brand) }}" class="brand-name">{{ $brand['label'] ?? 'Charley' }}</a>
            <div class="brand-sub">{{ $brand['sub_label'] ?? 'Admin Console' }}</div>
        </div>
    </div>

    <div class="sidebar-search">
        <svg class="icon"><use href="/assets/icons/sprite.svg#{{ $search['icon'] ?? 'icon-search-2' }}"></use></svg>
        <input type="text" placeholder="{{ $search['placeholder'] ?? 'Search platform...' }}" aria-label="{{ $search['placeholder'] ?? 'Search platform...' }}">
        <span class="kbd">{{ $search['kbd'] ?? '/' }}</span>
    </div>

    <nav class="nav-scroll" aria-label="Main navigation">
        @foreach ($groups as $group)
            @continue(($group['visible'] ?? true) === false)
            <div class="nav-group">
                <div class="nav-label">{{ $group['label'] ?? '' }}</div>
                @foreach ($group['items'] ?? [] as $item)
                    @include('components.admin.sidebar-menu-item', ['item' => $item, 'hrefFor' => $hrefFor, 'isActive' => $isActive])
                @endforeach
            </div>
        @endforeach
    </nav>

    <!-- <div class="sidebar-footer">
        <div class="ai-status-pill">
            <span class="pulse-dot"></span>
            <div class="ai-status-text">{{ $footer['label'] ?? 'AI Assistant - Operational' }}<span>{{ $footer['sub_label'] ?? 'Backend console ready' }}</span></div>
        </div>
    </div> -->
</aside>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
