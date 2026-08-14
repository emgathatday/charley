@extends('layouts.rebuild-dashboard')

@section('title', 'Plant Types')

@section('content')
    @include('templates.components.alert-session')

    @php
        $plantTypeRows = $plantTypes->getCollection();
        $plantTypeStatCards = [
            ['class' => 'blue', 'label' => 'Total Plant Types', 'value' => number_format($plantTypes->total()), 'sub' => 'Catalog records'],
            ['class' => 'green', 'label' => 'Active', 'value' => number_format($plantTypeRows->where('is_active', true)->count()), 'sub' => 'Visible on this page'],
            ['class' => 'amber', 'label' => 'Lib. Items', 'value' => number_format($plantTypeRows->sum('library_items_count')), 'sub' => 'Current page links'],
            ['label' => 'K. Domains', 'value' => number_format($plantTypeRows->sum('knowledge_domains_count')), 'sub' => 'Current page links'],
        ];
    @endphp

    <div class="page-head">
        <div>
            <div class="page-title">Plant Types &amp; PFD Configuration</div>
            <div class="page-subtitle">Manage the Plant Type catalog used by library content, questions, services, partner profiles and AI workflows.</div>
        </div>
        <div class="page-head-actions">
            <button class="btn btn-outline" type="button">Export</button>
            <a href="{{ route('admin.dashboard.plant-types.create') }}" class="btn btn-primary">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-save-2"></use></svg>
                Add Plant Type
            </a>
        </div>
    </div>

    {{ \App\Support\AdminStatCards::render($plantTypeStatCards) }}

    <div class="tab-bar plant-type-tab-bar mb-3">
        <button class="tab-btn active" type="button">Plant Types</button>
        <button class="tab-btn" type="button">Linked Content</button>
        <button class="tab-btn" type="button">PFD Usage</button>
        <button class="tab-btn" type="button">Audit</button>
    </div>

    <div class="plant-type-grid">
        @forelse ($plantTypes as $plantType)
            @php
                $toneClass = ['plant-type-tone-amber', 'plant-type-tone-green', 'plant-type-tone-blue'][$loop->index % 3];
                $updatedLabel = optional($plantType->updated_at)->format('Y-m-d') ?? optional($plantType->created_at)->format('Y-m-d') ?? 'Not recorded';
            @endphp
            <div @class(['plant-type-card', 'plant-type-card-inactive' => ! $plantType->is_active])>
                <div class="plant-type-card-top">
                    <div @class(['plant-type-icon', $plantType->is_active ? $toneClass : 'plant-type-tone-muted'])>
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-library"></use></svg>
                    </div>
                    <div>
                        <div class="plant-type-name">{{ $plantType->name }}</div>
                        <div class="plant-type-slug">Slug: {{ $plantType->slug }}</div>
                        <span @class(['badge', $plantType->is_active ? 'plant-type-badge-active' : 'plant-type-badge-muted'])>
                            {{ $plantType->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
                <p class="plant-type-desc">{{ $plantType->description ?: 'No description has been added for this plant type yet.' }}</p>
                <div class="plant-type-meta">
                    <span>Sort order: {{ $plantType->sort_order }}</span>
                    <span>Updated: {{ $updatedLabel }}</span>
                </div>
                <div class="plant-type-metrics">
                    <div><strong>{{ $plantType->library_items_count ?? 0 }}</strong><span>Lib. Items</span></div>
                    <div><strong>{{ $plantType->questions_count ?? 0 }}</strong><span>Q&amp;A</span></div>
                    <div><strong>{{ $plantType->knowledge_domains_count ?? 0 }}</strong><span>K. Domains</span></div>
                </div>
                <div class="plant-type-actions">
                    <a href="{{ route('admin.dashboard.plant-types.edit', $plantType) }}" class="btn btn-ghost btn-sm">View/Edit</a>
                </div>
            </div>
        @empty
            <div class="plant-type-card">
                <div class="plant-type-card-top">
                    <div class="plant-type-icon plant-type-tone-muted">
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-library"></use></svg>
                    </div>
                    <div>
                        <div class="plant-type-name">No plant types yet</div>
                        <div class="plant-type-slug">Create the first Plant Type catalog record.</div>
                        <span class="badge plant-type-badge-muted">Empty</span>
                    </div>
                </div>
                <p class="plant-type-desc">Plant types connect library content, questions, services, partner profiles and AI workflows.</p>
                <div class="plant-type-actions">
                    <a href="{{ route('admin.dashboard.plant-types.create') }}" class="btn btn-primary btn-sm">Add Plant Type</a>
                </div>
            </div>
        @endforelse
    </div>

    @if ($plantTypes->hasPages())
        <div class="table-foot">
            {{ $plantTypes->links() }}
        </div>
    @endif
@endsection
