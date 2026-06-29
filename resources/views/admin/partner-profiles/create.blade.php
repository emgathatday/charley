@extends('templates.layouts.admin')

@section('content')
    <div class="container-fluid">
        @include('templates.components.alert-session')
        <h1 class="h3 mb-3">Create Partner Profile</h1>
        <div class="card">
            <form method="POST" action="{{ route('admin.dashboard.partner-profiles.store') }}">
                @csrf
                <div class="card-body">
                    @include('admin.partner-profiles.partials.form', ['partnerProfile' => null])
                </div>
                <div class="card-footer"><button class="btn btn-primary" type="submit">Save</button></div>
            </form>
        </div>
    </div>
@endsection
