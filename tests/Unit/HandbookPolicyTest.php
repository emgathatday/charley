<?php

namespace Tests\Unit;

use App\Events\HandbookArticlePublished;
use App\Jobs\SyncHandbookArticleVectors;
use App\Listeners\QueueHandbookVectorSync;
use App\Models\HandbookArticle;
use App\Models\HandbookCategory;
use App\Models\User;
use App\Policies\HandbookArticlePolicy;
use App\Policies\HandbookCategoryPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HandbookPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_policy_allows_public_published_view_and_denies_unauthorized_management(): void
    {
        $policy = new HandbookCategoryPolicy;
        $member = User::factory()->professional()->create();
        $published = HandbookCategory::factory()->published()->create();
        $draft = HandbookCategory::factory()->create(['status' => 'draft']);

        $this->assertTrue($policy->viewAny(null)->allowed());
        $this->assertTrue($policy->view(null, $published)->allowed());
        $this->assertFalse($policy->view(null, $draft)->allowed());
        $this->assertFalse($policy->create($member)->allowed());
        $this->assertFalse($policy->update($member, $published)->allowed());
        $this->assertFalse($policy->delete($member, $published)->allowed());
        $this->assertFalse($policy->manage($member)->allowed());
        $this->assertFalse($policy->updateHotspots($member, $published)->allowed());
    }

    public function test_article_policy_allows_public_published_view_and_denies_unauthorized_management(): void
    {
        $policy = new HandbookArticlePolicy;
        $member = User::factory()->professional()->create();
        $published = HandbookArticle::factory()->published()->create();
        $draft = HandbookArticle::factory()->create(['status' => 'draft']);

        $this->assertTrue($policy->viewAny(null)->allowed());
        $this->assertTrue($policy->view(null, $published)->allowed());
        $this->assertFalse($policy->view(null, $draft)->allowed());
        $this->assertFalse($policy->create($member)->allowed());
        $this->assertFalse($policy->update($member, $published)->allowed());
        $this->assertFalse($policy->delete($member, $published)->allowed());
        $this->assertFalse($policy->publish($member, $published)->allowed());
        $this->assertFalse($policy->archive($member, $published)->allowed());
        $this->assertFalse($policy->updateAiTrainable($member, $published)->allowed());
        $this->assertFalse($policy->linkRelatedItem($member, $published)->allowed());
        $this->assertFalse($policy->manage($member)->allowed());
    }

    public function test_admin_before_bypasses_category_and_article_policy_denials(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue((new HandbookCategoryPolicy)->before($admin));
        $this->assertTrue((new HandbookArticlePolicy)->before($admin));
    }

    public function test_vector_sync_listener_dispatches_only_ai_trainable_articles(): void
    {
        Queue::fake();
        $trainable = HandbookArticle::factory()->published()->aiTrainable()->create();
        $notTrainable = HandbookArticle::factory()->published()->create(['is_ai_trainable' => false]);
        $listener = new QueueHandbookVectorSync;

        $listener->handle(new HandbookArticlePublished($trainable));
        $listener->handle(new HandbookArticlePublished($notTrainable));

        Queue::assertPushed(SyncHandbookArticleVectors::class, 1);
        Queue::assertPushed(
            SyncHandbookArticleVectors::class,
            fn (SyncHandbookArticleVectors $job): bool => $job->articleId === $trainable->id
        );
    }
}
