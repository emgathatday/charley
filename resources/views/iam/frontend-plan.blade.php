@extends('layouts.app')

@section('title', 'IAM Frontend Plan')

@section('content_header')
    <div class="page-header">
        <div>
            <div class="page-title">IAM Frontend Plan</div>
            <div class="page-subtitle">Static inventory for public and authenticated IAM experiences that do not yet have route-backed Laravel views.</div>
        </div>
        <div class="page-actions"><a class="btn" href="{{ route('admin.dashboard.iam.users') }}"><i class="bi bi-people" aria-hidden="true"></i>User Management</a></div>
    </div>
@endsection

@section('content')
    <div class="stats-row" style="grid-template-columns:repeat(4,minmax(0,1fr));padding:0;margin-bottom:22px;">
        <div class="stat-card blue"><div class="stat-label">Frontend IAM Pages</div><div class="stat-value">5 planned</div></div>
        <div class="stat-card amber"><div class="stat-label">Concrete Mockups</div><div class="stat-value">0 frontend</div></div>
        <div class="stat-card emerald"><div class="stat-label">Admin References</div><div class="stat-value">3 views</div></div>
        <div class="stat-card indigo"><div class="stat-label">Integration</div><div class="stat-value">pending</div></div>
    </div>

    <div class="table-card">
        <div class="table-header">
            <div><div class="table-title">Frontend IAM Route Inventory</div><div class="table-meta">Placeholders remain static until concrete route/controller contracts exist.</div></div>
        </div>
        <div class="table-responsive">
            <table class="qa-table">
                <thead><tr><th>Page</th><th>Route</th><th>Rendering</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ([
                        ['Registration', '/register', 'Public SSR', 'planned'],
                        ['Login', '/login', 'Public SSR', 'planned'],
                        ['Verification submission', '/account/verification', 'Authenticated SPA', 'planned'],
                        ['Account security', '/account/security', 'Authenticated SPA', 'planned'],
                        ['Activity history', '/account/activity', 'Authenticated SPA', 'planned'],
                    ] as $page)
                        <tr><td>{{ $page[0] }}</td><td>{{ $page[1] }}</td><td>{{ $page[2] }}</td><td><span class="badge badge-info">{{ $page[3] }}</span></td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="detail-grid" style="margin-top:20px;">
        <section class="form-card">
            <div class="form-card-header"><div class="form-card-icon amber"><i class="bi bi-exclamation-triangle"></i></div><div><div class="form-card-title">Integration Gap</div><div class="form-card-sub">No concrete Next.js app directory exists in this Laravel workspace yet.</div></div></div>
            <div class="form-card-body"><div class="info-grid"><div class="info-item"><div class="info-label">Backend References</div><div class="info-value">users.blade.php, verification-queue.blade.php, user-security.blade.php</div></div><div class="info-item"><div class="info-label">Binding Rule</div><div class="info-value">Keep static until routes and controllers exist</div></div></div></div>
        </section>
    </div>
@endsection
