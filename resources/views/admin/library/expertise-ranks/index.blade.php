@extends('layouts.master')

@section('title', 'Expertise Ranks')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Expertise Ranks</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.library.index') }}">Library</a></li><li class="breadcrumb-item active">Expertise Ranks</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        @include('templates.components.alert-session')
        <div class="row g-3">
            <div class="col-lg-8"><div class="card card-outline card-primary"><div class="card-header"><h3 class="card-title mb-0">Current Ranks</h3></div><div class="card-body p-0"><table class="table table-hover align-middle mb-0"><thead><tr><th>User</th><th>Rank</th><th>Scope</th><th>Source</th><th>Assigned</th></tr></thead><tbody>@forelse ($ranks as $rank)<tr><td>{{ $rank->user?->email ?? '-' }}</td><td><span class="badge text-bg-primary">{{ $rank->expertiseLevel?->name }}</span></td><td>{{ $rank->handbookCategory?->title ?? $rank->plantType?->name ?? 'Platform' }}</td><td>{{ Str::headline($rank->source) }}</td><td>{{ optional($rank->assigned_at)->format('Y-m-d H:i') }}</td></tr>@empty<tr><td colspan="5" class="text-center text-body-secondary py-4">No current ranks.</td></tr>@endforelse</tbody></table></div>@if ($ranks->hasPages())<div class="card-footer">{{ $ranks->links() }}</div>@endif</div></div>
            <div class="col-lg-4"><div class="card card-outline card-success"><div class="card-header"><h3 class="card-title mb-0">Assign CV Review Rank</h3></div><form method="POST" action="{{ route('admin.dashboard.library.expertise-ranks.store') }}">@csrf<div class="card-body"><div class="mb-3"><label class="form-label" for="user_id">User</label><select id="user_id" name="user_id" class="form-select" required>@foreach ($users as $user)<option value="{{ $user->id }}">{{ $user->email }}</option>@endforeach</select></div><div class="mb-3"><label class="form-label" for="expertise_level_id">Rank</label><select id="expertise_level_id" name="expertise_level_id" class="form-select" required>@foreach ($levels as $level)<option value="{{ $level->id }}">{{ $level->name }}</option>@endforeach</select></div><div class="mb-3"><label class="form-label" for="plant_type_id">Plant Scope</label><select id="plant_type_id" name="plant_type_id" class="form-select"><option value="">Platform-wide</option>@foreach ($plantTypes as $plantType)<option value="{{ $plantType->id }}">{{ $plantType->name }}</option>@endforeach</select></div><div><label class="form-label" for="notes">Notes</label><textarea id="notes" name="notes" rows="3" class="form-control"></textarea></div></div><div class="card-footer"><button class="btn btn-success" type="submit"><i class="bi bi-award me-1"></i>Assign</button></div></form></div></div>
        </div>
    </div></div>
@endsection
