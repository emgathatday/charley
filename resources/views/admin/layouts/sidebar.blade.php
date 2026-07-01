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

                <li class="nav-item {{ request()->routeIs('admin.dashboard.plant-types.*') ? 'menu-open' : '' }}">
                    <a href="{{ route('admin.dashboard.plant-types.index') }}"
                       class="nav-link {{ request()->routeIs('admin.dashboard.plant-types.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-seedling"></i>
                        <p>
                            Plant Types
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard.plant-types.index') }}"
                               class="nav-link {{ request()->routeIs('admin.dashboard.plant-types.index') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>All Plant Types</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard.plant-types.create') }}"
                               class="nav-link {{ request()->routeIs('admin.dashboard.plant-types.create') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Create Plant Type</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item {{ request()->routeIs('admin.dashboard.subscriptions.*') ? 'menu-open' : '' }}">
                    <a href="{{ route('admin.dashboard.subscriptions.index') }}"
                       class="nav-link {{ request()->routeIs('admin.dashboard.subscriptions.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-credit-card"></i>
                        <p>
                            Subscriptions
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard.subscriptions.index') }}"
                               class="nav-link {{ request()->routeIs('admin.dashboard.subscriptions.*') && ! request()->hasAny(['payment_status', 'quota_period']) ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Subscriptions</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard.subscriptions.index', ['payment_status' => 'pending']) }}"
                               class="nav-link {{ request()->routeIs('admin.dashboard.subscriptions.*') && request()->has('payment_status') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Payments</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard.subscriptions.index', ['quota_period' => now()->format('Y-m')]) }}"
                               class="nav-link {{ request()->routeIs('admin.dashboard.subscriptions.*') && request()->has('quota_period') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Quotas</p>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
    </div>
</aside>