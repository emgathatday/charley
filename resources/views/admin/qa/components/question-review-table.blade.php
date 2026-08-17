<div class="border-top pt-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3 class="card-title mb-0">Question Review Queue</h3>
        <span class="text-body-secondary small">{{ $questions->count() }} visible</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr>
                <th>Question</th>
                <th>Plant</th>
                <th>Answers</th>
                <th>Status</th>
                <th>Quick Change Status</th>
                <th>Author</th>
                <th>Created</th>
                <th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($questions as $question)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $question['title'] }}</div>
                        <div class="text-body-secondary small">{{ $question['domains'] ?: 'No domain links' }}</div>
                    </td>
                    <td>{{ $question['plant'] }}</td>
                    <td><span class="badge text-bg-info rounded-pill px-3">{{ $question['answer_count'] ?? 0 }}</span></td>
                    <td><span class="badge text-bg-{{ $question['status_color'] }}">{{ Str::headline($question['status']) }}</span></td>
                    <td>
                        <form method="POST" action="{{ route('admin.dashboard.qa.questions.demo-status', $question['id']) }}" class="d-flex gap-2 align-items-center">
                            @csrf
                            <select name="status" class="form-select form-select-sm" aria-label="Quick change status for {{ $question['title'] }}">
                                @foreach (['pending', 'published', 'hidden', 'flagged'] as $status)
                                    <option value="{{ $status }}" @selected($question['status'] === $status)>{{ Str::headline($status) }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-check2"></i></button>
                        </form>
                    </td>
                    <td>{{ $question['author'] }}</td>
                    <td>{{ $question['created_at'] ?? '-' }}</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.dashboard.qa.questions.show', $question['id']) }}">
                            <i class="bi bi-eye me-1"></i>View
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td class="text-muted text-center py-3" colspan="8">No questions match the current filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>