@extends('layouts.app')

@section('title', 'Create Member Plan')

@section('content')
    <div class="subscription-form-page">
        <div class="page-head">
            <div>
                <h1>Create Member Plan</h1>
                <p>Configure professional member upgrade access and visible feature notes.</p>
            </div>
            <div class="page-head-actions">
                <a class="btn-secondary" href="{{ route('admin.dashboard.subscriptions.index') }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-button-class-btn-btn-ghost-style-flex-1-onclick-"></use></svg>Back</a>
            </div>
        </div>

        @include('templates.components.alert-session')

        <div class="form-card">
            <form method="POST" action="{{ route('admin.dashboard.subscriptions.member-plans.store') }}">
                @csrf
                <div class="form-card-head"><h2>Plan details</h2><span>Professional member catalog</span></div>
                @include('admin.subscriptions.member-plans._form', ['memberSubscriptionPlan' => null])
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