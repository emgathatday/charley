<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.admin-login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginField = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt([
            $loginField => $credentials['login'],
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            $this->trackFailedAttempt($credentials['login'], $loginField);

            return back()
                ->withErrors(['login' => 'Invalid admin credentials.'])
                ->onlyInput('login');
        }

        $user = $request->user();

        if ($user->role !== 'admin' || $user->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['login' => 'Admin access is not available for this account.'])
                ->onlyInput('login');
        }

        $user->forceFill([
            'last_login_at' => now(),
            'login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard.iam.users'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function trackFailedAttempt(string $login, string $loginField): void
    {
        User::query()
            ->where($loginField, $login)
            ->where('role', 'admin')
            ->increment('login_attempts');
    }
}
