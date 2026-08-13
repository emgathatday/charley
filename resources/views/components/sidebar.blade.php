<aside class="sidebar" id="appSidebar">
    <div class="brand">
        <a href="{{ route('admin.dashboard.iam.users.engineers') }}" class="brand-mark" aria-label="Charley Admin">
            <svg class="icon"><use href="/assets/icons/sprite.svg#icon-charley-logo"></use></svg>
        </a>
        <div>
            <a href="{{ route('admin.dashboard.iam.users.engineers') }}" class="brand-name">Charley</a>
            <div class="brand-sub">Admin Console</div>
        </div>
    </div>

    <div class="sidebar-search">
        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-search-2"></use></svg>
        <input type="text" placeholder="Search platform..." aria-label="Search platform">
        <span class="kbd">/</span>
    </div>

    <nav class="nav-scroll" aria-label="Main navigation">
        <div class="nav-group">
            <div class="nav-label">Overview</div>
            <a href="#" class="nav-item">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-dashboard"></use></svg>
                Dashboard
            </a>
            <a href="#" class="nav-item">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-support-inbox"></use></svg>
                Support Inbox
                <span class="nav-badge urgent">5</span>
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Member Management</div>
            <a href="{{ route('admin.dashboard.iam.users.engineers') }}" class="nav-item {{ request()->routeIs('admin.dashboard.iam.users.engineers') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-users-5"></use></svg>
                Engineers
                <span class="nav-badge urgent">12</span>
            </a>
            <a href="{{ route('admin.dashboard.iam.users.partners') }}" class="nav-item {{ request()->routeIs('admin.dashboard.iam.users.partners') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-partners"></use></svg>
                Partners
            </a>
            <a href="{{ route('admin.dashboard.iam.users', ['member_view' => 'administrators']) }}" class="nav-item {{ request()->fullUrlIs('*member_view=administrators*') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-edit-5"></use></svg>
                Administrators
            </a>
            <a href="{{ route('admin.dashboard.iam.verification-queue') }}" class="nav-item {{ request()->routeIs('admin.dashboard.iam.verification-queue') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-verification-queue"></use></svg>
                Profile Verification Queue
                <span class="nav-badge urgent">5</span>
            </a>
            <a href="{{ route('admin.dashboard.subscriptions.index') }}" class="nav-item {{ request()->routeIs('admin.dashboard.subscriptions.*') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-billing"></use></svg>
                Subscription &amp; Billing
            </a>
            <a href="#" class="nav-item">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-expert-recognition"></use></svg>
                Monthly Expert Recognition
            </a>
            <a href="{{ route('admin.dashboard.iam.account-penalty-freeze') }}" class="nav-item {{ request()->routeIs('admin.dashboard.iam.account-penalty-freeze*') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-lock"></use></svg>
                Account Penalty &amp; Freeze
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Shared Services</div>
            <a href="{{ route('admin.dashboard.media-files.index') }}" class="nav-item {{ request()->routeIs('admin.dashboard.media-files.*') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-files"></use></svg>
                Media Files
            </a>
            <a href="{{ route('admin.dashboard.plant-types.index') }}" class="nav-item {{ request()->routeIs('admin.dashboard.plant-types.*') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-plant-focus-hydrogen"></use></svg>
                Plant Types
            </a>
            <a href="{{ route('admin.dashboard.taxonomy.index') }}" class="nav-item {{ request()->routeIs('admin.dashboard.taxonomy.*') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-library"></use></svg>
                Taxonomy
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Technical Q&amp;A</div>
            <a href="{{ route('admin.dashboard.qa.index') }}" class="nav-item {{ request()->routeIs('admin.dashboard.qa.*') && ! request()->routeIs('admin.dashboard.qa.weekly-themes') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-qa"></use></svg>
                Q&amp;A Management
            </a>
            <a href="{{ route('admin.dashboard.qa.weekly-themes') }}" class="nav-item {{ request()->routeIs('admin.dashboard.qa.weekly-themes') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-weekly-theme"></use></svg>
                Weekly Theme Management
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Charley Library</div>
            <a href="{{ route('admin.dashboard.library.items.index') }}" class="nav-item {{ request()->routeIs('admin.dashboard.library.items.*') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-library"></use></svg>
                Library &amp; PFD Content
            </a>
            <a href="{{ route('admin.dashboard.library.knowledge-domains.index') }}" class="nav-item {{ request()->routeIs('admin.dashboard.library.knowledge-domains.*') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-settings-2"></use></svg>
                Knowledge Domains
            </a>
            
        </div>

        <div class="nav-group">
            <div class="nav-label">Platform</div>
            <a href="{{ route('admin.dashboard.admin-operations.index') }}" class="nav-item {{ request()->routeIs('admin.dashboard.admin-operations.*') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-dashboard"></use></svg>
                Admin Operations
            </a>
            <a href="{{ route('admin.dashboard.feed-cms.index') }}" class="nav-item {{ request()->routeIs('admin.dashboard.feed-cms.*') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-announcements"></use></svg>
                Feed &amp; CMS
            </a>
            <a href="{{ route('admin.dashboard.subscriptions.index') }}" class="nav-item {{ request()->routeIs('admin.dashboard.subscriptions.*') ? 'active' : '' }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-billing"></use></svg>
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
