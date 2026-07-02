<?php

namespace Tests\Feature;

use App\Models\Concerns\HasTags;
use App\Models\SupportTicket;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\TaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TaxonomyFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_taxonomy_schema_and_seeder_are_available(): void
    {
        $this->assertTrue(Schema::hasColumns('tags', ['id', 'name', 'slug', 'category', 'usage_count']));
        $this->assertTrue(Schema::hasColumns('taggables', ['id', 'tag_id', 'taggable_type', 'taggable_id']));

        $seeder = new TaxonomySeeder;
        $seeder->run();
        $seeder->run();

        $this->assertSame(15, Tag::query()->count());
        $this->assertDatabaseHas('tags', [
            'name' => 'Corrosion Control',
            'slug' => 'corrosion-control',
            'category' => 'technical',
        ]);
    }

    public function test_taxonomy_api_lists_searches_and_shows_tags(): void
    {
        $user = User::factory()->professional()->create();
        $tag = $this->createTag(['name' => 'Pressure Safety', 'slug' => 'pressure-safety', 'category' => 'technical']);
        $this->createTag(['name' => 'Compressor', 'slug' => 'compressor', 'category' => 'equipment']);

        $this->getJson('/api/v1/taxonomy/tags')->assertUnauthorized();

        $this->actingAs($user)
            ->getJson('/api/v1/taxonomy/tags?category=technical&search=Pressure')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'category', 'usage_count']]])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $tag->id);

        $this->actingAs($user)
            ->getJson('/api/v1/taxonomy/tags/search?search=Pressure&limit=5')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'pressure-safety');

        $this->actingAs($user)
            ->getJson("/api/v1/taxonomy/tags/{$tag->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Pressure Safety');

        $this->actingAs($user)
            ->getJson('/api/v1/taxonomy/tags/999999')
            ->assertNotFound();
    }

    public function test_taxonomy_api_validates_read_filters_and_write_payloads(): void
    {
        $admin = User::factory()->admin()->create();
        $this->createTag(['name' => 'Existing Tag', 'slug' => 'existing-tag']);

        $this->actingAs($admin)
            ->getJson('/api/v1/taxonomy/tags?category=bad&per_page=0&limit=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category', 'per_page', 'limit']);

        $this->actingAs($admin)
            ->postJson('/api/v1/taxonomy/tags', [
                'name' => 'Existing Tag',
                'slug' => 'existing-tag',
                'category' => 'bad',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'slug', 'category']);
    }

    public function test_taxonomy_api_restricts_writes_to_admins(): void
    {
        $user = User::factory()->professional()->create();
        $tag = $this->createTag();

        $this->postJson('/api/v1/taxonomy/tags', ['name' => 'No Auth'])->assertUnauthorized();

        $this->actingAs($user)
            ->postJson('/api/v1/taxonomy/tags', ['name' => 'Denied', 'category' => 'general'])
            ->assertForbidden();

        $this->actingAs($user)
            ->putJson("/api/v1/taxonomy/tags/{$tag->id}", ['name' => 'Denied'])
            ->assertForbidden();

        $this->actingAs($user)
            ->deleteJson("/api/v1/taxonomy/tags/{$tag->id}")
            ->assertForbidden();
    }

    public function test_admin_can_create_update_delete_and_render_taxonomy_ui(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->professional()->create();
        $this->createTag(['name' => 'Pump', 'slug' => 'pump', 'category' => 'equipment']);

        $this->get('/dashboard/taxonomy')->assertRedirect('/login');
        $this->actingAs($user)->get('/dashboard/taxonomy')->assertForbidden();

        $this->actingAs($admin)
            ->get('/dashboard/taxonomy')
            ->assertOk()
            ->assertSee('Taxonomy')
            ->assertSee('Pump')
            ->assertSee('Selector');

        $tagId = $this->actingAs($admin)
            ->postJson('/api/v1/taxonomy/tags', ['name' => 'Heat Exchanger', 'category' => 'equipment'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'heat-exchanger')
            ->json('data.id');

        $this->actingAs($admin)
            ->putJson("/api/v1/taxonomy/tags/{$tagId}", ['name' => 'Heat Exchanger Updated', 'category' => 'process'])
            ->assertOk()
            ->assertJsonPath('data.slug', 'heat-exchanger-updated')
            ->assertJsonPath('data.category', 'process');

        $this->actingAs($admin)
            ->deleteJson("/api/v1/taxonomy/tags/{$tagId}")
            ->assertNoContent();
    }

    public function test_taxonomy_sync_attaches_detaches_and_recalculates_usage_counts(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = TaggableSupportTicket::query()->create([
            'user_id' => User::factory()->professional()->create()->id,
            'subject' => 'Tagged ticket',
            'category' => 'technical_issue',
            'priority' => 'normal',
            'status' => 'open',
            'description' => 'Ticket to tag.',
        ]);
        $first = $this->createTag(['name' => 'Safety', 'slug' => 'safety']);
        $second = $this->createTag(['name' => 'Pump', 'slug' => 'pump']);

        $this->actingAs($admin)
            ->postJson('/api/v1/taxonomy/tags/sync', [
                'taggable_type' => TaggableSupportTicket::class,
                'taggable_id' => $ticket->id,
                'tag_ids' => [$first->id],
                'mode' => 'attach',
            ])
            ->assertOk()
            ->assertJsonPath('data.mode', 'attach')
            ->assertJsonPath('data.tags.0.id', $first->id);

        $this->assertDatabaseHas('tags', ['id' => $first->id, 'usage_count' => 1]);

        $this->actingAs($admin)
            ->postJson('/api/v1/taxonomy/tags/sync', [
                'taggable_type' => TaggableSupportTicket::class,
                'taggable_id' => $ticket->id,
                'tag_ids' => [$second->id],
                'mode' => 'sync',
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data.tags')
            ->assertJsonPath('data.tags.0.id', $second->id);

        $this->assertDatabaseHas('tags', ['id' => $first->id, 'usage_count' => 0]);
        $this->assertDatabaseHas('tags', ['id' => $second->id, 'usage_count' => 1]);

        $this->actingAs($admin)
            ->postJson('/api/v1/taxonomy/tags/sync', [
                'taggable_type' => TaggableSupportTicket::class,
                'taggable_id' => $ticket->id,
                'tag_ids' => [$second->id],
                'mode' => 'detach',
            ])
            ->assertOk()
            ->assertJsonCount(0, 'data.tags');

        $this->assertDatabaseHas('tags', ['id' => $second->id, 'usage_count' => 0]);
    }

    public function test_taxonomy_sync_validates_taggable_payloads(): void
    {
        $admin = User::factory()->admin()->create();
        $tag = $this->createTag();
        $ticket = SupportTicket::query()->create([
            'user_id' => User::factory()->professional()->create()->id,
            'subject' => 'Plain ticket',
            'category' => 'technical_issue',
            'priority' => 'normal',
            'status' => 'open',
            'description' => 'No tag trait.',
        ]);

        $this->actingAs($admin)
            ->postJson('/api/v1/taxonomy/tags/sync', [
                'taggable_type' => TaggableSupportTicket::class,
                'taggable_id' => $ticket->id,
                'tag_ids' => [999999],
                'mode' => 'bad',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tag_ids.0', 'mode']);

        $this->actingAs($admin)
            ->postJson('/api/v1/taxonomy/tags/sync', [
                'taggable_type' => SupportTicket::class,
                'taggable_id' => $ticket->id,
                'tag_ids' => [$tag->id],
            ])
            ->assertUnprocessable();
    }

    private function createTag(array $attributes = []): Tag
    {
        return Tag::query()->create(array_merge([
            'name' => 'General Tag',
            'slug' => 'general-tag',
            'category' => 'general',
            'usage_count' => 0,
        ], $attributes));
    }
}

class TaggableSupportTicket extends SupportTicket
{
    use HasTags;
    protected $table = 'support_tickets';
}
