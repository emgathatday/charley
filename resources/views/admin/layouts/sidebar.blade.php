<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="{{ route('admin.dashboard.partner-profiles.index') }}" class="brand-link">
        <span class="brand-text font-weight-light">Charley Admin</span>
    </a>

    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard.partner-profiles.index') }}"
                       class="nav-link {{ request()->routeIs('admin.dashboard.partner-profiles.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-handshake"></i>
                        <p>Partner Profiles</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
