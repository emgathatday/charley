@extends('templates.layouts.admin')

@section('content')
    <div class="container-fluid">
        @include('templates.components.alert-session')
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div><h1 class="h3 mb-0">Partner Detail #{{ $partnerProfile->id }}</h1><p class="text-muted mb-0">{{ $partnerProfile->company_name }}</p></div>
            <div>
                <form method="POST" action="{{ route('admin.dashboard.partner-profiles.approve', $partnerProfile) }}" class="d-inline">@csrf<button class="btn btn-success" type="submit">Approve</button></form>
                <form method="POST" action="{{ route('admin.dashboard.partner-profiles.reject', $partnerProfile) }}" class="d-inline">@csrf<button class="btn btn-outline-danger" type="submit">Reject</button></form>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-4"><div class="card"><div class="card-header"><h3 class="card-title">Company</h3></div><div class="card-body"><p><strong>Tier:</strong> {{ $partnerProfile->partner_tier ?? '-' }}</p><p><strong>Plant Type:</strong> {{ $partnerProfile->plantType?->name ?? '-' }}</p><p><strong>Status:</strong> {{ $partnerProfile->approval_status }}</p><p><strong>Contact:</strong> {{ $partnerProfile->contact_email ?? '-' }}</p><p><strong>Logo Media ID:</strong> {{ $partnerProfile->logo_media_id ?? '-' }}</p></div></div></div>
            <div class="col-lg-8">
                <div class="card"><div class="card-header"><h3 class="card-title">Products</h3></div><div class="card-body table-responsive p-0"><table class="table table-sm mb-0"><thead><tr><th>Name</th><th>Type</th><th>Status</th></tr></thead><tbody>@forelse ($partnerProfile->products as $product)<tr><td>{{ $product->name }}</td><td>{{ $product->item_type }}</td><td>{{ $product->is_active ? 'Active' : 'Inactive' }}</td></tr>@empty<tr><td colspan="3" class="text-muted">No products.</td></tr>@endforelse</tbody></table></div></div>
                <div class="card"><div class="card-header"><h3 class="card-title">Presentations</h3></div><div class="card-body table-responsive p-0"><table class="table table-sm mb-0"><thead><tr><th>Title</th><th>Status</th><th>Plant Type ID</th></tr></thead><tbody>@forelse ($partnerProfile->presentations as $presentation)<tr><td>{{ $presentation->title }}</td><td>{{ $presentation->status }}</td><td>{{ $presentation->plant_type_id ?? '-' }}</td></tr>@empty<tr><td colspan="3" class="text-muted">No presentations.</td></tr>@endforelse</tbody></table></div></div>
                <div class="card"><div class="card-header"><h3 class="card-title">Members</h3></div><div class="card-body table-responsive p-0"><table class="table table-sm mb-0"><thead><tr><th>User</th><th>Role</th><th>Status</th></tr></thead><tbody>@forelse ($partnerProfile->members as $member)<tr><td>{{ $member->user?->email ?? $member->user_id }}</td><td>{{ $member->member_role }}</td><td>{{ $member->status }}</td></tr>@empty<tr><td colspan="3" class="text-muted">No members.</td></tr>@endforelse</tbody></table></div></div>
            </div>
        </div>
    </div>
@endsection
