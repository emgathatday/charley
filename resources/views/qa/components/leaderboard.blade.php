<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">Monthly Leaderboard</h3>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <tbody>
            @forelse ($leaders as $leader)
                <tr>
                    <td class="text-muted">#{{ $leader['rank'] }}</td>
                    <td>{{ $leader['name'] }}</td>
                    <td class="text-right">{{ number_format($leader['points']) }} pts</td>
                </tr>
            @empty
                <tr>
                    <td class="text-muted text-center py-3" colspan="3">No monthly ranking yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>