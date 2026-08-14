<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

class AdminStatCards
{
    public static function render(array $data): HtmlString
    {
        $cards = $data['cards'] ?? $data;
        $rowClass = $data['row_class'] ?? 'row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-3';
        $html = '<div class=\'' . e($rowClass) . '\'>';
        foreach ($cards as $card) {
            $html .= static::card($card);
        }
        return new HtmlString($html . '</div>');
    }

    private static function card(array $card): string
    {
        $body = empty($card['icon'])
            ? '<div class=\'stat-label\'>' . e($card['label'] ?? '') . '</div>' . '<div class=\'stat-value\'>' . e($card['value'] ?? '') . '</div>' . '<div class=\'stat-sub\'>' . e($card['sub'] ?? '') . '</div>'
            : static::icon($card) . '<div class=\'stat-value\'>' . e($card['value'] ?? '') . '</div>' . '<div class=\'stat-label\'>' . e($card['label'] ?? '') . '</div>' . '<div class=\'stat-sub\'>' . e($card['sub'] ?? '') . '</div>';

        return '<div class=\'col\'><div class=\'stat-card ' . e($card['class'] ?? '') . '\'>' . $body . static::trend($card['trend'] ?? null) . static::chip($card['chip'] ?? []) . '</div></div>';
    }

    private static function icon(array $card): string
    {
        if (empty($card['icon'])) { return ''; }
        $class = trim('stat-icon ' . ($card['icon_class'] ?? ''));
        $icon = '<div class=\'' . e($class) . '\'><svg class=\'icon\'><use href=\'/assets/icons/sprite.svg#' . e($card['icon']) . '\'></use></svg></div>';
        if (! empty($card['icon_wrap'])) { return '<div class=\'' . e($card['icon_wrap']) . '\'>' . $icon . '</div>'; }
        return $icon;
    }

    private static function trend(?string $trend): string
    {
        if ($trend === null || $trend === '') { return ''; }
        return '<div class=\'stat-trend\'>' . e($trend) . '</div>';
    }

    private static function chip(array $chip): string
    {
        if (empty($chip)) { return ''; }
        return '<div class=\'stat-chip ' . e($chip['class'] ?? '') . '\'>' . '<svg class=\'icon\'><use href=\'/assets/icons/sprite.svg#' . e($chip['icon'] ?? '') . '\'></use></svg>' . e($chip['label'] ?? '') . '</div>';
    }
}
