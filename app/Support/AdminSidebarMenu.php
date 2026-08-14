<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

class AdminSidebarMenu
{
    public static function render(array $data): HtmlString
    {
        return new HtmlString(static::aside($data));
    }

    private static function aside(array $data): string
    {
        return '<aside class=' . chr(34) . 'sidebar' . chr(34) . ' id=' . chr(34) . 'appSidebar' . chr(34) . '>'.static::brand($data['brand'] ?? []).static::search($data['search'] ?? []).static::groups($data['groups'] ?? []).static::footer($data['footer'] ?? []).'</aside><div class=' . chr(34) . 'sidebar-overlay' . chr(34) . ' id=' . chr(34) . 'sidebarOverlay' . chr(34) . ' onclick=' . chr(34) . 'closeSidebar()' . chr(34) . '></div>';
    }

    private static function brand(array $brand): string
    {
        $href = static::href($brand);
        return '<div class=' . chr(34) . 'brand' . chr(34) . '><a href=' . chr(34) . ''.e($href).'' . chr(34) . ' class=' . chr(34) . 'brand-mark' . chr(34) . ' aria-label=' . chr(34) . 'Charley Admin' . chr(34) . '><svg class=' . chr(34) . 'icon' . chr(34) . '><use href=' . chr(34) . '/assets/icons/sprite.svg#'.e($brand['icon'] ?? 'icon-charley-logo').'' . chr(34) . '></use></svg></a><div><a href=' . chr(34) . ''.e($href).'' . chr(34) . ' class=' . chr(34) . 'brand-name' . chr(34) . '>'.e($brand['label'] ?? 'Charley').'</a><div class=' . chr(34) . 'brand-sub' . chr(34) . '>'.e($brand['sub_label'] ?? 'Admin Console').'</div></div></div>';
    }

    private static function search(array $search): string
    {
        $placeholder = e($search['placeholder'] ?? 'Search platform...');
        return '<div class=' . chr(34) . 'sidebar-search' . chr(34) . '><svg class=' . chr(34) . 'icon' . chr(34) . '><use href=' . chr(34) . '/assets/icons/sprite.svg#'.e($search['icon'] ?? 'icon-search-2').'' . chr(34) . '></use></svg><input type=' . chr(34) . 'text' . chr(34) . ' placeholder=' . chr(34) . ''.$placeholder.'' . chr(34) . ' aria-label=' . chr(34) . ''.$placeholder.'' . chr(34) . '><span class=' . chr(34) . 'kbd' . chr(34) . '>'.e($search['kbd'] ?? '/').'</span></div>';
    }

    private static function groups(array $groups): string
    {
        $html = '<nav class=' . chr(34) . 'nav-scroll' . chr(34) . ' aria-label=' . chr(34) . 'Main navigation' . chr(34) . '>';
        foreach ($groups as $group) {
            if (($group['visible'] ?? true) === false) { continue; }
            $html .= '<div class=' . chr(34) . 'nav-group' . chr(34) . '><div class=' . chr(34) . 'nav-label' . chr(34) . '>'.e($group['label'] ?? '').'</div>';
            foreach ($group['items'] ?? [] as $item) { $html .= static::item($item); }
            $html .= '</div>';
        }
        return $html.'</nav>';
    }

    private static function item(array $item): string
    {
        if (($item['visible'] ?? true) === false) { return ''; }
        $html = '<a href=' . chr(34) . ''.e(static::href($item)).'' . chr(34) . ' class=' . chr(34) . 'nav-item'.(static::active($item) ? ' active' : '').'' . chr(34) . '><svg class=' . chr(34) . 'icon' . chr(34) . '><use href=' . chr(34) . '/assets/icons/sprite.svg#'.e($item['icon'] ?? '').'' . chr(34) . '></use></svg>'.e($item['label'] ?? '').static::badge($item['badge'] ?? []).'</a>';
        foreach ($item['children'] ?? [] as $child) { $html .= static::item($child); }
        return $html;
    }

    private static function badge(array $badge): string
    {
        if (empty($badge['label'])) { return ''; }
        return '<span class=' . chr(34) . 'nav-badge '.e($badge['class'] ?? '').'' . chr(34) . '>'.e($badge['label']).'</span>';
    }

    private static function href(array $item): string
    {
        if (empty($item['route']) === false) { return route($item['route'], $item['params'] ?? []); }
        return $item['url'] ?? '#';
    }

    private static function active(array $item): bool
    {
        $active = $item['active'] ?? false;
        if (is_bool($active)) { return $active; }
        if (is_array($active)) {
            if (empty($active['except_route']) === false && request()->routeIs($active['except_route'])) { return false; }
            if (empty($active['route']) === false && request()->routeIs($active['route'])) { return true; }
            if (empty($active['url']) === false && request()->fullUrlIs($active['url'])) { return true; }
        }
        foreach ($item['children'] ?? [] as $child) { if (static::active($child)) { return true; } }
        return false;
    }

    private static function footer(array $footer): string
    {
        return '<div class=' . chr(34) . 'sidebar-footer' . chr(34) . '><div class=' . chr(34) . 'ai-status-pill' . chr(34) . '><span class=' . chr(34) . 'pulse-dot' . chr(34) . '></span><div class=' . chr(34) . 'ai-status-text' . chr(34) . '>'.e($footer['label'] ?? 'AI Assistant - Operational').'<span>'.e($footer['sub_label'] ?? 'Backend console ready').'</span></div></div></div>';
    }
}
