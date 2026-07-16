<div class="card mb-3">
    <div class="card-header"><h3 class="card-title mb-0">Warning Review Queue</h3></div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr>
                <th>Warning</th>
                <th>Context</th>
                <th>User Count</th>
                <th>Evidence</th>
                <th class="text-end">Review Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($warnings as $warning)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $warning->user_name }}</div>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            <span class="badge text-bg-{{ $warning->source === 'system_rule' ? 'primary' : ($warning->source === 'ai' ? 'info' : 'secondary') }}">{{ Str::headline($warning->source) }}</span>
                            <span class="badge text-bg-{{ $warning->severity === 'high' ? 'danger' : ($warning->severity === 'medium' ? 'warning' : 'secondary') }}">{{ Str::headline($warning->severity) }}</span>
                            <span class="badge text-bg-light border">{{ Str::headline($warning->status) }}</span>
                        </div>
                        <div class="small text-body-secondary mt-1">{{ $warning->reason }}</div>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ Str::headline($warning->warnable_type) }} #{{ $warning->warnable_id }}</div>
                        <div class="small text-body-secondary">{{ $warning->context }}</div>
                    </td>
                    <td>
                        <span class="badge text-bg-{{ $warning->is_frozen ? 'danger' : ($warning->confirmed_warning_count >= 2 ? 'warning' : 'info') }}">{{ $warning->confirmed_warning_count }}/3 confirmed</span>
                        <div class="small text-body-secondary mt-1">{{ $warning->is_frozen ? 'Frozen' : 'Not frozen' }}</div>
                    </td>
                    <td><pre class="small mb-0 text-wrap">{{ $warning->evidence_text }}</pre></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            @foreach (['safe' => 'Safe', 'dismissed' => 'Dismiss', 'confirmed' => 'Confirm'] as $status => $label)
                                <form method="POST" action="{{ route('admin.dashboard.qa.warnings.review', [$warning->id, $status]) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-outline-{{ $status === 'confirmed' ? 'danger' : ($status === 'safe' ? 'success' : 'secondary') }}" type="submit">{{ $label }}</button>
                                </form>
                            @endforeach
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td class="text-muted text-center py-3" colspan="5">No moderation warnings match the current filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>