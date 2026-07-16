<div class="card card-outline card-success mb-3">
    <div class="card-header"><h3 class="card-title mb-0">Manual Reputation Adjustment</h3></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.dashboard.qa.reputation.adjustments.store') }}" class="row g-3">
            @csrf
            <div class="col-12">
                <label class="form-label" for="reputation_user_id">User</label>
                <select id="reputation_user_id" name="user_id" class="form-select" required>
                    @forelse ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->display_name ?? $user->email }} - {{ $user->email }}</option>
                    @empty
                        <option value="">No users available</option>
                    @endforelse
                </select>
            </div>

            <div class="col-12">
                <div class="form-label">Point change direction</div>
                <div class="btn-group w-100" role="group" aria-label="Point change direction">
                    <input type="radio" class="btn-check" name="direction" id="points_direction_positive" value="positive" autocomplete="off" checked>
                    <label class="btn btn-outline-success" for="points_direction_positive"><i class="bi bi-plus-circle me-1"></i>Positive</label>
                    <input type="radio" class="btn-check" name="direction" id="points_direction_negative" value="negative" autocomplete="off">
                    <label class="btn btn-outline-danger" for="points_direction_negative"><i class="bi bi-dash-circle me-1"></i>Negative</label>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label" for="points">Point amount</label>
                <input id="points" name="points" type="number" class="form-control" min="1" value="25" required>
                <div class="form-text">Ledger stores positive or negative points as a manual_adjustment transaction.</div>
            </div>

            <div class="col-12">
                <label class="form-label" for="reason">Required reason</label>
                <textarea id="reason" name="reason" class="form-control" rows="4" required>Awarded for accepted answer on compressor vibration troubleshooting.</textarea>
            </div>

            <div class="col-12">
                <div class="alert alert-info mb-0">
                    <div class="fw-semibold"><i class="bi bi-person-check me-1"></i>Performed by current admin</div>
                    <div class="small">The adjustment is recorded in point_transactions and reflected in user_reputation total points.</div>
                </div>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="bi bi-journal-plus me-1"></i>Record Adjustment</button>
            </div>
        </form>
    </div>
</div>