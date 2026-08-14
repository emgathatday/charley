<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $title ?? 'Dashboard') | Charley</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Inter+Tight:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/style.min.css') }}">
    @stack('styles')
</head>
<body>
    <div class="app">
        @include('components.sidebar')

        <div class="main">
            <div class="topbar">
                <div class="topbar-left">
                    <button class="hamburger-btn" id="hamburgerBtn" type="button" onclick="toggleSidebar()" aria-label="Toggle navigation">
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-menu"></use></svg>
                    </button>
                    <div class="breadcrumb">
                        <span>Charley</span>
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-chevron-right"></use></svg>
                        <span class="current">@yield('title', $title ?? 'Dashboard')</span>
                    </div>
                </div>

                <div class="topbar-actions">
                    <div class="dropdown-wrap">
                        <button class="icon-btn" id="notifBtn" type="button" onclick="toggleDropdown('notifMenu', this)" aria-label="Notifications" aria-expanded="false">
                            <svg class="icon"><use href="/assets/icons/sprite.svg#icon-notifications-mark-all-read-s"></use></svg>
                            <span class="dot"></span>
                        </button>
                        <div class="dropdown-menu notif-menu" id="notifMenu">
                            <div class="dropdown-head"><span>Notifications</span><span class="dropdown-head-action">Mark all read</span></div>
                            <div class="notif-item">
                                <div class="notif-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-clock"></use></svg></div>
                                <div><div class="notif-text">9 verifications exceeded the 48h SLA</div><div class="notif-time">12 minutes ago</div></div>
                            </div>
                            <div class="notif-item">
                                <div class="notif-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-user-k-habib-flagged"></use></svg></div>
                                <div><div class="notif-text">New answer flagged in &quot;Reformer tube failure&quot;</div><div class="notif-time">38 minutes ago</div></div>
                            </div>
                            <div class="dropdown-foot">View all notifications</div>
                        </div>
                    </div>

                    <div class="dropdown-wrap">
                        <button class="icon-btn" type="button" onclick="toggleDropdown('settingsMenu', this)" aria-label="Settings" aria-expanded="false">
                            <svg class="icon"><use href="/assets/icons/sprite.svg#icon-settings-2"></use></svg>
                        </button>
                        <div class="dropdown-menu" id="settingsMenu">
                            <div class="dropdown-head"><span>Settings</span></div>
                            <a class="settings-item" href="{{ route('admin.dashboard.iam.users.admin-profile') }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-users-3"></use></svg>Account settings</a>
                            <a class="settings-item" href="{{ route('admin.dashboard.iam.users.admin-profile', ['section' => 'security']) }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-lock"></use></svg>Security &amp; access</a>
                        </div>
                    </div>

                    <div class="topbar-divider"></div>

                    @php
                        $admin = auth()->user();
                        $adminName = $admin?->name ?? trim(($admin?->first_name ?? '').' '.($admin?->last_name ?? '')) ?: ($admin?->username ?? 'Sara Reyes');
                        $adminEmail = $admin?->email ?? 'sara.reyes@charleyplatform.com';
                        $adminInitials = collect(explode(' ', trim($adminName)))->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('') ?: 'SR';
                    @endphp
                    <div class="dropdown-wrap">
                        <button class="admin-profile-btn" type="button" onclick="toggleDropdown('adminMenu', this)" aria-label="Admin profile" aria-expanded="false">
                            <div class="admin-avatar">{{ $adminInitials }}</div>
                            <div class="admin-profile-text"><div class="admin-name">{{ $adminName }}</div><div class="admin-role">Super Admin</div></div>
                            <svg class="icon"><use href="/assets/icons/sprite.svg#icon-sr"></use></svg>
                        </button>
                        <div class="dropdown-menu admin-menu" id="adminMenu">
                            <div class="admin-menu-head">
                                <div class="admin-avatar">{{ $adminInitials }}</div>
                                <div><div class="admin-name">{{ $adminName }}</div><div class="admin-role">{{ $adminEmail }}</div></div>
                            </div>
                            <a class="settings-item" href="{{ route('admin.dashboard.iam.users.admin-profile') }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-users-3"></use></svg>My Profile</a>
                            <a class="settings-item" href="{{ route('admin.dashboard.platform-settings.index') }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-settings-2"></use></svg>Platform Settings</a>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="settings-item danger" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-sign-out"></use></svg>Sign out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="content">
                @yield('content_header')
                @yield('content')
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/layout.js') }}"></script>
    @stack('scripts')
</body>
</html>
