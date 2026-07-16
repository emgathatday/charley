<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">Answer Moderation</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Answer</th>
                <th>Question</th>
                <th>Author</th>
                <th>Confidence</th>
                <th>Featured</th>
                <th>Rank</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($answers as $answer)
                <tr>
                    <td>{{ $answer['body'] }}</td>
                    <td>{{ $answer['question'] }}</td>
                    <td>{{ $answer['author'] }}</td>
                    <td><span class="badge text-bg-info">{{ $answer['confidence'] }}</span></td>
                    <td>{{ $answer['featured'] ? 'Yes' : 'No' }}</td>
                    <td>{{ $answer['rank'] }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.dashboard.qa.answers.feature', $answer['id']) }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="confidence_level" value="high">
                            <input type="hidden" name="admin_rank_order" value="1">
                            <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-star-fill"></i></button>
                        </form>
                        <form method="POST" action="{{ route('admin.dashboard.qa.answers.unfeature', $answer['id']) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-x-lg"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td class="text-muted text-center py-3" colspan="7">No answers available.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>