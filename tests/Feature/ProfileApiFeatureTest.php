<?php

namespace Tests\Feature;

use App\Models\EngineerProfile;
use App\Models\SearchIndexEntry;
use App\Models\UnverifiedMemberProfile;
use App\Models\User;
use Database\Factories\EngineerProfileFactory;
use Database\Factories\UnverifiedMemberProfileFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_engineer_profile_requires_authentication(): void
    {
        $this->getJson('/api/v1/profile/engineer')->assertUnauthorized();
    }

    public function test_professional_can_upsert_engineer_profile_and_refresh_search_index(): void
    {
        $user = User::factory()->professional()->create();

        $this->actingAs($user)
            ->putJson('/api/v1/profile/engineer', [
                'bio' => 'Process safety specialist.',
                'current_company' => 'Charley Plant',
                'position' => 'Reliability Engineer',
                'experience_years' => 12,
                'expertise_tags' => ['safety', 'reliability'],
                'searchable_keywords' => ['pump', 'audit'],
                'is_discoverable' => true,
                'privacy_settings' => ['show_activity_feed' => true],
                'job_availability' => 'open',
            ])
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'user',
                    'bio',
                    'current_company',
                    'position',
                    'experience_years',
                    'expertise_tags',
                    'searchable_keywords',
                    'is_discoverable',
                    'privacy_settings',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.current_company', 'Charley Plant')
            ->assertJsonPath('data.expertise_tags', ['safety', 'reliability']);

        $profile = EngineerProfile::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertDatabaseHas('search_index_entries', [
            'indexable_type' => EngineerProfile::class,
            'indexable_id' => $profile->id,
            'search_context' => 'expert_directory',
            'is_discoverable' => true,
        ]);
    }

    public function test_unverified_member_can_upsert_unverified_profile(): void
    {
        $user = User::factory()->unverified()->create(['status' => 'active']);

        $this->actingAs($user)
            ->putJson('/api/v1/profile/unverified', [
                'bio' => 'New graduate.',
                'current_institution' => 'Technical College',
                'field_of_study' => 'Chemical Engineering',
                'experience_years' => 0,
                'expertise_tags' => ['training'],
                'verification_intent' => true,
                'is_discoverable' => true,
            ])
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'user',
                    'bio',
                    'current_institution',
                    'field_of_study',
                    'experience_years',
                    'expertise_tags',
                    'verification_intent',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.verification_intent', true);

        $this->assertDatabaseHas('unverified_member_profiles', [
            'user_id' => $user->id,
            'current_institution' => 'Technical College',
        ]);
    }

    public function test_profile_upsert_rejects_invalid_fields(): void
    {
        $user = User::factory()->professional()->create();

        $this->actingAs($user)
            ->putJson('/api/v1/profile/engineer', [
                'photo_media_id' => 0,
                'experience_years' => 81,
                'expertise_tags' => ['ok', 123],
                'linkedin_url' => 'not-a-url',
                'job_availability' => 'invalid',
                'reputation_points' => -1,
                'ai_usage_count' => -1,
                'verification_renewed_at' => 'not-a-date',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'photo_media_id',
                'experience_years',
                'expertise_tags.1',
                'linkedin_url',
                'job_availability',
                'reputation_points',
                'ai_usage_count',
                'verification_renewed_at',
            ]);
    }

    public function test_my_engineer_profile_returns_not_found_when_missing(): void
    {
        $user = User::factory()->professional()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/profile/engineer')
            ->assertNotFound();
    }

    public function test_discoverable_profile_can_be_shown_to_other_user(): void
    {
        $viewer = User::factory()->professional()->create();
        $profile = EngineerProfileFactory::new()->discoverable()->create([
            'privacy_settings' => ['show_activity_feed' => true],
        ]);

        $this->actingAs($viewer)
            ->getJson("/api/v1/profiles/engineers/{$profile->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $profile->id)
            ->assertJsonPath('data.is_discoverable', true);
    }

    public function test_hidden_profile_returns_forbidden_to_other_user(): void
    {
        $viewer = User::factory()->professional()->create();
        $profile = UnverifiedMemberProfileFactory::new()->hiddenFromDirectory()->create();

        $this->actingAs($viewer)
            ->getJson("/api/v1/profiles/unverified-members/{$profile->id}")
            ->assertForbidden();
    }

    public function test_missing_public_profile_returns_not_found(): void
    {
        $viewer = User::factory()->professional()->create();

        $this->actingAs($viewer)
            ->getJson('/api/v1/profiles/engineers/999999')
            ->assertNotFound();
    }
}
