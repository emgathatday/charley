<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TotpService;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Tests\TestCase;

class AdminLoginSessionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_middleware_group_keeps_session_and_csrf_stack(): void
    {
        app(\Illuminate\Contracts\Http\Kernel::class);

        $webMiddleware = app('router')->getMiddlewareGroups()['web'] ?? [];

        $this->assertContains(EncryptCookies::class, $webMiddleware);
        $this->assertContains(AddQueuedCookiesToResponse::class, $webMiddleware);
        $this->assertContains(StartSession::class, $webMiddleware);
        $this->assertContains(ShareErrorsFromSession::class, $webMiddleware);
        $this->assertContains(ValidateCsrfToken::class, $webMiddleware);
        $this->assertContains(SubstituteBindings::class, $webMiddleware);
    }

    public function test_admin_login_page_sets_session_cookie(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertCookie(config('session.cookie'))
            ->assertDontSee('action="'.route('admin.login.mfa', [], false).'"', false);
    }

    public function test_web_login_admin_without_mfa_logs_in_normally(): void
    {
        $user = User::factory()->create([
            'email' => 'admin-no-mfa@example.com',
            'username' => 'admin_no_mfa',
            'password' => Hash::make('password-secret'),
            'role' => 'admin',
            'status' => 'active',
            'mfa_enabled' => false,
            'last_login_at' => null,
        ]);

        $this->post('/login', [
            'login' => $user->email,
            'password' => 'password-secret',
        ])->assertRedirect(route('admin.dashboard.iam.users'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_web_login_admin_with_mfa_requires_challenge_after_valid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'admin-mfa@example.com',
            'username' => 'admin_mfa',
            'password' => Hash::make('password-secret'),
            'role' => 'admin',
            'status' => 'active',
            'mfa_enabled' => true,
            'mfa_secret' => app(TotpService::class)->generateSecret(),
            'last_login_at' => null,
        ]);

        $this->from('/login')->post('/login', [
            'login' => $user->email,
            'password' => 'password-secret',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('code')
            ->assertSessionHas('mfa_required', true);

        $this->get('/login')
            ->assertOk()
            ->assertSee('action="'.route('admin.login.mfa', [], false).'"', false)
            ->assertSee('name="code"', false)
            ->assertSee('Authenticator code')
            ->assertSee('Use a recovery code')
            ->assertDontSee('name="login"', false)
            ->assertDontSee('name="password"', false)
            ->assertSee('id="recoveryCodeGroup" style="display:none;"', false);

        $this->assertGuest();
        $this->assertNull($user->fresh()->last_login_at);
    }

    public function test_web_login_mfa_challenge_rejects_invalid_code_without_authentication(): void
    {
        $user = User::factory()->create([
            'email' => 'admin-mfa-bad@example.com',
            'username' => 'admin_mfa_bad',
            'password' => Hash::make('password-secret'),
            'role' => 'admin',
            'status' => 'active',
            'mfa_enabled' => true,
            'mfa_secret' => app(TotpService::class)->generateSecret(),
            'last_login_at' => null,
        ]);

        $this->from('/login')->post('/login', [
            'login' => $user->email,
            'password' => 'password-secret',
        ]);

        $this->from('/login')->post('/login/mfa', [
            'code' => '000000',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('code')
            ->assertSessionHas('mfa_required', true);

        $this->get('/login')
            ->assertOk()
            ->assertSee('action="'.route('admin.login.mfa', [], false).'"', false)
            ->assertSee('name="code"', false)
            ->assertSee('Invalid authenticator code.')
            ->assertDontSee('name="login"', false)
            ->assertDontSee('name="password"', false)
            ->assertSee('id="recoveryCodeGroup" style="display:none;"', false);

        $this->assertGuest();
        $this->assertNull($user->fresh()->last_login_at);
    }

    public function test_web_login_mfa_challenge_accepts_valid_totp(): void
    {
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();
        $user = User::factory()->create([
            'email' => 'admin-mfa-ok@example.com',
            'username' => 'admin_mfa_ok',
            'password' => Hash::make('password-secret'),
            'role' => 'admin',
            'status' => 'active',
            'mfa_enabled' => true,
            'mfa_secret' => $secret,
            'last_login_at' => null,
        ]);

        $this->from('/login')->post('/login', [
            'login' => $user->email,
            'password' => 'password-secret',
        ]);

        $this->post('/login/mfa', [
            'code' => $totp->at($secret, intdiv(time(), 30)),
        ])->assertRedirect(route('admin.dashboard.iam.users'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_source_php_and_blade_files_do_not_start_with_utf8_bom(): void
    {
        $directories = ['app', 'bootstrap', 'config', 'database', 'resources', 'routes', 'tests'];

        foreach ($directories as $directory) {
            foreach (File::allFiles(base_path($directory)) as $file) {
                if (! in_array($file->getExtension(), ['php'], true) && ! str_ends_with($file->getFilename(), '.blade.php')) {
                    continue;
                }

                $this->assertNotSame(
                    "\xEF\xBB\xBF",
                    file_get_contents($file->getPathname(), false, null, 0, 3),
                    $file->getPathname().' starts with UTF-8 BOM.'
                );
            }
        }
    }
}
