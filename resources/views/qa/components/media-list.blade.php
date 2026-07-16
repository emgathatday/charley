@if (! empty($media))
    <div class="mb-2">
        @foreach ($media as $mediaItem)
            <span class="badge badge-light"><i class="bi bi-file-earmark mr-1"></i>{{ is_array($mediaItem) ? $mediaItem['name'] : $mediaItem }}</span>
        @endforeach
    </div>
@endif