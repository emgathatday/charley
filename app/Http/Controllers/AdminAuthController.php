<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccountSecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function __construct(private readonly AccountSecurityService $accountSecurityService)
    {
    }

    public function showLogin(): View
    {
        return view('auth.admin-login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $loginField = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::query()
            ->where($loginField, $credentials['login'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            $this->trackFailedAttempt($credentials['login'], $loginField);

            return back()
                ->withErrors(['login' => 'Invalid admin credentials.'])
                ->onlyInput('login');
        }

        if ($user->role !== 'admin' || $user->status !== 'active') {
            Auth::logout();
            $request->session()->forget('admin_mfa_challenge');

            return back()
                ->withErrors(['login' => 'Admin access is not available for this account.'])
                ->onlyInput('login');
        }

        if ($user->mfa_enabled) {
            $code = $credentials['code'] ?? null;
            $recoveryCode = $credentials['recovery_code'] ?? null;

            if (! $code && ! $recoveryCode) {
                Auth::logout();
                $request->session()->put('admin_mfa_challenge', [
                    'user_id' => $user->id,
                    'remember' => $request->boolean('remember'),
                    'login' => $credentials['login'],
                ]);

                return back()
                    ->withErrors(['code' => 'Enter your authenticator code to finish signing in.'])
                    ->with('mfa_required', true)
                    ->onlyInput('login');
            }

            if (! $this->accountSecurityService->validMfaCredential($user, $code, $recoveryCode, consumeRecoveryCode: true)) {
                Auth::logout();
                $request->session()->put('admin_mfa_challenge', [
                    'user_id' => $user->id,
                    'remember' => $request->boolean('remember'),
                    'login' => $credentials['login'],
                ]);

                return back()
                    ->withErrors(['code' => 'Invalid authenticator code.'])
                    ->with('mfa_required', true)
                    ->onlyInput('login');
            }

            return $this->completeLogin($request, $user, $request->boolean('remember'));
        }

        return $this->completeLogin($request, $user, $request->boolean('remember'));
    }

    public function loginMfa(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);
        $challenge = $request->session()->get('admin_mfa_challenge');

        if (! $challenge || empty($challenge['user_id'])) {
            Auth::logout();

            return redirect()->route('login')
                ->withErrors(['login' => 'Your MFA challenge has expired.']);
        }

        $user = User::query()->find($challenge['user_id']);
        if (! $user || $user->role !== 'admin' || $user->status !== 'active' || ! $user->mfa_enabled) {
            Auth::logout();
            $request->session()->forget('admin_mfa_challenge');

            return redirect()->route('login')
                ->withErrors(['login' => 'Admin access is not available for this account.']);
        }

        if (! $this->accountSecurityService->validMfaCredential($user, $data['code'] ?? null, $data['recovery_code'] ?? null, consumeRecoveryCode: true)) {
            Auth::logout();

            return back()
                ->withErrors(['code' => 'Invalid authenticator code.'])
                ->with('mfa_required', true);
        }

        return $this->completeLogin($request, $user, (bool) ($challenge['remember'] ?? false));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function completeLogin(Request $request, User $user, bool $remember): RedirectResponse
    {
        Auth::login($user, $remember);

        $user->forceFill([
            'last_login_at' => now(),
            'login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        $request->session()->forget('admin_mfa_challenge');
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard.iam.users'));
    }

    private function trackFailedAttempt(string $login, string $loginField): void
    {
        User::query()
            ->where($loginField, $login)
            ->where('role', 'admin')
            ->increment('login_attempts');
    }
}
