@extends('layouts.app')

@section('title', 'Create Partner Profile')

@section('content_header')
    <div class="page-header">
        <div>
            <div class="page-title">Create New Partner</div>
            <div class="page-subtitle">Register a company, assign a tier, and prepare approval workflow data.</div>
        </div>
        <div class="page-actions"><a href="{{ route('admin.dashboard.partner-profiles.index') }}" class="btn"><i class="bi bi-arrow-left" aria-hidden="true"></i>Back to Partners</a></div>
    </div>
@endsection

@section('content')
    @include('templates.components.alert-session')

    <form method="POST" action="{{ route('admin.dashboard.partner-profiles.store') }}">
        @csrf
        @include('admin.partner-profiles.partials.form', ['partnerProfile' => null])
        <div class="page-actions" style="justify-content:flex-end;margin-top:18px;">
            <a href="{{ route('admin.dashboard.partner-profiles.index') }}" class="btn">Cancel</a>
            <button class="btn btn-primary" type="submit"><i class="bi bi-check2" aria-hidden="true"></i>Save Partner</button>
        </div>
    </form>
@endsection
