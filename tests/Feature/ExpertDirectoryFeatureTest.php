<?php

namespace Tests\Feature;

use App\Models\EngineerProfile;
use App\Models\SearchIndexEntry;
use App\Models\User;
use Database\Factories\EngineerProfileFactory;
use Database\Factories\SearchIndexEntryFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExpertDirectoryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_expert_directory_requires_authentication(): void
    {
        $this->getJson('/api/v1/expert-directory')->assertUnauthorized();
    }

    public function test_expert_directory_returns_filtered_structure(): void
    {
        $viewer = User::factory()->professional()->create();
        $visibleProfile = EngineerProfileFactory::new()->create();
        $hiddenProfile = EngineerProfileFactory::new()->create();

        $visible = SearchIndexEntryFactory::new()->expertDirectory()->discoverable()->create([
            'indexable_type' => EngineerProfile::class,
            'indexable_id' => $visibleProfile->id,
            'searchable_text' => 'pump reliability specialist',
            'last_indexed_at' => Carbon::parse('2026-06-29 10:00:00'),
        ]);
        SearchIndexEntryFactory::new()->expertDirectory()->hidden()->create([
            'indexable_type' => EngineerProfile::class,
            'indexable_id' => $hiddenProfile->id,
            'searchable_text' => 'pump hidden specialist',
        ]);

        $this->actingAs($viewer)
            ->getJson('/api/v1/expert-directory?q=pump&per_page=5')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'indexable_type',
                        'indexable_id',
                        'searchable_text',
                        'structured_data',
                        'search_context',
                        'is_discoverable',
                        'last_indexed_at',
                    ],
                ],
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visible->id)
            ->assertJsonPath('data.0.is_discoverable', true);
    }

    public function test_expert_directory_rejects_invalid_filters(): void
    {
        $viewer = User::factory()->professional()->create();

        $this->actingAs($viewer)
            ->getJson('/api/v1/expert-directory?search_context=invalid&is_discoverable=not-bool&per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['search_context', 'is_discoverable', 'per_page']);
    }

    public function test_expert_directory_can_return_empty_result_for_missing_term(): void
    {
        $viewer = User::factory()->professional()->create();
        SearchIndexEntryFactory::new()->expertDirectory()->discoverable()->create([
            'searchable_text' => 'compressor maintenance',
        ]);

        $this->actingAs($viewer)
            ->getJson('/api/v1/expert-directory?q=unmatched')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
