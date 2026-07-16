<div class="card card-warning mb-3">
    <div class="card-header"><h3 class="card-title">{{ $item['title'] }}</h3></div>
    <div class="card-body">
        <p>{{ $item['domains'] ?: 'Flagged for moderation review.' }}</p>
        <div class="mb-2">
            <span class="badge text-bg-danger">Question</span>
            <span class="badge text-bg-light">{{ $item['plant'] }}</span>
        </div>
        <form method="POST" action="{{ route('admin.dashboard.qa.questions.status', [$item['id'], 'published']) }}" class="d-inline">
            @csrf
            <button class="btn btn-sm btn-success" type="submit"><i class="bi bi-check-lg me-1"></i> Clear</button>
        </form>
        <form method="POST" action="{{ route('admin.dashboard.qa.questions.status', [$item['id'], 'hidden']) }}" class="d-inline">
            @csrf
            <button class="btn btn-sm btn-secondary" type="submit"><i class="bi bi-eye-slash me-1"></i> Hide</button>
        </form>
    </div>
</div>