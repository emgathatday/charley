<?php

namespace Tests\Unit;

use App\Jobs\SyncHandbookArticleVectors;
use App\Models\HandbookArticle;
use App\Models\HandbookCategory;
use App\Models\HandbookMetadata;
use App\Models\HandbookRelatedItem;
use App\Models\User;
use App\Services\HandbookService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Tests\TestCase;

class HandbookServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_tree_returns_published_parent_child_nodes_in_sort_order(): void
    {
        $service = $this->service();
        $parent = HandbookCategory::factory()->published()->create([
            'title' => 'Operations',
            'slug' => 'operations',
            'sort_order' => 20,
        ]);
        HandbookCategory::factory()->published()->create([
            'title' => 'Safety',
            'slug' => 'safety',
            'parent_id' => $parent->id,
            'sort_order' => 10,
        ]);
        HandbookCategory::factory()->create([
            'title' => 'Draft Area',
            'slug' => 'draft-area',
            'status' => 'draft',
            'sort_order' => 1,
        ]);

        $tree = $service->categoryTree();

        $this->assertCount(1, $tree);
        $this->assertSame('operations', $tree->first()['slug']);
        $this->assertSame('safety', $tree->first()['children']->first()['slug']);
    }

    public function test_plant_layout_hotspots_returns_nullable_layout_and_cast_coordinates(): void
    {
        $service = $this->service();
        HandbookCategory::factory()->published()->withHotspot()->create([
            'title' => 'Compressor',
            'slug' => 'compressor',
            'layout_image_media_id' => null,
            'map_coordinates' => ['x' => 25, 'y' => 40, 'label' => 'Inlet'],
        ]);
        HandbookCategory::factory()->published()->create([
            'title' => 'No Hotspot',
            'slug' => 'no-hotspot',
            'map_coordinates' => null,
        ]);

        $hotspots = $service->plantLayoutHotspots();

        $this->assertCount(1, $hotspots);
        $this->assertNull($hotspots->first()['layout_image_media_id']);
        $this->assertSame(['x' => 25, 'y' => 40, 'label' => 'Inlet'], $hotspots->first()['map_coordinates']);
    }

    public function test_publish_article_sets_status_user_and_loads_relationships(): void
    {
        $admin = User::factory()->admin()->create();
        $article = HandbookArticle::factory()->create([
            'status' => 'draft',
            'user_id' => null,
            'content' => 'Ready to publish',
        ]);
        HandbookMetadata::query()->create([
            'article_id' => $article->id,
            'meta_type' => 'kpi',
            'meta_key' => 'pressure',
            'meta_value' => '10 bar',
            'vector_status' => 'pending',
        ]);

        $published = $this->service()->publishArticle($article->id, $admin->id);

        $this->assertSame('published', $published->status);
        $this->assertSame($admin->id, $published->user_id);
        $this->assertTrue($published->relationLoaded('category'));
        $this->assertTrue($published->relationLoaded('metadata'));
        $this->assertTrue($published->relationLoaded('relatedItems'));
    }

    public function test_publish_article_rejects_missing_content_or_category(): void
    {
        $article = new HandbookArticle([
            'title' => 'Incomplete',
            'slug' => 'incomplete',
            'content' => '',
            'status' => 'draft',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires content and category');

        $this->service()->publishArticle($article);
    }

    public function test_metadata_grouped_and_related_item_link_update_existing_records(): void
    {
        $service = $this->service();
        $article = HandbookArticle::factory()->create();
        HandbookMetadata::query()->create([
            'article_id' => $article->id,
            'meta_type' => 'kpi',
            'meta_key' => 'pressure',
            'meta_value' => '10 bar',
            'vector_status' => 'pending',
        ]);
        HandbookMetadata::query()->create([
            'article_id' => $article->id,
            'meta_type' => 'troubleshooting',
            'meta_key' => 'trip',
            'meta_value' => 'Check feed',
            'vector_status' => 'pending',
        ]);

        $grouped = $service->metadataGrouped($article->slug);
        $firstLink = $service->linkRelatedItem($article, 'library_item', 10, 'library_item', 2);
        $secondLink = $service->linkRelatedItem($article, 'library_item', 10, 'ai_shortcut', 5);

        $this->assertTrue($grouped->has('kpi'));
        $this->assertTrue($grouped->has('troubleshooting'));
        $this->assertSame($firstLink->id, $secondLink->id);
        $this->assertSame('ai_shortcut', $secondLink->relation_type);
        $this->assertSame(5, $secondLink->sort_order);
    }

    public function test_link_related_item_rejects_invalid_relation_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid handbook relation type.');

        $this->service()->linkRelatedItem(HandbookArticle::factory()->create(), 'library_item', 10, 'bad_type');
    }

    public function test_model_casts_unique_fk_and_cascade_constraints_are_enforced(): void
    {
        $category = HandbookCategory::factory()->published()->create([
            'slug' => 'unique-category',
            'map_coordinates' => ['x' => 1, 'y' => 2],
        ]);
        $article = HandbookArticle::factory()->create([
            'category_id' => $category->id,
            'failure_modes' => [['mode' => 'high pressure']],
            'ai_shortcut_config' => ['enabled' => true],
            'is_ai_trainable' => true,
        ]);
        HandbookMetadata::query()->create([
            'article_id' => $article->id,
            'meta_type' => 'kpi',
            'meta_key' => 'temperature',
            'meta_value' => '300C',
            'vector_status' => 'pending',
        ]);
        HandbookRelatedItem::query()->create([
            'handbook_article_id' => $article->id,
            'relatable_type' => 'library_item',
            'relatable_id' => 15,
            'relation_type' => 'library_item',
            'sort_order' => 1,
        ]);

        $this->assertSame(['x' => 1, 'y' => 2], $category->refresh()->map_coordinates);
        $this->assertSame([['mode' => 'high pressure']], $article->refresh()->failure_modes);
        $this->assertTrue($article->is_ai_trainable);

        try {
            HandbookCategory::factory()->create(['slug' => 'unique-category']);
            $this->fail('Expected duplicate handbook category slug to fail.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        try {
            HandbookArticle::factory()->create(['category_id' => 999999]);
            $this->fail('Expected invalid handbook category FK to fail.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        $article->delete();

        $this->assertDatabaseMissing('handbook_metadata', ['article_id' => $article->id]);
        $this->assertDatabaseMissing('handbook_related_items', ['handbook_article_id' => $article->id]);
    }

    public function test_vector_sync_job_updates_only_eligible_article_metadata(): void
    {
        Queue::fake();
        $eligible = HandbookArticle::factory()->published()->aiTrainable()->create();
        $draft = HandbookArticle::factory()->aiTrainable()->create(['status' => 'draft']);
        HandbookMetadata::query()->create([
            'article_id' => $eligible->id,
            'meta_type' => 'kpi',
            'meta_key' => 'pressure',
            'meta_value' => '10 bar',
            'vector_status' => 'pending',
        ]);
        HandbookMetadata::query()->create([
            'article_id' => $draft->id,
            'meta_type' => 'kpi',
            'meta_key' => 'pressure',
            'meta_value' => '5 bar',
            'vector_status' => 'pending',
        ]);

        (new SyncHandbookArticleVectors($eligible->id))->handle();
        (new SyncHandbookArticleVectors($draft->id))->handle();

        $this->assertDatabaseHas('handbook_metadata', [
            'article_id' => $eligible->id,
            'vector_status' => 'synced',
        ]);
        $this->assertDatabaseHas('handbook_metadata', [
            'article_id' => $draft->id,
            'vector_status' => 'pending',
        ]);
        Queue::assertNothingPushed();
    }

    private function service(): HandbookService
    {
        return new HandbookService(
            new HandbookCategory,
            new HandbookArticle,
            new HandbookMetadata,
            new HandbookRelatedItem,
        );
    }
}
