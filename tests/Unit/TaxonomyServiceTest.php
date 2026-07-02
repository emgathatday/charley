<?php

namespace Tests\Unit;

use App\Models\Concerns\HasTags;
use App\Models\SupportTicket;
use App\Models\Tag;
use App\Models\User;
use App\Services\Taxonomy\TaxonomyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TaxonomyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalize_slug_returns_slug_and_rejects_empty_values(): void
    {
        $service = new TaxonomyService;

        $this->assertSame('pressure-safety', $service->normalizeSlug('Pressure Safety'));

        $this->expectException(ValidationException::class);
        $service->normalizeSlug('***');
    }

    public function test_create_and_update_generate_unique_slugs(): void
    {
        $service = new TaxonomyService;

        $first = $service->create(['name' => 'Pressure Safety', 'category' => 'technical']);
        $second = $service->create(['name' => 'Pressure Safety Plus', 'slug' => 'pressure-safety', 'category' => 'technical']);

        $this->assertSame('pressure-safety', $first->slug);
        $this->assertSame('pressure-safety-2', $second->slug);

        $updated = $service->update($second, ['name' => 'Pressure Safety Advanced', 'category' => 'process']);

        $this->assertSame('pressure-safety-advanced', $updated->slug);
        $this->assertSame('process', $updated->category);
    }

    public function test_list_and_search_apply_filters_and_limits(): void
    {
        $service = new TaxonomyService;
        $this->createTag(['name' => 'Pump Safety', 'slug' => 'pump-safety', 'category' => 'equipment']);
        $this->createTag(['name' => 'Process Safety', 'slug' => 'process-safety', 'category' => 'process']);

        $list = $service->list(['category' => 'equipment', 'search' => 'Pump'], 10);
        $search = $service->search('Safety', null, 1);

        $this->assertSame(1, $list->total());
        $this->assertSame('pump-safety', $list->items()[0]->slug);
        $this->assertCount(1, $search);
    }

    public function test_attach_sync_detach_and_recalculate_usage_counts(): void
    {
        $service = new TaxonomyService;
        $ticket = UnitTaggableSupportTicket::query()->create([
            'user_id' => User::factory()->professional()->create()->id,
            'subject' => 'Tagged ticket',
            'category' => 'technical_issue',
            'priority' => 'normal',
            'status' => 'open',
            'description' => 'Ticket to tag.',
        ]);
        $first = $this->createTag(['name' => 'Safety', 'slug' => 'safety']);
        $second = $this->createTag(['name' => 'Pump', 'slug' => 'pump']);

        $service->attach($ticket, [$first->id, $second->id]);

        $this->assertDatabaseHas('tags', ['id' => $first->id, 'usage_count' => 1]);
        $this->assertDatabaseHas('tags', ['id' => $second->id, 'usage_count' => 1]);

        $service->sync($ticket, [$second->id]);

        $this->assertDatabaseHas('tags', ['id' => $first->id, 'usage_count' => 0]);
        $this->assertDatabaseHas('tags', ['id' => $second->id, 'usage_count' => 1]);

        $service->detach($ticket);

        $this->assertDatabaseHas('tags', ['id' => $second->id, 'usage_count' => 0]);
    }

    public function test_service_rejects_models_without_taggable_relation(): void
    {
        $service = new TaxonomyService;
        $tag = $this->createTag();
        $ticket = SupportTicket::query()->create([
            'user_id' => User::factory()->professional()->create()->id,
            'subject' => 'Plain ticket',
            'category' => 'technical_issue',
            'priority' => 'normal',
            'status' => 'open',
            'description' => 'No tags relation.',
        ]);

        $this->expectException(ValidationException::class);
        $service->attach($ticket, [$tag->id]);
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

class UnitTaggableSupportTicket extends SupportTicket
{
    use HasTags;
    protected $table = 'support_tickets';
}
