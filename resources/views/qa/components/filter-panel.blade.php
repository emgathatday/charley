<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('qa.community.index') }}" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label" for="plant_type_id">Plant type</label>
                <select id="plant_type_id" name="plant_type_id" class="form-select">
                    <option value="">All plant types</option>
                    @foreach ($plantTypes as $plantType)
                        <option value="{{ $plantType->id }}" @selected(($filters['plant_type_id'] ?? '') == $plantType->id)>{{ $plantType->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label" for="weekly_theme_id">Weekly theme</label>
                <select id="weekly_theme_id" name="weekly_theme_id" class="form-select">
                    <option value="">All themes</option>
                    @foreach ($weeklyThemes as $weeklyTheme)
                        <option value="{{ $weeklyTheme->id }}" @selected(($filters['weekly_theme_id'] ?? '') == $weeklyTheme->id)>{{ $weeklyTheme->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('qa.community.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>