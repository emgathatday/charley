@props([
    'categories' => [],
    'selected' => null,
    'name' => 'category',
    'label' => 'Category',
    'placeholder' => 'All categories',
])

<div>
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    <select id="{{ $name }}" name="{{ $name }}" {{ $attributes->merge(['class' => 'form-select']) }}>
        <option value="">{{ $placeholder }}</option>
        @foreach ($categories as $category)
            <option value="{{ $category }}" @selected($selected === $category)>
                {{ Str::headline(str_replace('_', ' ', $category)) }}
            </option>
        @endforeach
    </select>
</div>
