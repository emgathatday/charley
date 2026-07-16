<div class="card card-outline card-primary mb-3">
    <div class="card-header"><h3 class="card-title mb-0">Rule Pre-check Configuration</h3></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.dashboard.qa.moderation-rules.store') }}" class="row g-3">
            @csrf
            <div class="col-md-6">
                <label class="form-label" for="name">Rule name</label>
                <input id="name" name="name" class="form-control" value="Blocked outage keywords" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="rule_type">Rule type</label>
                <select id="rule_type" name="rule_type" class="form-select">
                    @foreach (['keyword', 'max_links', 'min_length', 'regex', 'attachment_type', 'custom'] as $type)
                        <option value="{{ $type }}">{{ Str::headline($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="target_type">Target</label>
                <select id="target_type" name="target_type" class="form-select">
                    @foreach (['question', 'answer', 'both'] as $target)
                        <option value="{{ $target }}">{{ Str::headline($target) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="severity">Severity</label>
                <select id="severity" name="severity" class="form-select">
                    @foreach (['low', 'medium', 'high'] as $severity)
                        <option value="{{ $severity }}" @selected($severity === 'medium')>{{ Str::headline($severity) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input id="is_active" name="is_active" class="form-check-input" type="checkbox" value="1" checked>
                    <label class="form-check-label" for="is_active">Active before AI</label>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="keywords">Keyword list</label>
                <input id="keywords" name="keywords" class="form-control" value="exploit, bypass" placeholder="Comma separated keywords">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="max_links">Max links</label>
                <input id="max_links" name="max_links" class="form-control" type="number" value="2" min="0">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="min_length">Minimum length</label>
                <input id="min_length" name="min_length" class="form-control" type="number" value="80" min="0">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="pattern">Regex pattern</label>
                <input id="pattern" name="pattern" class="form-control" value="/[0-9]{10,}/">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="blocked_mime_types">Blocked attachment MIME types</label>
                <input id="blocked_mime_types" name="blocked_mime_types" class="form-control" value="application/x-msdownload">
            </div>
            <div class="col-md-9">
                <label class="form-label" for="custom_reason">Custom reason</label>
                <input id="custom_reason" name="custom_reason" class="form-control" value="Partner disclosure required before AI moderation.">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input id="always_warn" name="always_warn" class="form-check-input" type="checkbox" value="1">
                    <label class="form-check-label" for="always_warn">Always warn</label>
                </div>
            </div>
            <div class="col-12">
                <div class="alert alert-info mb-0">
                    <div class="fw-semibold"><i class="bi bi-shield-check me-1"></i>Rule-first moderation</div>
                    <div class="small">Active rules run before AI. A matched rule creates a system_rule warning and skips AI provider calls.</div>
                </div>
            </div>
            <div class="col-12"><button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Save Rule</button></div>
        </form>
    </div>
</div>