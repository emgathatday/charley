@extends('templates.layouts.admin')

@section('content')
    <div class="container-fluid">
        @include('templates.components.alert-session')

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-0">Partner Profiles</h1>
                <p class="text-muted mb-0">Review partner companies, approvals, products and members.</p>
            </div>
            <a href="{{ route('admin.dashboard.partner-profiles.create') }}" class="btn btn-primary">Create Partner</a>
        </div>

        <form method="GET" action="{{ route('admin.dashboard.partner-profiles.index') }}" class="card mb-3">
            <div class="card-body row">
                <div class="col-md-4"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search company"></div>
                <div class="col-md-3"><select class="form-control" name="approval_status"><option value="">All statuses</option>@foreach (['pending','approved','rejected','suspended'] as $status)<option value="{{ $status }}" @selected(request('approval_status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
                <div class="col-md-3"><select class="form-control" name="plant_type_id"><option value="">All plant types</option>@foreach ($plantTypes as $plantType)<option value="{{ $plantType->id }}" @selected((string) request('plant_type_id') === (string) $plantType->id)>{{ $plantType->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><button class="btn btn-primary btn-block" type="submit">Filter</button></div>
            </div>
        </form>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Partner queue</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap mb-0">
                    <thead><tr><th>Company</th><th>Tier</th><th>Plant Type</th><th>Status</th><th>Products</th><th>Presentations</th><th>Members</th><th class="text-right">Actions</th></tr></thead>
                    <tbody>
                        @forelse ($partnerProfiles as $partnerProfile)
                            <tr>
                                <td>{{ $partnerProfile->company_name }}</td>
                                <td>{{ $partnerProfile->partner_tier ?? '-' }}</td>
                                <td>{{ $partnerProfile->plantType?->name ?? '-' }}</td>
                                <td><span class="badge badge-info">{{ $partnerProfile->approval_status }}</span></td>
                                <td>{{ $partnerProfile->products_count }}</td>
                                <td>{{ $partnerProfile->presentations_count }}</td>
                                <td>{{ $partnerProfile->members_count }}</td>
                                <td class="text-right"><a href="{{ route('admin.dashboard.partner-profiles.show', $partnerProfile) }}" class="btn btn-sm btn-outline-primary">View</a> <a href="{{ route('admin.dashboard.partner-profiles.edit', $partnerProfile) }}" class="btn btn-sm btn-outline-secondary">Edit</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">No partner profiles found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($partnerProfiles->hasPages())<div class="card-footer">{{ $partnerProfiles->links() }}</div>@endif
        </div>
    </div>
@endsection
