<?php

namespace Tests\Feature;

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\File;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Tests\TestCase;

class AdminLoginSessionMiddlewareTest extends TestCase
{
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
            ->assertCookie(config('session.cookie'));
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
