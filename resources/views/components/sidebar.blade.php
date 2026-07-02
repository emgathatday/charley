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
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard.iam.users') }}" class="nav-link {{ request()->routeIs('admin.dashboard.iam.users') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-people"></i>
                        <p>Users</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard.iam.verification-queue') }}" class="nav-link {{ request()->routeIs('admin.dashboard.iam.verification-queue') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-person-check"></i>
                        <p>Verification Queue</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard.iam.user-security') }}" class="nav-link {{ request()->routeIs('admin.dashboard.iam.user-security') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-shield-lock"></i>
                        <p>User Security</p>
                    </a>
                </li>

                <li class="nav-header">SHARED SERVICES</li>
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard.media-files.index') }}" class="nav-link {{ request()->routeIs('admin.dashboard.media-files.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-folder2-open"></i>
                        <p>Media Files</p>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('admin.dashboard.plant-types.*') ? 'menu-open' : '' }}">
                    <a href="{{ route('admin.dashboard.plant-types.index') }}" class="nav-link {{ request()->routeIs('admin.dashboard.plant-types.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-diagram-3"></i>
                        <p>Plant Types<i class="nav-arrow bi bi-chevron-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard.plant-types.index') }}" class="nav-link {{ request()->routeIs('admin.dashboard.plant-types.index') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>All Plant Types</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard.plant-types.create') }}" class="nav-link {{ request()->routeIs('admin.dashboard.plant-types.create') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Create Plant Type</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item {{ request()->routeIs('admin.dashboard.taxonomy.*') ? 'menu-open' : '' }}">
                    <a href="{{ route('admin.dashboard.taxonomy.index') }}" class="nav-link {{ request()->routeIs('admin.dashboard.taxonomy.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-tags"></i>
                        <p>Taxonomy<i class="nav-arrow bi bi-chevron-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard.taxonomy.index') }}" class="nav-link {{ request()->routeIs('admin.dashboard.taxonomy.index') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>All Tags</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard.taxonomy.create') }}" class="nav-link {{ request()->routeIs('admin.dashboard.taxonomy.create') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Create Tag</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-header">PARTNERS</li>
                <li class="nav-item {{ request()->routeIs('admin.dashboard.partner-profiles.*') ? 'menu-open' : '' }}">
                    <a href="{{ route('admin.dashboard.partner-profiles.index') }}" class="nav-link {{ request()->routeIs('admin.dashboard.partner-profiles.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-buildings"></i>
                        <p>Partner Profiles<i class="nav-arrow bi bi-chevron-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard.partner-profiles.index') }}" class="nav-link {{ request()->routeIs('admin.dashboard.partner-profiles.index') && ! request()->has('approval_status') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>All Profiles</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard.partner-profiles.index', ['approval_status' => 'pending']) }}" class="nav-link {{ request()->routeIs('admin.dashboard.partner-profiles.index') && request('approval_status') === 'pending' ? 'active' : '' }}">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Pending Approval</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard.partner-profiles.create') }}" class="nav-link {{ request()->routeIs('admin.dashboard.partner-profiles.create') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Create Profile</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-header">ADMIN OPERATIONS</li>
                <li class="nav-item {{ request()->routeIs('admin.dashboard.admin-operations.*') ? 'menu-open' : '' }}">
                    <a href="{{ route('admin.dashboard.admin-operations.index') }}" class="nav-link {{ request()->routeIs('admin.dashboard.admin-operations.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-kanban"></i>
                        <p>Operations<i class="nav-arrow bi bi-chevron-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="{{ route('admin.dashboard.admin-operations.index') }}" class="nav-link {{ request()->routeIs('admin.dashboard.admin-operations.index') ? 'active' : '' }}"><i class="nav-icon bi bi-circle"></i><p>Overview</p></a></li>
                        <li class="nav-item"><a href="{{ route('admin.dashboard.admin-operations.support-tickets.create') }}" class="nav-link {{ request()->routeIs('admin.dashboard.admin-operations.support-tickets.create') ? 'active' : '' }}"><i class="nav-icon bi bi-circle"></i><p>Create Ticket</p></a></li>
                        <li class="nav-item"><a href="{{ route('admin.dashboard.admin-operations.account-penalties.create') }}" class="nav-link {{ request()->routeIs('admin.dashboard.admin-operations.account-penalties.create') ? 'active' : '' }}"><i class="nav-icon bi bi-circle"></i><p>Create Penalty</p></a></li>
                    </ul>
                </li>
                <li class="nav-header">FEED & CMS</li>
                <li class="nav-item {{ request()->routeIs('admin.dashboard.feed-cms.*') ? 'menu-open' : '' }}">
                    <a href="{{ route('admin.dashboard.feed-cms.index') }}" class="nav-link {{ request()->routeIs('admin.dashboard.feed-cms.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-newspaper"></i>
                        <p>Feed CMS<i class="nav-arrow bi bi-chevron-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="{{ route('admin.dashboard.feed-cms.index') }}" class="nav-link {{ request()->routeIs('admin.dashboard.feed-cms.index') ? 'active' : '' }}"><i class="nav-icon bi bi-circle"></i><p>Pages & Priorities</p></a></li>
                        <li class="nav-item"><a href="{{ route('admin.dashboard.feed-cms.pages.create') }}" class="nav-link {{ request()->routeIs('admin.dashboard.feed-cms.pages.create') ? 'active' : '' }}"><i class="nav-icon bi bi-circle"></i><p>Create Page</p></a></li>
                    </ul>
                </li>                <li class="nav-header">SUBSCRIPTIONS</li>
                <li class="nav-item {{ request()->routeIs('admin.dashboard.subscriptions.*') ? 'menu-open' : '' }}">
                    <a href="{{ route('admin.dashboard.subscriptions.index') }}" class="nav-link {{ request()->routeIs('admin.dashboard.subscriptions.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-credit-card"></i>
                        <p>Subscriptions<i class="nav-arrow bi bi-chevron-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard.subscriptions.index') }}" class="nav-link {{ request()->routeIs('admin.dashboard.subscriptions.index') && ! request()->hasAny(['subscription_status', 'payment_status', 'quota_period']) ? 'active' : '' }}">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Overview</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard.subscriptions.index', ['subscription_status' => 'pending_approval']) }}" class="nav-link {{ request()->routeIs('admin.dashboard.subscriptions.index') && request('subscription_status') === 'pending_approval' ? 'active' : '' }}">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Pending Subscriptions</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard.subscriptions.index', ['payment_status' => 'pending']) }}" class="nav-link {{ request()->routeIs('admin.dashboard.subscriptions.index') && request('payment_status') === 'pending' ? 'active' : '' }}">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Payments</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard.subscriptions.index', ['quota_period' => now()->format('Y-m')]) }}" class="nav-link {{ request()->routeIs('admin.dashboard.subscriptions.index') && request()->has('quota_period') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Quotas</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard.subscriptions.tiers.create') }}" class="nav-link {{ request()->routeIs('admin.dashboard.subscriptions.tiers.create') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Create Tier</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard.subscriptions.member-plans.create') }}" class="nav-link {{ request()->routeIs('admin.dashboard.subscriptions.member-plans.create') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Create Member Plan</p>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
    </div>
</aside>



