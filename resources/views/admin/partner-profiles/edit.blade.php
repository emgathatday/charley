@extends('templates.layouts.admin')

@section('content')
    <div class="container-fluid">
        @include('templates.components.alert-session')
        <h1 class="h3 mb-3">Edit Partner Profile #{{ $partnerProfile->id }}</h1>
        <div class="card">
            <form method="POST" action="{{ route('admin.dashboard.partner-profiles.update', $partnerProfile) }}">
                @csrf
                @method('PUT')
                <div class="card-body">
                    @include('admin.partner-profiles.partials.form', ['partnerProfile' => $partnerProfile])
                </div>
                <div class="card-footer"><button class="btn btn-primary" type="submit">Save</button></div>
            </form>
        </div>
    </div>
@endsection
