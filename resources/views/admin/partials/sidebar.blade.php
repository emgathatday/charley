<li class="nav-header">HANDBOOK</li>
<li class="nav-item {{ request()->routeIs('admin.dashboard.handbook.*') ? 'menu-open' : '' }}">
    <a href="{{ route('admin.dashboard.handbook.index') }}" class="nav-link {{ request()->routeIs('admin.dashboard.handbook.*') ? 'active' : '' }}">
        <i class="nav-icon bi bi-map"></i>
        <p>Handbook<i class="nav-arrow bi bi-chevron-right"></i></p>
    </a>
    <ul class="nav nav-treeview">
        <li class="nav-item">
            <a href="{{ route('admin.dashboard.handbook.index') }}" class="nav-link {{ request()->routeIs('admin.dashboard.handbook.index') ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle"></i>
                <p>Overview</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.dashboard.handbook.create') }}" class="nav-link {{ request()->routeIs('admin.dashboard.handbook.create') ? 'active' : '' }}">
                <i class="nav-icon bi bi-circle"></i>
                <p>Create Article</p>
            </a>
        </li>
    </ul>
</li>
