<?php

namespace Tests\Feature;

use App\Models\HomepageFeedPriority;
use App\Models\Page;
use App\Models\PageRevision;
use App\Models\User;
use App\Models\UserFeedCache;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FeedCmsFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_cms_schema_contains_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('homepage_feed_priorities', [
            'id',
            'content_type',
            'priority_weight',
            'is_highlighted',
            'highlight_color',
            'is_active',
            'updated_by',
        ]));
        $this->assertTrue(Schema::hasColumns('user_feed_cache', [
            'id',
            'user_id',
            'feedable_type',
            'feedable_id',
            'priority_score',
            'source_reason',
            'is_seen',
            'expires_at',
        ]));
        $this->assertTrue(Schema::hasColumns('pages', [
            'id',
            'title',
            'slug',
            'content_blocks',
            'status',
            'is_system_page',
            'view_count',
            'seo_meta',
            'user_id',
            'published_at',
        ]));
        $this->assertTrue(Schema::hasColumns('page_revisions', [
            'id',
            'page_id',
            'content_blocks',
            'changed_by',
            'change_summary',
            'created_at',
        ]));
    }

    public function test_public_page_api_and_web_routes_only_show_published_pages(): void
    {
        $published = $this->createPage([
            'title' => 'Safety Handbook',
            'slug' => 'safety-handbook',
            'status' => Page::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $draft = $this->createPage(['title' => 'Draft Handbook', 'slug' => 'draft-handbook']);

        $this->getJson('/api/v1/feed-cms/pages?search=Safety')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'title', 'slug', 'content_blocks', 'status']]])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $published->id);

        $this->getJson('/api/v1/feed-cms/pages/safety-handbook')
            ->assertOk()
            ->assertJsonPath('data.slug', 'safety-handbook');

        $this->getJson("/api/v1/feed-cms/pages/{$draft->slug}")->assertNotFound();

        $this->get('/pages')->assertOk()->assertSee('Safety Handbook')->assertDontSee('Draft Handbook');
        $this->get('/pages/safety-handbook')->assertOk()->assertSee('Safety Handbook');
    }

    public function test_admin_page_api_requires_admin_and_validates_payloads(): void
    {
        $user = User::factory()->professional()->create();
        $admin = User::factory()->admin()->create();
        $existing = $this->createPage(['title' => 'Existing Page', 'slug' => 'existing-page']);

        $this->getJson('/api/v1/feed-cms/admin/pages')->assertUnauthorized();

        $this->actingAs($user)
            ->postJson('/api/v1/feed-cms/admin/pages', ['title' => 'Denied'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson('/api/v1/feed-cms/admin/pages', [
                'title' => '',
                'slug' => $existing->slug,
                'content_blocks' => 'bad',
                'status' => 'bad',
                'is_system_page' => 'bad',
                'seo_meta' => 'bad',
                'published_at' => 'not-a-date',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'slug', 'content_blocks', 'status', 'is_system_page', 'seo_meta', 'published_at']);
    }

    public function test_admin_can_create_update_publish_archive_and_rollback_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $pageId = $this->actingAs($admin)
            ->postJson('/api/v1/feed-cms/admin/pages', [
                'title' => 'Operations Handbook',
                'content_blocks' => [['type' => 'paragraph', 'content' => 'Draft copy']],
                'status' => Page::STATUS_DRAFT,
                'is_system_page' => true,
                'seo_meta' => ['title' => 'Ops'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'operations-handbook')
            ->json('data.id');

        $this->assertDatabaseHas('page_revisions', ['page_id' => $pageId, 'change_summary' => 'Initial page content.']);

        $this->actingAs($admin)
            ->putJson("/api/v1/feed-cms/admin/pages/{$pageId}", [
                'title' => 'Operations Handbook',
                'content_blocks' => [['type' => 'paragraph', 'content' => 'Updated copy']],
            ])
            ->assertOk()
            ->assertJsonPath('data.content_blocks.0.content', 'Updated copy');

        $revisionId = PageRevision::query()
            ->where('page_id', $pageId)
            ->where('change_summary', 'Snapshot before page update.')
            ->firstOrFail()
            ->id;

        $this->actingAs($admin)
            ->postJson("/api/v1/feed-cms/admin/pages/{$pageId}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', Page::STATUS_PUBLISHED);

        $this->actingAs($admin)
            ->postJson("/api/v1/feed-cms/admin/pages/{$pageId}/archive")
            ->assertOk()
            ->assertJsonPath('data.status', Page::STATUS_ARCHIVED);

        $this->actingAs($admin)
            ->postJson("/api/v1/feed-cms/admin/pages/{$pageId}/revisions/{$revisionId}/rollback")
            ->assertOk()
            ->assertJsonPath('data.content_blocks.0.content', 'Draft copy');
    }

    public function test_feed_priorities_and_cache_api_enforce_auth_and_ownership(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->professional()->create();
        $other = User::factory()->professional()->create();
        $page = $this->createPage(['status' => Page::STATUS_PUBLISHED, 'published_at' => now()]);
        $cache = $this->createFeedCache($user, $page, ['priority_score' => 88]);
        $otherCache = $this->createFeedCache($other, $page);

        $this->getJson('/api/v1/feed-cms/feed')->assertUnauthorized();

        $this->actingAs($user)
            ->getJson('/api/v1/feed-cms/feed?limit=5')
            ->assertOk()
            ->assertJsonPath('data.0.id', $cache->id)
            ->assertJsonPath('data.0.priority_score', 88);

        $this->actingAs($user)
            ->postJson("/api/v1/feed-cms/feed-cache/{$otherCache->id}/seen")
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson("/api/v1/feed-cms/feed-cache/{$cache->id}/seen")
            ->assertOk()
            ->assertJsonPath('data.is_seen', true);

        $this->actingAs($user)
            ->putJson('/api/v1/feed-cms/feed-priorities', [
                'content_type' => 'network_post',
                'priority_weight' => 100,
                'is_highlighted' => true,
                'is_active' => true,
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->putJson('/api/v1/feed-cms/feed-priorities', [
                'content_type' => 'network_post',
                'priority_weight' => 100,
                'is_highlighted' => true,
                'highlight_color' => '#198754',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.priority_weight', 100)
            ->assertJsonPath('data.updated_by', $admin->id);
    }

    public function test_admin_feed_cms_ui_routes_render_and_validate_json_forms(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->professional()->create();
        $page = $this->createPage(['title' => 'Admin CMS Page', 'slug' => 'admin-cms-page']);

        $this->get('/dashboard/feed-cms')->assertRedirect('/login');
        $this->actingAs($user)->get('/dashboard/feed-cms')->assertForbidden();

        $this->actingAs($admin)
            ->get('/dashboard/feed-cms')
            ->assertOk()
            ->assertSee('Feed CMS')
            ->assertSee('Admin CMS Page');

        $this->actingAs($admin)
            ->from('/dashboard/feed-cms/pages/create')
            ->post('/dashboard/feed-cms/pages', [
                'title' => 'Bad JSON',
                'status' => Page::STATUS_DRAFT,
                'content_blocks_json' => 'not-json',
            ])
            ->assertRedirect('/dashboard/feed-cms/pages/create')
            ->assertSessionHasErrors(['content_blocks_json']);

        $this->actingAs($admin)
            ->put("/dashboard/feed-cms/pages/{$page->id}", [
                'title' => 'Admin CMS Page Updated',
                'status' => Page::STATUS_DRAFT,
                'content_blocks_json' => '[{"type":"paragraph","content":"Updated"}]',
                'seo_meta_json' => '{"title":"Updated"}',
            ])
            ->assertRedirect("/dashboard/feed-cms/pages/{$page->id}/edit");
    }

    public function test_fk_constraints_fail_for_invalid_feed_cms_records(): void
    {
        $page = $this->createPage();

        $this->expectException(QueryException::class);
        PageRevision::query()->create([
            'page_id' => $page->id,
            'content_blocks' => [['type' => 'paragraph', 'content' => 'Bad user']],
            'changed_by' => 999999,
            'change_summary' => 'Invalid user.',
            'created_at' => now(),
        ]);
    }

    private function createPage(array $attributes = []): Page
    {
        return Page::query()->create(array_merge([
            'title' => 'CMS Page',
            'slug' => 'cms-page',
            'content_blocks' => [['type' => 'paragraph', 'content' => 'Body']],
            'status' => Page::STATUS_DRAFT,
            'is_system_page' => false,
            'view_count' => 0,
            'seo_meta' => null,
            'user_id' => User::factory()->admin()->create()->id,
            'published_at' => null,
        ], $attributes));
    }

    private function createFeedCache(User $user, Page $page, array $attributes = []): UserFeedCache
    {
        return UserFeedCache::query()->create(array_merge([
            'user_id' => $user->id,
            'feedable_type' => Page::class,
            'feedable_id' => $page->id,
            'priority_score' => 50,
            'source_reason' => 'fresh_content',
            'is_seen' => false,
            'created_at' => now(),
            'expires_at' => now()->addDay(),
        ], $attributes));
    }
}
