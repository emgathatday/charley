<nav class="app-header navbar navbar-expand bg-body">
    @php
        $currentUser = auth()->user();
        $displayName = $currentUser
            ? (trim(($currentUser->first_name ?? '').' '.($currentUser->last_name ?? '')) ?: ($currentUser->username ?? $currentUser->email ?? 'Admin'))
            : 'Admin';
        $initials = collect(explode(' ', $displayName))->filter()->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') ?: 'AD';
    @endphp

    <div class="container-fluid">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button"><i class="bi bi-list"></i></a>
            </li>
            <li class="nav-item d-none d-md-block">
                <a href="{{ route('admin.dashboard.iam.users') }}" class="nav-link"><i class="bi bi-shield-lock me-1" aria-hidden="true"></i>IAM Admin</a>
            </li>
        </ul>

        <ul class="navbar-nav ms-auto">
            <li class="nav-item"><a class="nav-link" href="#" title="Search"><i class="bi bi-search"></i></a></li>
            <li class="nav-item dropdown">
                <a class="nav-link" data-bs-toggle="dropdown" href="#"><i class="bi bi-bell-fill"></i><span class="navbar-badge badge text-bg-warning">36</span></a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                    <span class="dropdown-item dropdown-header">36 verification items</span>
                    <div class="dropdown-divider"></div>
                    <a href="{{ route('admin.dashboard.iam.verification-queue') }}" class="dropdown-item"><i class="bi bi-person-check me-2"></i>Review pending identities</a>
                </div>
            </li>
            <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                    <span class="user-image rounded-circle shadow d-inline-flex align-items-center justify-content-center bg-primary text-white text-uppercase" style="width: 2rem; height: 2rem;">{{ $initials }}</span>
                    <span class="d-none d-md-inline">{{ $displayName }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                    <li class="user-header text-bg-primary">
                        <span class="rounded-circle bg-white text-primary d-inline-flex align-items-center justify-content-center text-uppercase mb-2" style="width: 4.25rem; height: 4.25rem; font-size: 1.5rem;">{{ $initials }}</span>
                        <p>
                            {{ $displayName }}
                            <small>{{ $currentUser?->email ?? 'No email' }}</small>
                            <small>{{ $currentUser?->role ?? 'admin' }} · {{ $currentUser?->status ?? 'active' }}</small>
                        </p>
                    </li>
                    <li class="user-footer">
                        @if ($currentUser)
                            <a href="{{ route('admin.dashboard.iam.user-security', $currentUser) }}" class="btn btn-outline-secondary">Profile</a>
                        @else
                            <a href="#" class="btn btn-outline-secondary disabled" aria-disabled="true">Profile</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="float-end">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger">Sign out</button>
                        </form>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
