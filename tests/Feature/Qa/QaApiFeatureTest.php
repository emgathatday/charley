<?php

namespace Tests\Feature\Qa;

use App\Models\Answer;
use App\Models\KnowledgeDomain;
use App\Models\LeaderboardSetting;
use App\Models\MediaFile;
use App\Models\MonthlyLeaderboardSnapshot;
use App\Models\PartnerProfile;
use App\Models\PlantType;
use App\Models\PointTransaction;
use App\Models\Question;
use App\Models\User;
use App\Models\WeeklyTheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'auth.guards.sanctum' => [
                'driver' => 'session',
                'provider' => 'users',
            ],
        ]);
    }

    public function test_public_question_index_returns_published_questions_structure(): void
    {
        $published = Question::factory()->published()->create(['is_anonymous' => false]);
        Question::factory()->create(['status' => 'pending']);

        $this->getJson('/api/v1/qa/questions')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'user_id',
                        'title',
                        'body',
                        'is_anonymous',
                        'status',
                        'attachment_media_ids',
                    ],
                ],
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $published->id);
    }

    public function test_public_question_show_returns_not_found_for_unpublished_question(): void
    {
        $question = Question::factory()->create(['status' => 'pending']);

        $this->getJson("/api/v1/qa/questions/{$question->id}")
            ->assertNotFound();
    }

    public function test_authenticated_user_can_post_anonymous_question_with_media_and_domains(): void
    {
        $user = User::factory()->professional()->create();
        $media = $this->createMediaFile();
        $domain = KnowledgeDomain::factory()->active()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/qa/questions', [
                'title' => 'Why is my ammonia plant yellowing?',
                'body' => 'The lower leaves are yellow after repotting.',
                'is_anonymous' => true,
                'attachment_media_ids' => [$media->id],
                'knowledge_domain_ids' => [$domain->id],
                'status' => 'pending',
            ])
            ->assertCreated()
            ->assertJsonPath('data.user_id', null)
            ->assertJsonPath('data.is_anonymous', true)
            ->assertJsonPath('data.attachment_media_ids.0', $media->id);

        $this->assertDatabaseHas('question_domain_links', ['knowledge_domain_id' => $domain->id]);
    }

    public function test_question_store_requires_authentication_and_valid_payload(): void
    {
        $this->postJson('/api/v1/qa/questions', [])
            ->assertUnauthorized();

        $user = User::factory()->professional()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/qa/questions', [
                'title' => '',
                'body' => '',
                'is_anonymous' => 'not-boolean',
                'attachment_media_ids' => [999999],
                'knowledge_domain_ids' => [999999],
                'status' => 'hidden',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'body',
                'is_anonymous',
                'attachment_media_ids.0',
                'knowledge_domain_ids.0',
                'status',
            ]);
    }

    public function test_non_admin_cannot_post_question_on_behalf_of_partner(): void
    {
        $user = User::factory()->professional()->create();
        $partner = $this->createPartnerProfile();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/qa/questions', [
                'posted_by_admin_id' => $user->id,
                'on_behalf_of_partner_id' => $partner->id,
                'title' => 'Partner proxy question',
                'body' => 'Only admins should be able to proxy post.',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_post_question_on_behalf_of_partner(): void
    {
        $admin = User::factory()->admin()->create();
        $partner = $this->createPartnerProfile();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/qa/questions', [
                'posted_by_admin_id' => $admin->id,
                'on_behalf_of_partner_id' => $partner->id,
                'title' => 'Partner proxy question',
                'body' => 'Admin submits a question for a partner.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.posted_by_admin_id', $admin->id)
            ->assertJsonPath('data.on_behalf_of_partner_id', $partner->id);
    }

    public function test_authenticated_user_can_answer_published_question_anonymously(): void
    {
        $user = User::factory()->professional()->create();
        $question = Question::factory()->published()->create(['is_anonymous' => false]);
        $media = $this->createMediaFile();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/qa/questions/{$question->id}/answers", [
                'body' => 'Water only when the top soil is dry.',
                'is_anonymous' => true,
                'attachment_media_ids' => [$media->id],
            ])
            ->assertCreated()
            ->assertJsonPath('data.user_id', null)
            ->assertJsonPath('data.is_anonymous', true)
            ->assertJsonPath('data.attachment_media_ids.0', $media->id);
    }

    public function test_answer_store_requires_auth_and_published_question(): void
    {
        $question = Question::factory()->published()->create(['is_anonymous' => false]);

        $this->postJson("/api/v1/qa/questions/{$question->id}/answers", ['body' => 'No token'])
            ->assertUnauthorized();

        $draft = Question::factory()->create(['status' => 'pending']);

        $this->actingAs(User::factory()->professional()->create(), 'sanctum')
            ->postJson("/api/v1/qa/questions/{$draft->id}/answers", ['body' => 'Cannot answer drafts'])
            ->assertNotFound();
    }

    public function test_answer_store_validates_required_fields_and_media_ids(): void
    {
        $question = Question::factory()->published()->create(['is_anonymous' => false]);

        $this->actingAs(User::factory()->professional()->create(), 'sanctum')
            ->postJson("/api/v1/qa/questions/{$question->id}/answers", [
                'body' => '',
                'is_anonymous' => 'bad',
                'attachment_media_ids' => [999999],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['body', 'is_anonymous', 'attachment_media_ids.0']);
    }

    public function test_admin_can_review_questions_and_moderate_answers(): void
    {
        $admin = User::factory()->admin()->create();
        $question = Question::factory()->create(['status' => 'pending']);
        $answer = Answer::factory()->create(['question_id' => $question->id]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/qa/questions/{$question->id}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/qa/answers/{$answer->id}/feature", [
                'confidence_level' => 'high',
                'admin_rank_order' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('data.is_admin_featured', true)
            ->assertJsonPath('data.confidence_level', 'high');
    }

    public function test_non_admin_cannot_use_admin_qa_endpoints(): void
    {
        $question = Question::factory()->create(['status' => 'pending']);

        $this->postJson("/api/v1/admin/qa/questions/{$question->id}/publish")
            ->assertUnauthorized();

        $this->actingAs(User::factory()->professional()->create(), 'sanctum')
            ->postJson("/api/v1/admin/qa/questions/{$question->id}/publish")
            ->assertForbidden();
    }

    public function test_admin_weekly_theme_management_supports_create_update_and_archive(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/qa/weekly-themes', [
                'title' => 'Monsoon plant care',
                'description' => null,
                'week_start_date' => '2026-07-13',
                'week_end_date' => '2026-07-19',
                'status' => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Monsoon plant care')
            ->assertJsonPath('data.created_by_admin_id', $admin->id);

        $themeId = $response->json('data.id');

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/qa/weekly-themes/{$themeId}", [
                'title' => 'Updated plant care',
                'description' => 'Updated',
                'week_start_date' => '2026-07-13',
                'week_end_date' => '2026-07-20',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated plant care');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/qa/weekly-themes/{$themeId}/archive")
            ->assertOk()
            ->assertJsonPath('data.status', 'archived');
    }

    public function test_weekly_theme_management_rejects_invalid_payload_and_non_admin(): void
    {
        $this->actingAs(User::factory()->professional()->create(), 'sanctum')
            ->postJson('/api/v1/admin/qa/weekly-themes', [
                'title' => 'Denied',
                'week_start_date' => '2026-07-13',
                'week_end_date' => '2026-07-19',
            ])
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create(), 'sanctum')
            ->postJson('/api/v1/admin/qa/weekly-themes', [
                'title' => '',
                'week_start_date' => '2026-07-20',
                'week_end_date' => '2026-07-13',
                'status' => 'draft',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'week_end_date', 'status']);
    }

    public function test_public_weekly_theme_and_leaderboard_endpoints_return_structures(): void
    {
        $theme = WeeklyTheme::factory()->active()->create(['week_start_date' => '2026-07-13']);
        $snapshot = MonthlyLeaderboardSnapshot::factory()->create(['year_month' => '2026-07', 'rank_position' => 1]);

        $this->getJson('/api/v1/qa/weekly-themes')
            ->assertOk()
            ->assertJsonPath('data.0.id', $theme->id);

        $this->getJson('/api/v1/qa/leaderboard/monthly/2026-07')
            ->assertOk()
            ->assertJsonPath('data.0.id', $snapshot->id)
            ->assertJsonPath('data.0.rank_position', 1);
    }

    public function test_admin_can_adjust_reputation_and_generate_monthly_snapshot(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->professional()->create();
        LeaderboardSetting::factory()->create([
            'min_points_threshold' => 10,
            'top_n' => 10,
            'effective_from' => '2026-01-01',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/qa/reputation/adjustments', [
                'user_id' => $user->id,
                'points' => 25,
                'reason' => 'Helpful expert answer.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.points', 25)
            ->assertJsonPath('data.source_type', 'manual_adjustment');

        PointTransaction::query()
            ->where('user_id', $user->id)
            ->update(['created_at' => '2026-07-16 10:00:00']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/qa/leaderboard/monthly/2026-07/snapshots')
            ->assertOk()
            ->assertJsonPath('data.0.user_id', $user->id)
            ->assertJsonPath('data.0.rank_position', 1);
    }

    public function test_reputation_adjustment_validates_payload_and_role(): void
    {
        $this->actingAs(User::factory()->professional()->create(), 'sanctum')
            ->postJson('/api/v1/admin/qa/reputation/adjustments', [
                'user_id' => User::factory()->professional()->create()->id,
                'points' => 1,
                'reason' => 'Denied',
            ])
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create(), 'sanctum')
            ->postJson('/api/v1/admin/qa/reputation/adjustments', [
                'user_id' => 999999,
                'points' => 'bad',
                'reason' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'points', 'reason']);
    }

    private function createMediaFile(array $attributes = []): MediaFile
    {
        return MediaFile::query()->create(array_merge([
            'disk' => 'public',
            'path' => 'qa/test-image.jpg',
            'original_name' => 'test-image.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'uploader_id' => User::factory()->create()->id,
            'upload_context' => 'question_attachment',
            'file_category' => 'image',
        ], $attributes));
    }

    private function createPartnerProfile(array $attributes = []): PartnerProfile
    {
        return PartnerProfile::factory()->approved()->create(array_merge([
            'plant_type_id' => PlantType::query()->create([
                'name' => 'Ammonia',
                'slug' => 'ammonia',
                'description' => 'Ammonia plant',
                'is_active' => true,
                'sort_order' => 1,
            ])->id,
        ], $attributes));
    }
}
