<aside class="sidebar" id="appSidebar">
    <div class="brand">
        <a href="{{ route('admin.dashboard.iam.users.engineers') }}" class="brand-mark" aria-label="Charley Admin">
            <svg class="icon"><use href="/assets/icons/sprite.svg#icon-charley-admin-console-svg-viewbox-0"></use></svg>
        </a>
        <div>
            <a href="{{ route('admin.dashboard.iam.users.engineers') }}" class="brand-name">Charley</a>
            <div class="brand-sub">Admin Console</div>
        </div>
    </div>

    <div class="sidebar-search">
        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-k-overview-a-href-admin-d"></use></svg>
        <input type="text" placeholder="Search platform..." aria-label="Search platform">
        <span class="kbd">/</span>
    </div>

    <nav class="nav-scroll" aria-label="Main navigation">
        <div class="nav-group">
            <div class="nav-label">Overview</div>
            <a href="#" class="nav-item">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-dashboard-path-d-m14-5-17h9-5a2-2"></use></svg>
                Dashboard
            </a>
            <a href="#" class="nav-item">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-support-inbox-5-users-and"></use></svg>
                Support Inbox
                <span class="nav-badge urgent">5</span>
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Member Management</div>
            <a href="{{ route('admin.dashboard.iam.users.engineers') }}" class="nav-item {{ request()->routeIs('admin.dashboard.iam.users.engineers') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-user-management-12-svg-viewbox-0"></use></svg>
                Engineers
                <span class="nav-badge urgent">12</span>
            </a>
            <a href="{{ route('admin.dashboard.iam.users.partners') }}" class="nav-item {{ request()->routeIs('admin.dashboard.iam.users.partners') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-partner-management-path-d-m9-12"></use></svg>
                Partners
            </a>            <a href="{{ route('admin.dashboard.iam.users', ['member_view' => 'administrators']) }}" class="nav-item {{ request()->fullUrlIs('*member_view=administrators*') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-internal-admin-notes-not-visible"></use></svg>
                Administrators
            </a>
            <a href="{{ route('admin.dashboard.iam.verification-queue') }}" class="nav-item {{ request()->routeIs('admin.dashboard.iam.verification-queue') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-profile-verification-queue-5-svg"></use></svg>
                Profile Verification Queue
                <span class="nav-badge urgent">5</span>
            </a>
            <a href="{{ route('admin.dashboard.subscriptions.index') }}" class="nav-item {{ request()->routeIs('admin.dashboard.subscriptions.*') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-subscription-and-billing"></use></svg>
                Subscription &amp; Billing
            </a>
            <a href="#" class="nav-item">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-monthly-expert-recognition-svg-viewbox-0"></use></svg>
                Monthly Expert Recognition
            </a>
            <a href="{{ route('admin.dashboard.iam.account-penalty-freeze') }}" class="nav-item {{ request()->routeIs('admin.dashboard.iam.account-penalty-freeze*') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-account-penalty-and-freeze-3"></use></svg>
                Account Penalty &amp; Freeze
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Shared Services</div>
            <a href="{{ route('admin.dashboard.media-files.index') }}" class="nav-item {{ request()->routeIs('admin.dashboard.media-files.*') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-2-files-svg-viewbox-0-0"></use></svg>
                Media Files
            </a>
            <a href="{{ route('admin.dashboard.plant-types.index') }}" class="nav-item {{ request()->routeIs('admin.dashboard.plant-types.*') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-plant-focus-hydrogen-svg-viewbox-0"></use></svg>
                Plant Types
            </a>
            <a href="{{ route('admin.dashboard.taxonomy.index') }}" class="nav-item {{ request()->routeIs('admin.dashboard.taxonomy.*') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-library-and-pfd-content-path"></use></svg>
                Taxonomy
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Technical Q&amp;A</div>
            <a href="{{ route('admin.dashboard.qa.index') }}" class="nav-item {{ request()->routeIs('admin.dashboard.qa.*') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-qanda-management-rect-x-3-y-4"></use></svg>
                Q&amp;A Management
            </a>
            <a href="{{ route('admin.dashboard.qa.weekly-themes') }}" class="nav-item {{ request()->routeIs('admin.dashboard.qa.weekly-themes') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-weekly-theme-management-path-d-m3"></use></svg>
                Weekly Theme Management
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Charley Library</div>
            <a href="{{ route('admin.dashboard.library.items.index') }}" class="nav-item {{ request()->routeIs('admin.dashboard.library.*') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-library-and-pfd-content-path"></use></svg>
                Library &amp; PFD Content
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Platform</div>
            <a href="{{ route('admin.dashboard.admin-operations.index') }}" class="nav-item {{ request()->routeIs('admin.dashboard.admin-operations.*') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-dashboard-path-d-m14-5-17h9-5a2-2"></use></svg>
                Admin Operations
            </a>
            <a href="{{ route('admin.dashboard.feed-cms.index') }}" class="nav-item {{ request()->routeIs('admin.dashboard.feed-cms.*') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-announcement-management-charley-library-svg"></use></svg>
                Feed &amp; CMS
            </a>
            <a href="{{ route('admin.dashboard.subscriptions.index') }}" class="nav-item {{ request()->routeIs('admin.dashboard.subscriptions.*') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-subscription-and-billing"></use></svg>
                Subscription &amp; Billing
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        <div class="ai-status-pill">
            <span class="pulse-dot"></span>
            <div class="ai-status-text">
                AI Assistant - Operational
                <span>Backend console ready</span>
            </div>
        </div>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

