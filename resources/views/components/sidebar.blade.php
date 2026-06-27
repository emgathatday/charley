<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="{{ route('admin.dashboard.iam.users') }}" class="brand-link">
            <i class="brand-image bi bi-shield-lock opacity-75 shadow"></i>
            <span class="brand-text fw-light">Charley Admin</span>
        </a>
    </div>
    <div class="sidebar-wrapper">
        <nav class="mt-2" aria-label="Main navigation">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" data-accordion="false">
                <li class="nav-header">IDENTITY ACCESS</li>
                <li class="nav-item"><a href="{{ route('admin.dashboard.iam.users') }}" class="nav-link {{ request()->routeIs('admin.dashboard.iam.users') ? 'active' : '' }}"><i class="nav-icon bi bi-people"></i><p>Users</p></a></li>
                <li class="nav-item"><a href="{{ route('admin.dashboard.iam.verification-queue') }}" class="nav-link {{ request()->routeIs('admin.dashboard.iam.verification-queue') ? 'active' : '' }}"><i class="nav-icon bi bi-person-check"></i><p>Verification Queue<span class="badge text-bg-warning ms-2">36</span></p></a></li>
                <li class="nav-item"><a href="{{ route('admin.dashboard.iam.user-security') }}" class="nav-link {{ request()->routeIs('admin.dashboard.iam.user-security') ? 'active' : '' }}"><i class="nav-icon bi bi-shield-lock"></i><p>User Security</p></a></li>
            </ul>
        </nav>
    </div>
</aside>

