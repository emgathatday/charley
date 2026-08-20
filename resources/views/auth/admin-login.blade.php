<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Charley - Admin Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Inter+Tight:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/style.min.css') }}">
    <style>
        .login-card .form-card {
            padding: 32px;
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="right-panel">
            <div class="login-card">
                <div class="login-card-header">
                    <div class="login-card-title">Welcome back</div>
                    <div class="login-card-sub">Sign in to your admin account to continue</div>
                </div>

                <div class="form-card">
                    <div class="main-view" id="mainView">
                        @php
                            $showMfaChallenge = session('mfa_required') || $errors->has('code') || $errors->has('recovery_code');
                        @endphp
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

                        @if ($showMfaChallenge)
                            <form method="POST" action="{{ route('admin.login.mfa', [], false) }}" class="mfa-challenge-form" style="margin-bottom:20px;">
                                @csrf
                                <div class="form-group">
                                    <label class="form-label" for="mfa_code">Authenticator code</label>
                                    <div class="input-wrap">
                                        <svg class="icon input-icon"><use href="/assets/icons/sprite.svg#icon-active-sessions-svg-viewbox-0-0"></use></svg>
                                        <input
                                            class="form-input @error('code') is-invalid @enderror"
                                            id="mfa_code"
                                            name="code"
                                            type="text"
                                            inputmode="numeric"
                                            pattern="[0-9]{6}"
                                            maxlength="6"
                                            placeholder="Enter 6-digit code"
                                            autocomplete="one-time-code"
                                            autofocus
                                        >
                                    </div>
                                </div>

                                <button class="forgot-link" type="button" onclick="showRecoveryCodeField()" style="background:none;border:0;padding:0;margin:0 0 16px;color:var(--accent-blue);font:inherit;font-weight:700;cursor:pointer;">Use a recovery code</button>

                                <div class="form-group" id="recoveryCodeGroup" style="display:none;">
                                    <label class="form-label" for="recovery_code">Recovery code</label>
                                    <div class="input-wrap">
                                        <svg class="icon input-icon"><use href="/assets/icons/sprite.svg#icon-penalty-history-3-actions-recorded"></use></svg>
                                        <input
                                            class="form-input @error('recovery_code') is-invalid @enderror"
                                            id="recovery_code"
                                            name="recovery_code"
                                            type="text"
                                            placeholder="Enter recovery code"
                                            autocomplete="one-time-code"
                                        >
                                    </div>
                                </div>

                                <button class="btn-login" type="submit">
                                    <span class="btn-text">Verify and enter Admin Console</span>
                                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-secured-access"></use></svg>
                                </button>
                            </form>
                        @endif

                        @unless ($showMfaChallenge)
                            <form method="POST" action="{{ route('admin.login.store') }}">
                            @csrf
                            <div class="form-group">
                                <label class="form-label" for="email">Email address</label>
                                <div class="input-wrap">
                                    <svg class="icon input-icon"><use href="/assets/icons/sprite.svg#icon-email"></use></svg>
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
                                    <svg class="icon input-icon"><use href="/assets/icons/sprite.svg#icon-lock"></use></svg>
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
                        @endunless

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

        function showRecoveryCodeField() {
            const group = document.getElementById('recoveryCodeGroup');
            const input = document.getElementById('recovery_code');
            if (!group) return;
            group.style.display = 'block';
            if (input) input.focus();
        }
    </script>
</body>
</html>
