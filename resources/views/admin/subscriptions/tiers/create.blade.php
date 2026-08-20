@extends('layouts.rebuild-dashboard')

@section('title', 'Create Subscription Tier')

@section('content')
    @include('templates.components.alert-session')

    <form method="POST" action="{{ route('admin.dashboard.subscriptions.tiers.store') }}">
        @csrf
        <a href="{{ route('admin.dashboard.subscriptions.index') }}" class="back-link"><x-admin.icon name="back-to-account-penalty-and" />Back to Subscriptions</a>

        <div class="page-head">
            <div><div class="page-title">Create Subscription Tier</div><div class="page-subtitle">Create a dynamic partner tier and configure permission values by name, module, type, and enabled state.</div></div>
            <div class="page-head-actions"><button class="btn btn-primary" type="submit"><x-admin.icon name="icon-add-another-document-clas" />Create tier</button></div>
        
        </div>

        <div class="tab-bar subscription-tab-bar mb-3">
            <button class="tab-btn active" type="button"><x-admin.icon name="billing" />Tier details</button>
            <button class="tab-btn" type="button"><x-admin.icon name="settings-2" />Permissions</button>
        </div>

        @include('admin.subscriptions.tiers._form', ['subscriptionTier' => null])

        <div class="action-bar">
            <div class="action-bar-left">Value -1 means unlimited; disabled permissions are not assigned to the tier.</div>
            <div class="action-bar-right"><a href="{{ route('admin.dashboard.subscriptions.index') }}" class="btn btn-ghost">Cancel</a><button class="btn btn-primary" type="submit"><x-admin.icon name="save-as-draft-svg-viewbox-0" />Create tier</button></div>
        </div>
    </form>
@endsection
