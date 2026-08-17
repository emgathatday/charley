@if (! empty($domains))
    <div class="mb-2">
        @foreach ($domains as $domain)
            <span class="badge badge-outline-primary border text-primary">{{ $domain }}</span>
        @endforeach
    </div>
@endif