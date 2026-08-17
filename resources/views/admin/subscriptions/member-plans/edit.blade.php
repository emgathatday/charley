@extends('layouts.app')

@section('title', 'Edit Member Plan')

@section('content')
    <div class="subscription-form-page">
        <div class="page-head">
            <div>
                <h1>Edit Member Plan</h1>
                <p>Update {{ $memberSubscriptionPlan->display_name }} member plan details.</p>
            </div>
            <div class="page-head-actions">
                <a class="btn-secondary" href="{{ route('admin.dashboard.subscriptions.index') }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-button-class-btn-btn-ghost-style-flex-1-onclick-"></use></svg>Back</a>
            </div>
        </div>

        @include('templates.components.alert-session')

        <div class="form-card">
            <form method="POST" action="{{ route('admin.dashboard.subscriptions.member-plans.update', $memberSubscriptionPlan) }}">
                @csrf
                @method('PUT')
                <div class="form-card-head"><h2>{{ $memberSubscriptionPlan->display_name }}</h2><span>Plan #{{ $memberSubscriptionPlan->id }}</span></div>
                @include('admin.subscriptions.member-plans._form', ['memberSubscriptionPlan' => $memberSubscriptionPlan])
                <div class="form-actions">
                    <a class="btn-secondary" href="{{ route('admin.dashboard.subscriptions.index') }}">Cancel</a>
                    <button class="btn-primary" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-5-this-month-svg-viewbox-0"></use></svg>Save Plan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
@include('admin.subscriptions._form-styles')
@endpush