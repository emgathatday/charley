@extends('layouts.master')

@section('title', 'Edit Partner Profile')

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h1 class="mb-0">Edit Partner Profile</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.partner-profiles.index') }}">Partner Profiles</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $partnerProfile->company_name }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="app-content">
        <div class="container-fluid">
            @include('templates.components.alert-session')

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                <p class="text-body-secondary mb-0">Update partner company details, taxonomy, and moderation state.</p>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.dashboard.partner-profiles.show', $partnerProfile) }}" class="btn btn-outline-primary">
                        <i class="bi bi-eye me-1"></i>
                        View
                    </a>
                    <a href="{{ route('admin.dashboard.partner-profiles.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>
                        Back
                    </a>
                </div>
            </div>

            <div class="card card-outline card-primary">
                <form method="POST" action="{{ route('admin.dashboard.partner-profiles.update', $partnerProfile) }}">
                    @csrf
                    @method('PUT')
                    <div class="card-header">
                        <h3 class="card-title mb-0">{{ $partnerProfile->company_name }}</h3>
                    </div>
                    <div class="card-body">
                        @include('admin.partner-profiles.partials.form', ['partnerProfile' => $partnerProfile])
                    </div>
                    <div class="card-footer d-flex flex-column flex-md-row justify-content-between gap-2">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-check2 me-1"></i>
                            Save Changes
                        </button>
                        <button type="submit" form="delete-partner-profile-{{ $partnerProfile->id }}" class="btn btn-outline-danger">
                            <i class="bi bi-trash me-1"></i>
                            Delete
                        </button>
                    </div>
                </form>
            </div>

            <form id="delete-partner-profile-{{ $partnerProfile->id }}" method="POST" action="{{ route('admin.dashboard.partner-profiles.destroy', $partnerProfile) }}" onsubmit="return confirm('Delete this partner profile?');">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
@endsection
