<?php

namespace Tests\Feature;

use App\Models\LibraryCategory;
use App\Models\LibraryItem;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

class RouteRefactorParityTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_table_keeps_representative_api_web_and_admin_signatures(): void
    {
        $this->assertRoute('POST', 'api/v1/auth/register', null, 'App\Http\Controllers\Api\V1\AuthController@register', ['api']);
        $this->assertRoute('GET', 'api/v1/auth/me', null, 'App\Http\Controllers\Api\V1\AuthController@me', ['api', 'auth']);
        $this->assertRoute('GET', 'api/v1/library/items', null, 'App\Http\Controllers\Api\V1\LibraryController@index', ['api']);
        $this->assertRoute('POST', 'api/v1/library/admin/items/{libraryItem}/approve', null, 'App\Http\Controllers\Api\V1\LibraryController@approve', ['api', 'auth', 'can:approve,libraryItem']);
        $this->assertRoute('GET', 'api/v1/feed-cms/pages', null, 'App\Http\Controllers\Api\V1\FeedCmsPageController@publicIndex', ['api']);
        $this->assertRoute('GET', 'api/v1/taxonomy/tags', null, 'App\Http\Controllers\Api\V1\TaxonomyController@index', ['api', 'auth']);

        $this->assertRoute('GET', 'pages', 'pages.index', 'App\Http\Controllers\PageController@index', ['web']);
        $this->assertRoute('GET', 'feed', 'feed.index', 'App\Http\Controllers\PageController@feed', ['web']);
        $this->assertRoute('GET', 'library', 'library.index', 'App\Http\Controllers\LibraryPageController@index', ['web']);
        $this->assertRoute('POST', 'library/items/{libraryItem}/download', 'library.items.download', 'App\Http\Controllers\LibraryPageController@download', ['web', 'auth']);

        $this->assertRoute('GET', 'dashboard/library', 'admin.dashboard.library.index', 'App\Http\Controllers\Admin\LibraryController@index', ['web', 'auth', 'role:admin', 'account.status:active']);
        $this->assertRoute('GET', 'dashboard/feed-cms', 'admin.dashboard.feed-cms.index', 'App\Http\Controllers\Admin\FeedCmsController@index', ['web', 'auth', 'role:admin', 'account.status:active']);
        $this->assertRoute('GET', 'dashboard/taxonomy', 'admin.dashboard.taxonomy.index', 'App\Http\Controllers\Admin\TaxonomyController@index', ['web', 'auth', 'role:admin', 'account.status:active']);

        $this->assertSame('/dashboard/library', parse_url(route('admin.dashboard.library.index'), PHP_URL_PATH));
        $this->assertSame('/library', parse_url(route('library.index'), PHP_URL_PATH));
        $this->assertSame('/feed', parse_url(route('feed.index'), PHP_URL_PATH));
    }

    public function test_route_table_has_no_duplicate_method_uri_pairs(): void
    {
        $routes = collect(RouteFacade::getRoutes())->reject(
            fn (Route $route): bool => str_starts_with($route->uri(), '_boost')
                || str_starts_with($route->uri(), 'storage/')
                || $route->uri() === 'up'
        );

        $signatures = $routes->map(fn (Route $route): string => implode('|', $route->methods()).' '.$route->uri());

        $this->assertGreaterThan(200, $signatures->count());
        $this->assertSame([], $signatures->duplicates()->values()->all());
    }

    public function test_smoke_routes_resolve_for_public_api_dashboard_and_public_pages(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->admin()->create();
        Page::query()->create([
            'title' => 'Public Safety Page',
            'slug' => 'public-safety-page',
            'content_blocks' => [['type' => 'paragraph', 'content' => 'Route smoke page.']],
            'status' => Page::STATUS_PUBLISHED,
            'is_system_page' => true,
            'view_count' => 0,
            'seo_meta' => null,
            'user_id' => $author->id,
            'published_at' => now(),
        ]);
        $category = LibraryCategory::query()->create([
            'title' => 'Route Library',
            'slug' => 'route-library',
            'sort_order' => 10,
        ]);
        LibraryItem::query()->create([
            'category_id' => $category->id,
            'user_id' => $author->id,
            'title' => 'Route Library Item',
            'slug' => 'route-library-item',
            'summary' => 'Smoke item',
            'content' => 'Smoke content',
            'access_level' => 'public',
            'download_allowed' => false,
            'copy_paste_disabled' => false,
            'download_count' => 0,
            'status' => LibraryItem::STATUS_PUBLISHED,
            'is_ai_trainable' => true,
            'content_type' => 'article',
            'item_type' => 'article',
            'view_count' => 0,
            'approved_by' => $author->id,
            'approved_at' => now(),
            'year' => 2026,
        ]);

        $this->getJson('/api/v1/library/categories')->assertOk();
        $this->getJson('/api/v1/feed-cms/pages')->assertOk();
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();

        $this->get('/pages')->assertOk()->assertSee('Public Safety Page');
        $this->get('/feed')->assertOk();
        $this->get('/library')->assertOk()->assertSee('Route Library Item');

        $this->actingAs($admin)->get('/dashboard/library')->assertOk();
        $this->actingAs($admin)->get('/dashboard/feed-cms')->assertOk();
        $this->actingAs($admin)->get('/dashboard/taxonomy')->assertOk();
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
