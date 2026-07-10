<?php

namespace Tests\Feature;

use App\Models\HandbookArticle;
use App\Models\HandbookCategory;
use App\Models\LibraryCategory;
use App\Models\LibraryItem;
use App\Models\PlantType;
use App\Models\User;
use Database\Seeders\HandbookSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandbookAdminUiFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_handbook_admin_ui_requires_admin_and_exposes_sidebar_menu(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->professional()->create();
        $category = HandbookCategory::factory()->published()->create(['title' => 'Operations', 'slug' => 'operations']);
        HandbookArticle::factory()->published()->create([
            'category_id' => $category->id,
            'title' => 'Operations Handbook',
            'slug' => 'operations-handbook',
        ]);

        $this->get('/dashboard/handbook')->assertRedirect('/login');
        $this->actingAs($user)->get('/dashboard/handbook')->assertForbidden();

        $this->actingAs($admin)
            ->get('/dashboard/handbook')
            ->assertOk()
            ->assertSee('HANDBOOK')
            ->assertSee('Create Article')
            ->assertSee('Operations Handbook');
    }

    public function test_admin_can_create_view_update_publish_and_archive_handbook_articles(): void
    {
        $admin = User::factory()->admin()->create();
        $category = HandbookCategory::factory()->published()->create(['title' => 'Operations', 'slug' => 'operations']);

        $this->actingAs($admin)
            ->get('/dashboard/handbook/create')
            ->assertOk()
            ->assertSee('Create Handbook Article');

        $this->actingAs($admin)
            ->from('/dashboard/handbook/create')
            ->post('/dashboard/handbook', [
                'category_id' => 999999,
                'title' => '',
                'content' => '',
                'status' => 'bad',
            ])
            ->assertRedirect('/dashboard/handbook/create')
            ->assertSessionHasErrors(['category_id', 'title', 'content', 'status']);

        $this->actingAs($admin)
            ->post('/dashboard/handbook', [
                'category_id' => $category->id,
                'title' => 'Startup Checklist',
                'slug' => '',
                'summary' => null,
                'content' => 'Startup checklist body.',
                'optimization_guidance' => null,
                'status' => 'draft',
                'is_ai_trainable' => true,
                'process_description' => null,
            ])
            ->assertRedirect();

        $article = HandbookArticle::query()->where('slug', 'startup-checklist')->firstOrFail();

        $this->actingAs($admin)
            ->get("/dashboard/handbook/{$article->slug}")
            ->assertOk()
            ->assertSee('Published Article');

        $this->actingAs($admin)
            ->put("/dashboard/handbook/{$article->slug}", [
                'category_id' => $category->id,
                'title' => 'Startup Checklist Updated',
                'slug' => '',
                'summary' => 'Updated summary',
                'content' => 'Updated body.',
                'optimization_guidance' => null,
                'status' => 'draft',
                'is_ai_trainable' => false,
                'process_description' => null,
            ])
            ->assertRedirect();

        $article = $article->fresh();
        $this->assertSame('startup-checklist-updated', $article->slug);

        $this->actingAs($admin)
            ->post("/dashboard/handbook/{$article->slug}/publish")
            ->assertRedirect();
        $this->assertDatabaseHas('handbook_articles', ['id' => $article->id, 'status' => 'published', 'user_id' => $admin->id]);

        $this->actingAs($admin)
            ->post("/dashboard/handbook/{$article->slug}/archive")
            ->assertRedirect();
        $this->assertDatabaseHas('handbook_articles', ['id' => $article->id, 'status' => 'archived']);
    }

    public function test_seeded_demo_handbook_data_is_visible_in_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();
        PlantType::query()->create(['name' => 'Ammonia', 'slug' => 'ammonia', 'is_active' => true, 'sort_order' => 10]);
        $this->createLibraryItem($this->createLibraryCategory(), ['item_type' => 'handbook']);

        $this->seed(HandbookSeeder::class);

        $this->actingAs($admin)
            ->get('/dashboard/handbook')
            ->assertOk()
            ->assertSee('Start-up Readiness Walkthrough')
            ->assertSee('Synthesis Loop Pressure Monitoring')
            ->assertSee('HANDBOOK');
    }

    private function createLibraryCategory(array $attributes = []): LibraryCategory
    {
        return LibraryCategory::query()->create(array_merge([
            'title' => 'Library Category',
            'slug' => 'library-category',
            'parent_id' => null,
            'sort_order' => 10,
        ], $attributes));
    }

    private function createLibraryItem(LibraryCategory $category, array $attributes = []): LibraryItem
    {
        return LibraryItem::query()->create(array_merge([
            'category_id' => $category->id,
            'user_id' => User::factory()->admin()->create()->id,
            'title' => 'Library Item',
            'slug' => 'library-item',
            'summary' => 'Library summary',
            'content' => 'Library content',
            'plant_type_id' => null,
            'author' => 'Admin',
            'source' => 'Internal',
            'published_year' => 2026,
            'access_level' => 'public',
            'download_allowed' => false,
            'copy_paste_disabled' => false,
            'download_count' => 0,
            'status' => LibraryItem::STATUS_PUBLISHED,
            'is_ai_trainable' => true,
            'content_type' => 'article',
            'item_type' => 'article',
            'view_count' => 0,
            'approved_by' => User::factory()->admin()->create()->id,
            'approved_at' => now(),
            'year' => 2026,
            'file_media_id' => null,
        ], $attributes));
    }
}
