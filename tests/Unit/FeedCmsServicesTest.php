<?php

namespace Tests\Unit;

use App\Events\FeedCacheRebuildRequested;
use App\Events\HomepageFeedPriorityEffectsRefreshRequested;
use App\Jobs\ExpireStaleFeedCacheJob;
use App\Jobs\RebuildPersonalizedFeedCachesJob;
use App\Jobs\RebuildUserFeedCacheJob;
use App\Jobs\RefreshHomepageFeedPriorityEffectsJob;
use App\Models\HomepageFeedPriority;
use App\Models\Page;
use App\Models\PageRevision;
use App\Models\User;
use App\Models\UserFeedCache;
use App\Services\FeedCms\FeedCacheService;
use App\Services\FeedCms\FeedPriorityService;
use App\Services\FeedCms\PageRevisionService;
use App\Services\FeedCms\PageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FeedCmsServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_service_creates_revisions_publishes_archives_and_rolls_back(): void
    {
        $admin = User::factory()->admin()->create();
        $revisionService = new PageRevisionService;
        $pageService = new PageService($revisionService);

        $page = $pageService->create([
            'title' => 'Safety Guide',
            'content_blocks' => [['type' => 'paragraph', 'content' => 'Draft']],
            'is_system_page' => true,
        ], $admin);

        $this->assertSame('safety-guide', $page->slug);
        $this->assertSame(Page::STATUS_DRAFT, $page->status);
        $this->assertSame(1, $page->revisions()->count());

        $updated = $pageService->update($page, [
            'content_blocks' => [['type' => 'paragraph', 'content' => 'Updated']],
        ], $admin);

        $this->assertSame('Updated', $updated->content_blocks[0]['content']);
        $this->assertSame(3, $updated->revisions()->count());

        $published = $pageService->publish($updated, $admin);
        $this->assertSame(Page::STATUS_PUBLISHED, $published->status);
        $this->assertNotNull($published->published_at);

        $archived = $pageService->archive($published, $admin);
        $this->assertSame(Page::STATUS_ARCHIVED, $archived->status);

        $revision = $archived->revisions()->where('change_summary', 'Snapshot before page update.')->firstOrFail();
        $rolledBack = $revisionService->rollback($archived, $revision, $admin);

        $this->assertSame('Draft', $rolledBack->content_blocks[0]['content']);
    }

    public function test_page_service_rejects_empty_slugs(): void
    {
        $service = new PageService(new PageRevisionService);

        $this->expectException(ValidationException::class);
        $service->create([
            'title' => '***',
            'content_blocks' => [['type' => 'paragraph', 'content' => 'Invalid slug']],
        ]);
    }

    public function test_feed_priority_service_scores_and_validates_content_types(): void
    {
        $admin = User::factory()->admin()->create();
        $service = new FeedPriorityService;

        $priority = $service->updatePriority('network_post', [
            'priority_weight' => 80,
            'is_highlighted' => true,
            'highlight_color' => '#0d6efd',
            'is_active' => true,
        ], $admin);

        $this->assertSame($admin->id, $priority->updated_by);
        $this->assertSame(115, $service->score('network_post', 10));
        $this->assertSame(10, $service->score('missing_type', 10));

        $this->expectException(ValidationException::class);
        $service->updatePriority('bad_type', ['priority_weight' => 1], $admin);
    }

    public function test_feed_cache_service_rebuilds_orders_marks_seen_and_expires_items(): void
    {
        $user = User::factory()->professional()->create();
        $page = $this->createPage(['status' => Page::STATUS_PUBLISHED, 'published_at' => now()]);
        $priorityService = new FeedPriorityService;
        $priorityService->updatePriority('handbook_article', [
            'priority_weight' => 70,
            'is_highlighted' => true,
            'is_active' => true,
        ]);
        $service = new FeedCacheService($priorityService);

        $items = $service->rebuild($user, [[
            'feedable' => $page,
            'content_type' => 'handbook_article',
            'base_score' => 5,
            'source_reason' => 'admin_highlight',
        ]], 60);

        $this->assertSame(100, $items->first()->priority_score);
        $this->assertSame($items->first()->id, $service->personalizedFeed($user, 5)->first()->id);

        $seen = $service->markSeen($items->first());
        $this->assertTrue($seen->is_seen);

        $seen->forceFill(['expires_at' => now()->subMinute()])->save();
        $this->assertSame(1, $service->expireStale());
    }

    public function test_feed_cache_service_rejects_unpersisted_feedable_items(): void
    {
        $service = new FeedCacheService(new FeedPriorityService);

        $this->expectException(ValidationException::class);
        $service->rebuild(User::factory()->professional()->create(), [[
            'feedable' => new Page,
            'content_type' => 'network_post',
        ]]);
    }

    public function test_feed_cache_jobs_dispatch_events_and_queue_work(): void
    {
        Queue::fake();
        Event::fake();
        $user = User::factory()->professional()->create();

        (new RebuildPersonalizedFeedCachesJob('manual'))->handle();

        Event::assertDispatched(FeedCacheRebuildRequested::class);
        Queue::assertPushed(RebuildUserFeedCacheJob::class, fn (RebuildUserFeedCacheJob $job): bool => $job->userId === $user->id);

        (new RefreshHomepageFeedPriorityEffectsJob('network_post', 'priority_refresh'))->handle();

        Event::assertDispatched(HomepageFeedPriorityEffectsRefreshRequested::class);
        Queue::assertPushed(RebuildPersonalizedFeedCachesJob::class);
    }

    public function test_rebuild_user_and_expire_jobs_update_feed_cache_rows(): void
    {
        Event::fake();
        $user = User::factory()->professional()->create();
        $page = $this->createPage([
            'status' => Page::STATUS_PUBLISHED,
            'is_system_page' => true,
            'published_at' => now(),
            'view_count' => 25,
        ]);
        HomepageFeedPriority::query()->create([
            'content_type' => 'handbook_article',
            'priority_weight' => 50,
            'is_highlighted' => false,
            'highlight_color' => null,
            'is_active' => true,
            'updated_by' => null,
        ]);

        (new RebuildUserFeedCacheJob($user->id, 'manual'))->handle(new FeedCacheService(new FeedPriorityService));

        Event::assertDispatched(FeedCacheRebuildRequested::class);
        $this->assertDatabaseHas('user_feed_cache', [
            'user_id' => $user->id,
            'feedable_type' => Page::class,
            'feedable_id' => $page->id,
            'priority_score' => 82,
            'source_reason' => 'admin_highlight',
        ]);

        UserFeedCache::query()->update(['expires_at' => now()->subMinute()]);

        (new ExpireStaleFeedCacheJob)->handle(new FeedCacheService(new FeedPriorityService));

        $this->assertSame(0, UserFeedCache::query()->count());
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
}
