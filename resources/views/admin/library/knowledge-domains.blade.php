@extends('layouts.rebuild-dashboard')

@section('title', 'Knowledge Domains')

@section('content')
    @php
        $domainRows = $domains->getCollection();
        $belowTargetCount = $domainRows->filter(fn ($domain) => ($domain->active_questions_count ?? 0) < ($domain->quiz_question_count ?? 0))->count();
        $inactiveCount = max(0, ($stats['total'] ?? 0) - ($stats['active'] ?? 0));
        $statusTab = request()->query('is_active');
        $statusTabBaseQuery = request()->except(['is_active', 'page']);
        $statusTabRoute = fn (array $query = []) => route('admin.dashboard.library.knowledge-domains.index', array_merge($statusTabBaseQuery, $query));
    @endphp

    @include('templates.components.alert-session')

    <div class="page-head">
        <div class="page-title-row">
            <div>
                <div class="page-title">Knowledge Domains</div>
                <div class="page-subtitle">Manage the independent domain catalog used by quiz questions, attempts, mandatory domains and expertise rank rules.</div>
            </div>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 knowledge-stat-row">
        <div class="col"><div class="stat-card"><div class="stat-label">Active Domains</div><div class="stat-value">{{ $stats['active'] ?? 0 }}</div><div class="stat-sub">is_active = true</div></div></div>
        <div class="col"><div class="stat-card"><div class="stat-label">Quiz Questions</div><div class="stat-value">{{ number_format($stats['questions'] ?? 0) }}</div><div class="stat-sub">quiz_questions total</div></div></div>
        <div class="col"><div class="stat-card"><div class="stat-label">Mandatory Domains</div><div class="stat-value">0</div><div class="stat-sub">rank promotion core set</div></div></div>
        <div class="col"><div class="stat-card"><div class="stat-label">Below Target</div><div class="stat-value">{{ $belowTargetCount }}</div><div class="stat-sub">active count below quiz setting</div></div></div>
    </div>

    <div class="row g-3 align-items-center mb-3">
        <div class="col-12 col-xl">
            <div class="tab-bar">
                <a @class(['tab-btn', 'active' => $statusTab === null || $statusTab === '']) href="{{ $statusTabRoute() }}">All <span class="tab-count">{{ $stats['total'] ?? $domains->total() }}</span></a>
                <a @class(['tab-btn', 'active' => $statusTab === '1']) href="{{ $statusTabRoute(['is_active' => 1]) }}">Active <span class="tab-count">{{ $stats['active'] ?? 0 }}</span></a>
                <a @class(['tab-btn', 'active' => $statusTab === '0']) href="{{ $statusTabRoute(['is_active' => 0]) }}">Inactive <span class="tab-count">{{ $inactiveCount }}</span></a>
            </div>
        </div>
    </div>

    <div class="table-wrap knowledge-domain-table-wrap">
        <div class="table-header table-head-panel">
            <div class="table-head-main">
                <div class="table-title">Knowledge Domains</div>
                <div class="table-meta">{{ $domains->total() }} domains - use row actions to view/edit domain settings and related questions.</div>
            </div>
            <form method="GET" action="{{ route('admin.dashboard.library.knowledge-domains.index') }}" class="table-head-actions">
                <select class="filter-select" name="plant_type_id">
                    <option value="">All Plant Types</option>
                    @foreach ($plantTypes as $plantType)
                        <option value="{{ $plantType->id }}" @selected((string) request('plant_type_id') === (string) $plantType->id)>{{ $plantType->name }}</option>
                    @endforeach
                </select>
                @if (request()->filled('is_active'))
                    <input type="hidden" name="is_active" value="{{ request('is_active') }}">
                @endif
                <button class="btn-outline btn-filter" type="submit">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-filter-account-acc"></use></svg>
                    Filter
                </button>
                <a href="{{ route('admin.dashboard.library.knowledge-domains.create') }}" class="btn btn-primary btn-sm">Add Domain</a>
            </form>
        </div>
        <div class="table-scroll">
            <table class="knowledge-domain-table">
                <thead>
                    <tr>
                        <th class="knowledge-check-col"><input class="knowledge-check-input" type="checkbox"></th>
                        <th>Domain Name</th>
                        <th>Plant Type</th>
                        <th>Total Questions</th>
                        <th>Per Attempt</th>
                        <th>Mandatory</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($domains as $domain)
                        <tr>
                            <td><input class="knowledge-check-input" type="checkbox"></td>
                            <td>
                                <div class="knowledge-domain-cell">
                                    <strong>{{ $domain->name }}</strong>
                                    <span>slug: {{ $domain->slug }}</span>
                                </div>
                            </td>
                            <td>{{ $domain->plantType?->name ?? 'General' }}</td>
                            <td><strong>{{ $domain->quiz_questions_count ?? 0 }}</strong></td>
                            <td>{{ $domain->quiz_question_count }}</td>
                            <td><span class="badge knowledge-badge-muted">Optional</span></td>
                            <td><span @class(['badge', $domain->is_active ? 'knowledge-badge-active' : 'knowledge-badge-muted'])>{{ $domain->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <div class="action-cell">
                                    <a href="{{ route('admin.dashboard.library.knowledge-domains.edit', $domain) }}" class="action-btn primary" aria-label="View domain"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-1-204-views-svg-viewbox-0-0"></use></svg></a>
                                    <a href="{{ route('admin.dashboard.library.knowledge-domains.edit', $domain) }}" class="action-btn" aria-label="Edit domain"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-add-note"></use></svg></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="knowledge-domain-cell">
                                    <strong>No knowledge domains match the current filters.</strong>
                                    <span>Create a domain or adjust the filters.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($domains->hasPages())
            <div class="pagination">
                <span class="page-info">Showing {{ $domains->firstItem() }}-{{ $domains->lastItem() }} of {{ $domains->total() }} results</span>
                {{ $domains->links() }}
            </div>
        @endif
    </div>
@endsection

