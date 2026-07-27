@extends('layouts.app')

@section('title', 'Edit Subscription Tier')

@section('content')
    <div class="subscription-form-page">
        <div class="page-head">
            <div>
                <h1>Edit Subscription Tier</h1>
                <p>Update {{ $subscriptionTier->display_name ?: $subscriptionTier->name }} while preserving dynamic permissions and partner bindings.</p>
            </div>
            <div class="page-head-actions">
                <a class="btn-secondary" href="{{ route('admin.dashboard.subscriptions.index') }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-button-class-btn-btn-ghost-style-flex-1-onclick-"></use></svg>Back</a>
            </div>
        </div>

        @include('templates.components.alert-session')

        <div class="form-card">
            <form method="POST" action="{{ route('admin.dashboard.subscriptions.tiers.update', $subscriptionTier) }}">
                @csrf
                @method('PUT')
                <div class="form-card-head"><h2>{{ $subscriptionTier->display_name ?: $subscriptionTier->name }}</h2><span>Tier #{{ $subscriptionTier->id }}</span></div>
                @include('admin.subscriptions.tiers._form', ['subscriptionTier' => $subscriptionTier])
                <div class="form-actions">
                    <a class="btn-secondary" href="{{ route('admin.dashboard.subscriptions.index') }}">Cancel</a>
                    <button class="btn-primary" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-5-this-month-svg-viewbox-0"></use></svg>Save Tier</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
@include('admin.subscriptions._form-styles')
@endpush