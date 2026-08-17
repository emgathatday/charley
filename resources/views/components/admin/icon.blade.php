@props([
    'name',
    'sprite' => '/assets/icons/sprite.svg',
])

@php
    $iconId = str_starts_with($name, 'icon-') ? $name : 'icon-'.$name;
@endphp

<svg {{ $attributes->class(['icon']) }}><use href="{{ $sprite }}#{{ $iconId }}"></use></svg>
