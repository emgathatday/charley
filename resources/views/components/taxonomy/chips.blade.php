@props([
    'tags' => collect(),
    'empty' => 'No tags selected',
])

@php
    $tags = collect($tags);
@endphp

<div {{ $attributes->merge(['class' => 'd-flex flex-wrap gap-2']) }}>
    @forelse ($tags as $tag)
        <span class="badge text-bg-light border d-inline-flex align-items-center gap-1 py-2 px-3">
            <i class="bi bi-tag"></i>
            <span>{{ $tag->name }}</span>
            @if ($tag->category)
                <small class="text-body-secondary">{{ Str::headline(str_replace('_', ' ', $tag->category)) }}</small>
            @endif
        </span>
    @empty
        <span class="text-body-secondary">{{ $empty }}</span>
    @endforelse
</div>
