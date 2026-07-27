@extends('layouts.app')

@section('title', 'Create Support Ticket')

@section('content_header')
    <div class="page-header"><div><div class="page-title">Create Support Ticket</div><div class="page-subtitle">Open an admin-created support request and optionally assign it to an admin.</div></div><div class="page-actions"><a href="{{ route('admin.dashboard.admin-operations.index') }}" class="btn"><i class="bi bi-arrow-left" aria-hidden="true"></i>Back to Operations</a></div></div>
@endsection

@section('content')
    @include('templates.components.alert-session')
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ route('admin.dashboard.admin-operations.support-tickets.store') }}" class="form-card">
        @csrf
        <div class="form-card-header"><div class="form-card-icon blue"><i class="bi bi-life-preserver"></i></div><div><div class="form-card-title">Ticket Details</div><div class="form-card-sub">Support inbox placeholder in the new dashboard UI.</div></div></div>
        <div class="form-card-body"><div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="user_id">User</label><select id="user_id" class="form-select" name="user_id" required>@foreach ($users as $user)<option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->email }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label" for="subject">Subject</label><input id="subject" type="text" class="form-control" name="subject" value="{{ old('subject') }}" placeholder="Support request subject" required></div>
            <div class="col-md-4"><label class="form-label" for="category">Category</label><select id="category" class="form-select" name="category" required>@foreach (['subscription_support','technical_issue','content_approval','account_issue','other'] as $category)<option value="{{ $category }}" @selected(old('category') === $category)>{{ str_replace('_', ' ', $category) }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label" for="priority">Priority</label><select id="priority" class="form-select" name="priority" required>@foreach (['normal','low','high','urgent'] as $priority)<option value="{{ $priority }}" @selected(old('priority', 'normal') === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label" for="assigned_to">Assigned admin</label><select id="assigned_to" class="form-select" name="assigned_to"><option value="">Unassigned</option>@foreach ($users->where('role', 'admin') as $user)<option value="{{ $user->id }}" @selected(old('assigned_to') == $user->id)>{{ $user->email }}</option>@endforeach</select></div>
            <div class="col-12"><label class="form-label" for="description">Description</label><textarea id="description" class="form-control" name="description" rows="5" required>{{ old('description') }}</textarea></div>
        </div></div>
        <div class="page-actions" style="justify-content:flex-end;"><a href="{{ route('admin.dashboard.admin-operations.index') }}" class="btn">Cancel</a><button class="btn btn-primary" type="submit"><i class="bi bi-check2" aria-hidden="true"></i>Save Ticket</button></div>
    </form>
@endsection
