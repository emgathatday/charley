@php
    $iconClass = match ($label ?? '') {
        'Pending Review' => 'bi bi-hourglass-split',
        'Published' => 'bi bi-check2-circle',
        'Flagged' => 'bi bi-flag',
        'Answers' => 'bi bi-chat-dots',
        default => 'bi bi-info-circle',
    };
@endphp

<div class="col-md-3">
    <div class="info-box">
        <span class="info-box-icon text-bg-{{ $color }}"><i class="{{ $iconClass }}"></i></span>
        <div class="info-box-content">
            <span class="info-box-text">{{ $label }}</span>
            <span class="info-box-number">{{ $value }}</span>
        </div>
    </div>
</div>