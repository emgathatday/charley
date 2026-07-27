@extends('layouts.app')

@section('title', 'Edit Partner Profile')

@section('content_header')
    <div class="page-header">
        <div>
            <div class="page-title">Edit Partner Profile</div>
            <div class="page-subtitle">{{ $partnerProfile->company_name }} - update company details, taxonomy, and moderation state.</div>
        </div>
        <div class="page-actions"><a href="{{ route('admin.dashboard.partner-profiles.show', $partnerProfile) }}" class="btn"><i class="bi bi-eye" aria-hidden="true"></i>View</a><a href="{{ route('admin.dashboard.partner-profiles.index') }}" class="btn"><i class="bi bi-arrow-left" aria-hidden="true"></i>Back</a></div>
    </div>
@endsection

@section('content')
    @include('templates.components.alert-session')

    <form method="POST" action="{{ route('admin.dashboard.partner-profiles.update', $partnerProfile) }}">
        @csrf
        @method('PUT')
        @include('admin.partner-profiles.partials.form', ['partnerProfile' => $partnerProfile])
        <div class="page-actions" style="justify-content:space-between;margin-top:18px;">
            <button class="btn btn-primary" type="submit"><i class="bi bi-check2" aria-hidden="true"></i>Save Changes</button>
            <button type="submit" form="delete-partner-profile-{{ $partnerProfile->id }}" class="btn"><i class="bi bi-trash" aria-hidden="true"></i>Delete</button>
        </div>
    </form>

    <form id="delete-partner-profile-{{ $partnerProfile->id }}" method="POST" action="{{ route('admin.dashboard.partner-profiles.destroy', $partnerProfile) }}" onsubmit="return confirm('Delete this partner profile?');">
        @csrf
        @method('DELETE')
    </form>
@endsection
