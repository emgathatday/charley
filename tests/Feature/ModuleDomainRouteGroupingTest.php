<?php

namespace Tests\Feature;

use App\Models\LibraryCategory;
use App\Models\LibraryItem;
use App\Models\MediaFile;
use App\Models\Page;
use App\Models\PlantType;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

class ModuleDomainRouteGroupingTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_route_files_only_require_module_domain_files(): void
    {
        $api = file_get_contents(base_path('routes/api.php'));
        $web = file_get_contents(base_path('routes/web.php'));

        $this->assertSame(11, substr_count($api, "require __DIR__.'/api/v1/"));
        $this->assertStringContainsString("require __DIR__.'/api/v1/auth.php';", $api);
        $this->assertStringContainsString("require __DIR__.'/api/v1/media-files.php';", $api);
        $this->assertStringContainsString("require __DIR__.'/api/v1/taxonomy.php';", $api);
        $this->assertStringContainsString("require __DIR__.'/api/v1/library.php';", $api);
        $this->assertStringNotContainsString('Route::', $api);

        $this->assertSame(12, substr_count($web, "require __DIR__.'/web/"));
        $this->assertStringContainsString("require __DIR__.'/web/dashboard/iam.php';", $web);
        $this->assertStringContainsString("require __DIR__.'/web/dashboard/media-files.php';", $web);
        $this->assertStringContainsString("require __DIR__.'/web/dashboard/taxonomy.php';", $web);
        $this->assertStringContainsString("require __DIR__.'/web/dashboard/library.php';", $web);
        $this->assertStringNotContainsString('Route::', $web);
    }

    public function test_module_domain_routes_keep_methods_names_actions_and_middleware(): void
    {
        $this->assertRoute('POST', 'api/v1/auth/register', null, 'App\Http\Controllers\Api\V1\AuthController@register', ['api']);
        $this->assertRoute('GET', 'api/v1/auth/me', null, 'App\Http\Controllers\Api\V1\AuthController@me', ['api', 'auth']);
        $this->assertRoute('GET', 'api/v1/media-files', null, 'App\Http\Controllers\Api\V1\MediaFileController@index', ['api', 'auth']);
        $this->assertRoute('GET', 'api/v1/plant-types', null, 'App\Http\Controllers\Api\V1\PlantTypeController@index', ['api', 'auth']);
        $this->assertRoute('GET', 'api/v1/taxonomy/tags', null, 'App\Http\Controllers\Api\V1\TaxonomyController@index', ['api', 'auth']);
        $this->assertRoute('GET', 'api/v1/content-approvals', null, 'App\Http\Controllers\Api\V1\ContentApprovalQueueController@index', ['api', 'auth']);
        $this->assertRoute('GET', 'api/v1/partner-profiles', null, 'App\Http\Controllers\Api\V1\PartnerProfileController@index', ['api', 'auth']);
        $this->assertRoute('GET', 'api/v1/subscription-tiers', null, 'App\Http\Controllers\Api\V1\SubscriptionTierController@index', ['api', 'auth']);
        $this->assertRoute('GET', 'api/v1/feed-cms/pages', null, 'App\Http\Controllers\Api\V1\FeedCmsPageController@publicIndex', ['api']);
        $this->assertRoute('GET', 'api/v1/library/items', null, 'App\Http\Controllers\Api\V1\LibraryController@index', ['api']);

        $this->assertRoute('GET', 'dashboard/iam/users', 'admin.dashboard.iam.users', 'App\Http\Controllers\Admin\IamUserController@index', ['web', 'auth', 'role:admin', 'account.status:active']);
        $this->assertRoute('GET', 'dashboard/media-files', 'admin.dashboard.media-files.index', 'App\Http\Controllers\Admin\MediaFileController@index', ['web', 'auth', 'role:admin', 'account.status:active']);
        $this->assertRoute('GET', 'dashboard/plant-types', 'admin.dashboard.plant-types.index', 'App\Http\Controllers\Admin\PlantTypeController@index', ['web', 'auth', 'role:admin', 'account.status:active']);
        $this->assertRoute('GET', 'dashboard/taxonomy', 'admin.dashboard.taxonomy.index', 'App\Http\Controllers\Admin\TaxonomyController@index', ['web', 'auth', 'role:admin', 'account.status:active']);
        $this->assertRoute('GET', 'dashboard/partner-profiles', 'admin.dashboard.partner-profiles.index', 'App\Http\Controllers\Admin\PartnerProfileController@index', ['web', 'auth', 'role:admin', 'account.status:active']);
        $this->assertRoute('GET', 'dashboard/subscriptions', 'admin.dashboard.subscriptions.index', 'App\Http\Controllers\Admin\SubscriptionAdminController@index', ['web', 'auth', 'role:admin', 'account.status:active']);
        $this->assertRoute('GET', 'dashboard/admin-operations', 'admin.dashboard.admin-operations.index', 'App\Http\Controllers\Admin\AdminOperationsController@index', ['web', 'auth', 'role:admin', 'account.status:active']);
        $this->assertRoute('GET', 'dashboard/feed-cms', 'admin.dashboard.feed-cms.index', 'App\Http\Controllers\Admin\FeedCmsController@index', ['web', 'auth', 'role:admin', 'account.status:active']);
        $this->assertRoute('GET', 'dashboard/library', 'admin.dashboard.library.index', 'App\Http\Controllers\Admin\LibraryController@index', ['web', 'auth', 'role:admin', 'account.status:active']);

        $this->assertRoute('GET', 'pages', 'pages.index', 'App\Http\Controllers\PageController@index', ['web']);
        $this->assertRoute('GET', 'feed', 'feed.index', 'App\Http\Controllers\PageController@feed', ['web']);
        $this->assertRoute('GET', 'library', 'library.index', 'App\Http\Controllers\LibraryPageController@index', ['web']);
    }

    public function test_module_route_table_has_no_duplicate_method_uri_pairs(): void
    {
        $routes = collect(RouteFacade::getRoutes())->reject(
            fn (Route $route): bool => str_starts_with($route->uri(), '_boost')
                || str_starts_with($route->uri(), 'storage/')
                || $route->uri() === 'up'
        );

        $signatures = $routes->map(fn (Route $route): string => implode('|', $route->methods()).' '.$route->uri());

        $this->assertSame(238, $routes->count());
        $this->assertSame([], $signatures->duplicates()->values()->all());
    }

    public function test_representative_module_routes_resolve_after_grouping(): void
    {
        $admin = User::factory()->admin()->create();
        $category = LibraryCategory::query()->create([
            'title' => 'Route Category',
            'slug' => 'route-category',
            'sort_order' => 10,
        ]);
        LibraryItem::query()->create([
            'category_id' => $category->id,
            'user_id' => $admin->id,
            'title' => 'Route Library Item',
            'slug' => 'route-library-item',
            'summary' => 'Route smoke',
            'content' => 'Route content',
            'access_level' => 'public',
            'download_allowed' => false,
            'copy_paste_disabled' => false,
            'download_count' => 0,
            'status' => LibraryItem::STATUS_PUBLISHED,
            'is_ai_trainable' => true,
            'content_type' => 'article',
            'item_type' => 'article',
            'view_count' => 0,
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'year' => 2026,
        ]);
        Page::query()->create([
            'title' => 'Route Page',
            'slug' => 'route-page',
            'content_blocks' => [['type' => 'paragraph', 'content' => 'Route page']],
            'status' => Page::STATUS_PUBLISHED,
            'is_system_page' => true,
            'view_count' => 0,
            'seo_meta' => null,
            'user_id' => $admin->id,
            'published_at' => now(),
        ]);
        Tag::query()->create(['name' => 'Route Tag', 'slug' => 'route-tag', 'category' => 'general', 'usage_count' => 0]);
        PlantType::query()->create(['name' => 'Route Plant', 'slug' => 'route-plant', 'is_active' => true, 'sort_order' => 10]);
        MediaFile::query()->create([
            'uploader_id' => $admin->id,
            'disk' => 'local',
            'path' => 'general/route.pdf',
            'original_name' => 'route.pdf',
            'mime_type' => 'application/pdf',
            'size' => 512,
            'upload_context' => 'general',
            'file_category' => 'document',
            'processing_status' => 'processed',
            'is_orphan' => false,
        ]);

        $this->getJson('/api/v1/feed-cms/pages')->assertOk();
        $this->getJson('/api/v1/library/items')->assertOk();

        $this->actingAs($admin)->getJson('/api/v1/auth/me')->assertOk();
        $this->actingAs($admin)->getJson('/api/v1/media-files')->assertOk();
        $this->actingAs($admin)->getJson('/api/v1/plant-types')->assertOk();
        $this->actingAs($admin)->getJson('/api/v1/taxonomy/tags')->assertOk();

        $this->get('/pages')->assertOk()->assertSee('Route Page');
        $this->get('/feed')->assertOk();
        $this->get('/library')->assertOk()->assertSee('Route Library Item');

        $this->actingAs($admin)->get('/dashboard/iam/users')->assertOk();
        $this->actingAs($admin)->get('/dashboard/media-files')->assertOk();
        $this->actingAs($admin)->get('/dashboard/plant-types')->assertOk();
        $this->actingAs($admin)->get('/dashboard/taxonomy')->assertOk();
        $this->actingAs($admin)->get('/dashboard/admin-operations')->assertOk();
        $this->actingAs($admin)->get('/dashboard/feed-cms')->assertOk();
        $this->actingAs($admin)->get('/dashboard/library')->assertOk();
    }

    private function assertRoute(string $method, string $uri, ?string $name, string $action, array $middleware): void
    {
        $route = collect(RouteFacade::getRoutes())->first(
            fn (Route $route): bool => $route->uri() === $uri && in_array($method, $route->methods(), true)
        );

        $this->assertNotNull($route, "Missing route [{$method} {$uri}].");
        $this->assertSame($name, $route->getName());
        $this->assertSame($action, $route->getActionName());

        foreach ($middleware as $expectedMiddleware) {
            $this->assertContains($expectedMiddleware, $route->gatherMiddleware(), "{$method} {$uri} missing {$expectedMiddleware} middleware.");
        }
    }
}
