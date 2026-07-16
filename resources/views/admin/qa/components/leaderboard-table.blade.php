<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">Monthly Leaderboard Report</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-bordered mb-0">
            <thead>
            <tr>
                <th>Rank</th>
                <th>User</th>
                <th>Points</th>
                <th>Star Rank</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($leaders as $leader)
                <tr>
                    <td>#{{ $leader['rank'] }}</td>
                    <td>{{ $leader['name'] }}</td>
                    <td>{{ number_format($leader['points']) }}</td>
                    <td>{{ $leader['stars'] ? $leader['stars'].' stars' : 'Pending tier sync' }}</td>
                </tr>
            @empty
                <tr><td class="text-muted text-center py-3" colspan="4">No leaderboard snapshot available.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>