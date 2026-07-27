<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Charley - Admin Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Inter+Tight:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>
<body>
    <div class="login-page">
        <div class="left-panel">
            <div class="brand">
                <div class="brand-mark">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-charley-admin-console-svg-viewbox-0"></use></svg>
                </div>
                <div>
                    <div class="brand-name">Charley</div>
                    <div class="brand-sub">Admin Console</div>
                </div>
            </div>

            <div class="panel-body">
                <div class="panel-eyebrow">
                    <span class="panel-eyebrow-dot"></span>
                    <span class="panel-eyebrow-text">Platform Operational</span>
                </div>

                <h1 class="panel-heading">
                    Engineering<br>
                    knowledge,<br>
                    <span>governed right.</span>
                </h1>

                <p class="panel-desc">
                    Manage users, moderate Q&amp;A, oversee partner activity, and keep Charley's knowledge base accurate - from one secure console.
                </p>

                <div class="stat-row">
                    <div class="stat-pill">
                        <div class="stat-icon" style="background:rgba(59,130,246,0.12);">
                            <svg class="icon"><use href="/assets/icons/sprite.svg#icon-user-management-12-svg-viewbox-0"></use></svg>
                        </div>
                        <div>
                            <div class="stat-value">1,248</div>
                            <div class="stat-label">Verified professionals</div>
                        </div>
                        <span class="stat-meta" style="background:rgba(16,185,129,0.12);color:#34D399;">+14 this week</span>
                    </div>

                    <div class="stat-pill">
                        <div class="stat-icon" style="background:rgba(99,102,241,0.12);">
                            <svg class="icon"><use href="/assets/icons/sprite.svg#icon-qanda-management-rect-x-3-y-4"></use></svg>
                        </div>
                        <div>
                            <div class="stat-value">342</div>
                            <div class="stat-label">Technical Q&amp;As active</div>
                        </div>
                        <span class="stat-meta" style="background:rgba(244,63,94,0.1);color:#FB7185;">5 flagged</span>
                    </div>

                    <div class="stat-pill">
                        <div class="stat-icon" style="background:rgba(6,182,212,0.1);">
                            <svg class="icon"><use href="/assets/icons/sprite.svg#icon-partner-management-path-d-m9-12"></use></svg>
                        </div>
                        <div>
                            <div class="stat-value">38</div>
                            <div class="stat-label">Active partners</div>
                        </div>
                        <span class="stat-meta" style="background:rgba(6,182,212,0.1);color:#67E8F9;">12 Diamond</span>
                    </div>
                </div>
            </div>

            <div class="panel-footer">
                <span class="panel-footer-dot"></span>
                <span class="panel-footer-text">Charley AI - <strong>Operational</strong> &middot; 4 data sources synced</span>
            </div>
        </div>

        <div class="right-panel">
            <div class="login-card">
                <div class="login-card-header">
                    <div class="login-card-title">Welcome back</div>
                    <div class="login-card-sub">Sign in to your admin account to continue</div>
                </div>

                <div class="form-card">
                    <div class="main-view" id="mainView">
                        @if (session('status'))
                            <div class="error-msg" style="display:flex;background:rgba(16,185,129,0.08);border-color:rgba(16,185,129,0.28);color:#047857;">
                                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-profile-verification-queue-5-svg"></use></svg>
                                <span>{{ session('status') }}</span>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="error-msg" id="errorMsg" style="display:flex;">
                                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-user-k-habib-flagged-for"></use></svg>
                                <span id="errorText">{{ $errors->first() }}</span>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('admin.login.store') }}">
                            @csrf
                            <div class="form-group">
                                <label class="form-label" for="email">Email address</label>
                                <div class="input-wrap">
                                    <svg class="icon input-icon"><use href="/assets/icons/sprite.svg#icon-unnamed-1"></use></svg>
                                    <input
                                        class="form-input @error('login') is-invalid @enderror"
                                        id="email"
                                        name="login"
                                        type="text"
                                        value="{{ old('login') }}"
                                        placeholder="admin@charley.tech"
                                        autocomplete="username"
                                        required
                                        autofocus
                                    >
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="password">Password</label>
                                <div class="input-wrap">
                                    <svg class="icon input-icon"><use href="/assets/icons/sprite.svg#icon-account-penalty-and-freeze-3"></use></svg>
                                    <input
                                        class="form-input @error('password') is-invalid @enderror"
                                        id="password"
                                        name="password"
                                        type="password"
                                        placeholder="Enter your password"
                                        autocomplete="current-password"
                                        required
                                    >
                                    <svg id="pwToggle" class="icon password-toggle" role="button" tabindex="0" aria-label="Toggle password visibility" onclick="togglePasswordVisibility()"><use href="/assets/icons/sprite.svg#icon-input-type-checkbox-id"></use></svg>
                                </div>
                            </div>

                            <div class="form-options">
                                <label class="checkbox-wrap" style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                                    <input type="checkbox" name="remember" value="1" id="rememberMe" style="width:16px;height:16px;accent-color:var(--accent-blue);border-radius:5px;" @checked(old('remember'))>
                                    <span class="checkbox-label">Keep me signed in</span>
                                </label>
                                {{-- TODO: forgot password route is not defined in current auth routes. --}}
                                <a href="#" class="forgot-link">Forgot password?</a>
                            </div>

                            <button class="btn-login" id="loginBtn" type="submit">
                                <span class="btn-text">Sign in to Admin Console</span>
                                <div class="spinner" id="loginSpinner"></div>
                                <svg id="loginArrow" class="icon"><use href="/assets/icons/sprite.svg#icon-secured-access"></use></svg>
                            </button>
                        </form>

                        <div class="form-divider">
                            <div class="form-divider-line"></div>
                            <span class="form-divider-text">Secured access</span>
                            <div class="form-divider-line"></div>
                        </div>

                        <div class="security-badges">
                            <span class="security-badge">
                                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-penalty-history-3-actions-recorded"></use></svg>
                                SSL encrypted
                            </span>
                            <span class="security-badge">
                                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-account-penalty-and-freeze-3"></use></svg>
                                Session protected
                            </span>
                            <span class="security-badge">
                                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-profile-verification-queue-5-svg"></use></svg>
                                Audit logged
                            </span>
                        </div>
                    </div>
                </div>

                <div class="login-footer">
                    Having trouble? <a href="mailto:support@charley.tech">Contact platform support</a><br>
                    <span style="font-size:11px;margin-top:4px;display:block;">Charley Admin Console &middot; v1.0 &middot; Restricted access</span>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/layout.js') }}"></script>
    <script>
        function togglePasswordVisibility() {
            const input = document.getElementById('password');
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>


