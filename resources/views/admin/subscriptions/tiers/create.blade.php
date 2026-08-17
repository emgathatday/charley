@extends('layouts.rebuild-dashboard')

@section('title', 'Tạo Subscription mới')

@section('content')
    @include('templates.components.alert-session')

    <form method="POST" action="{{ route('admin.dashboard.subscriptions.tiers.store') }}">
        @csrf
        <a href="{{ route('admin.dashboard.subscriptions.index') }}" class="back-link"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-back-to-account-penalty-and"></use></svg>Back to Subscriptions</a>

        <div class="page-head">
            <div class="page-title-row">
                <div><div class="page-title">Tạo Subscription mới</div><div class="page-subtitle">Tạo gói thành viên mới và cấu hình permission theo name, module, type, value, toggle on/off.</div></div>
                <div class="page-head-actions"><button class="btn btn-outline" type="button">Save draft</button><button class="btn btn-primary" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-save-as-draft-svg-viewbox-0"></use></svg>Create subscription</button></div>
            </div>
        </div>

        <div class="tab-bar subscription-tab-bar mb-3">
            <button class="tab-btn active" type="button">Thông tin gói</button>
            <button class="tab-btn" type="button">Permissions</button>
        </div>

        @include('admin.subscriptions.tiers._form', ['subscriptionTier' => null])

        <div class="action-bar">
            <div class="action-bar-left">Value `-1` hiển thị là Không giới hạn; permission tắt sẽ không được gán vào gói.</div>
            <div class="action-bar-right"><a href="{{ route('admin.dashboard.subscriptions.index') }}" class="btn btn-ghost">Cancel</a><button class="btn btn-primary" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-save-as-draft-svg-viewbox-0"></use></svg>Create plan</button></div>
        </div>
    </form>
@endsection