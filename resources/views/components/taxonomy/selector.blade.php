@props([
    'tags' => collect(),
    'selected' => [],
    'name' => 'tag_ids',
    'label' => 'Tags',
    'help' => null,
])

@php
    $selected = collect(old($name, $selected))->map(fn ($id) => (string) $id)->all();
@endphp

<div>
    <label class="form-label">{{ $label }}</label>
    <select name="{{ $name }}[]" {{ $attributes->merge(['class' => 'form-select']) }} multiple>
        @foreach ($tags as $tag)
            <option value="{{ $tag->id }}" @selected(in_array((string) $tag->id, $selected, true))>
                {{ $tag->name }}{{ $tag->category ? ' - '.Str::headline(str_replace('_', ' ', $tag->category)) : '' }}
            </option>
        @endforeach
    </select>
    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
