<div class="card mb-3">
    <div class="card-body">
        <div class="mb-2">
            <span class="badge badge-info">{{ $question['plant'] }}</span>
            <span class="badge badge-light">{{ $question['theme'] }}</span>
            @include('qa.components.anonymous-badge', ['anonymous' => $question['anonymous']])
        </div>
        <h2 class="h5">
            <a href="{{ route('qa.community.show', $question['slug']) }}">{{ $question['title'] }}</a>
        </h2>
        <p class="text-muted">{{ $question['body'] }}</p>
        @include('qa.components.domain-links', ['domains' => $question['domains']])
        @include('qa.components.media-list', ['media' => $question['media']])
        <span class="text-muted"><i class="bi bi-chat-dots mr-1"></i>{{ $question['answers'] }} answers</span>
    </div>
</div>