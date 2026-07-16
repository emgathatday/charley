<div class="card mb-3">
    <div class="card-header"><h3 class="card-title">Weekly Theme Setup</h3></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.dashboard.qa.weekly-themes.store') }}">
            @csrf
            <div class="form-group">
                <label>Title</label>
                <input name="title" class="form-control" value="Rotating equipment reliability">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="3">Collect practical troubleshooting questions for compressor and pump reliability.</textarea>
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Week start</label>
                    <input name="week_start_date" type="date" class="form-control" value="{{ now()->startOfWeek()->toDateString() }}">
                </div>
                <div class="col-md-6 form-group">
                    <label>Week end</label>
                    <input name="week_end_date" type="date" class="form-control" value="{{ now()->endOfWeek()->toDateString() }}">
                </div>
            </div>
            <input type="hidden" name="status" value="active">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Theme</button>
        </form>
    </div>
</div>