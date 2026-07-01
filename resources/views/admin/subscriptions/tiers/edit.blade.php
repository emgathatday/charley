@extends('layouts.master')

@section('title', 'Edit Subscription Tier')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Edit Subscription Tier</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.subscriptions.index') }}">Subscriptions</a></li><li class="breadcrumb-item active" aria-current="page">{{ ucfirst($subscriptionTier->name) }}</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        @include('templates.components.alert-session')
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3"><p class="text-body-secondary mb-0">Update partner tier #{{ $subscriptionTier->id }}.</p><a href="{{ route('admin.dashboard.subscriptions.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a></div>
        <div class="card card-outline card-primary"><form method="POST" action="{{ route('admin.dashboard.subscriptions.tiers.update', $subscriptionTier) }}">@csrf @method('PUT')<div class="card-header"><h3 class="card-title mb-0">{{ ucfirst($subscriptionTier->name) }}</h3></div>@include('admin.subscriptions.tiers._form', ['subscriptionTier' => $subscriptionTier])<div class="card-footer d-flex justify-content-end gap-2"><a href="{{ route('admin.dashboard.subscriptions.index') }}" class="btn btn-outline-secondary">Cancel</a><button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Save Tier</button></div></form></div>
    </div></div>
@endsection
