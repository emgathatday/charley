@extends('layouts.rebuild-dashboard')

@section('title', 'Create Plant Type')

@section('content')
    @php
        $previewName = old('name', 'Ammonia Plant');
        $previewSlug = old('slug', 'ammonia-plant');
        $previewDescription = old('description', 'Primary process catalog for ammonia plant library content, questions, services, partner profiles and AI chat context.');
        $previewSort = old('sort_order', 10);
        $previewActive = (string) old('is_active', '1') === '1';
    @endphp

    @include('templates.components.alert-session')

    <a href="{{ route('admin.dashboard.plant-types.index') }}" class="back-link">
        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-back-to-account-penalty-and"></use></svg>
        Back to Plant Types
    </a>

    <form id="plant-type-form" method="POST" action="{{ route('admin.dashboard.plant-types.store') }}">
        @csrf
        <div class="page-head">
            <div>
                <div class="page-title">Create Plant Type</div>
                <div class="page-subtitle">Create one Plant Type record. PFD sections, content tags and quiz banks are configured after the catalog record exists.</div>
            </div>
            <div class="page-head-actions">
                <button class="btn btn-outline" type="button">Save draft</button>
                <button class="btn btn-primary" type="submit">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-save-as-draft-svg-viewbox-0"></use></svg>
                    Create plant type
                </button>
            </div>
        </div>

        <div class="plant-type-create-layout">
            <div class="plant-type-create-main">
                @include('admin.plant-types._form')

                <div class="form-card">
                    <div class="form-card-header">
                        <div class="form-card-icon purple">
                            <svg class="icon"><use href="/assets/icons/sprite.svg#icon-platform-settings-ai-assistant"></use></svg>
                        </div>
                        <div>
                            <div class="form-card-title">Linked modules after creation</div>
                            <div class="form-card-sub">These are related records through Plant Type, not fields on the catalog record.</div>
                        </div>
                    </div>
                    <div class="form-card-body">
                        <div class="plant-type-relation-grid">
                            <div class="plant-type-relation-item"><strong>Library</strong><span>Library items, handbook categories, posts</span></div>
                            <div class="plant-type-relation-item"><strong>Q&amp;A and AI</strong><span>Questions, AI chat sessions, AI prompt templates</span></div>
                            <div class="plant-type-relation-item"><strong>Partner activity</strong><span>Partner profiles, service requests, jobs management</span></div>
                            <div class="plant-type-relation-item"><strong>Tools</strong><span>Calculation tools, services, events, polls</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="plant-type-create-side">
                <div class="side-card">
                    <div class="card card-padded plant-type-preview-card">
                        <div class="card-title">Preview</div>
                        <div class="plant-type-card plant-type-card-preview">
                            <div class="plant-type-card-top">
                                <div class="plant-type-icon plant-type-tone-amber">
                                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-library-and-pfd-content-path"></use></svg>
                                </div>
                                <div>
                                    <div class="plant-type-name">{{ $previewName }}</div>
                                    <div class="plant-type-slug">Slug: {{ $previewSlug }}</div>
                                </div>
                            </div>
                            <p class="plant-type-desc">{{ $previewDescription }}</p>
                            <div class="plant-type-meta">
                                <span @class(['badge', $previewActive ? 'plant-type-badge-active' : 'plant-type-badge-muted'])>{{ $previewActive ? 'Active' : 'Inactive' }}</span>
                                <span>Sort order: {{ $previewSort }}</span>
                            </div>
                        </div>
                        <div class="plant-type-next-list">
                            <div><strong>1</strong><span>Create catalog record</span></div>
                            <div><strong>2</strong><span>Add related content and sections</span></div>
                            <div><strong>3</strong><span>Review usage before inactivation</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="action-bar">
            <span class="action-bar-note">Required: Name, Slug. Optional: Description, Sort order, Status.</span>
            <div class="action-bar-right">
                <a href="{{ route('admin.dashboard.plant-types.index') }}" class="btn btn-outline">Cancel</a>
                <button class="btn btn-primary" type="submit">Create plant type</button>
            </div>
        </div>
    </form>
@endsection
