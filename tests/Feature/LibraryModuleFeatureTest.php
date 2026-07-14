<?php

namespace Tests\Feature;

use App\Jobs\ProcessLibraryItemIngestionJob;
use App\Models\ExpertiseRankTier;
use App\Models\KnowledgeDomain;
use App\Models\LibraryAccessRule;
use App\Models\LibraryCategory;
use App\Models\LibraryItem;
use App\Models\MandatoryQuizDomain;
use App\Models\PlantType;
use App\Models\QuizAttempt;
use App\Models\QuizQuestionChoice;
use App\Models\User;
use App\Models\UserExpertiseRank;
use App\Services\ExpertiseRankService;
use App\Services\KnowledgeDomainQuizService;
use App\Services\LibraryItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class LibraryModuleFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_library_schema_relationships_access_rules_and_approval_workflow(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        $user = User::factory()->professional()->create();
        $plantType = $this->plantType();
        $category = LibraryCategory::factory()->create(['title' => 'Operations Manuals']);
        $child = LibraryCategory::factory()->childOf($category)->create(['title' => 'Turbines']);
        $item = LibraryItem::factory()->create([
            'category_id' => $child->id,
            'plant_type_id' => $plantType->id,
            'status' => 'draft',
            'access_level' => 'gold',
            'download_allowed' => true,
            'copy_paste_disabled' => true,
            'content_type' => 'document',
        ]);

        $this->assertTrue(Schema::hasColumns('library_items', [
            'category_id',
            'plant_type_id',
            'access_level',
            'download_allowed',
            'copy_paste_disabled',
            'approved_by',
            'approved_at',
            'file_media_id',
        ]));
        $this->assertTrue(Schema::hasColumns('knowledge_domains', [
            'quiz_question_count',
        ]));
        $this->assertTrue($child->parent()->is($category));
        $this->assertTrue($item->category()->is($child));
        $this->assertTrue($item->plantType()->is($plantType));

        LibraryAccessRule::factory()->create([
            'partner_tier' => 'gold',
            'can_view' => true,
            'can_download' => true,
            'can_copy_paste' => false,
            'max_downloads_per_month' => 1,
        ]);

        $service = app(LibraryItemService::class);
        $approved = $service->approve($item, $admin);
        $this->assertSame('published', $approved->status);
        $this->assertSame($admin->id, $approved->approved_by);
        $this->assertNotNull($approved->approved_at);
        Queue::assertPushed(ProcessLibraryItemIngestionJob::class);

        $policy = $service->accessPolicy($approved, $user, 'gold');
        $this->assertTrue($policy['can_view']);
        $this->assertTrue($policy['can_download']);
        $this->assertFalse($policy['can_copy_paste']);

        $service->recordView($approved, $user, '127.0.0.1', 'gold');
        $service->recordDownload($approved, $user, '127.0.0.1', 'gold');
        $this->assertDatabaseHas('library_access_logs', [
            'library_item_id' => $approved->id,
            'user_id' => $user->id,
            'action' => 'download',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Monthly library download limit reached.');
        $service->recordDownload($approved, $user, '127.0.0.1', 'gold');
    }

    public function test_nested_quiz_management_scoring_cooldown_and_rank_promotion_rules(): void
    {
        Queue::fake();

        $user = User::factory()->professional()->create();
        $plantType = $this->plantType();
        $domain = KnowledgeDomain::query()->create([
            'name' => 'Gas Turbine Operations',
            'slug' => 'gas-turbine-operations',
            'description' => 'Core operations domain.',
            'plant_type_id' => $plantType->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $quizService = app(KnowledgeDomainQuizService::class);
        $question = $quizService->createQuestion($domain, [
            'question_text' => 'Which limit blocks restart after a failed start?',
            'difficulty_level' => 'medium',
            'status' => 'active',
            'choices' => [
                ['choice_text' => 'Rotor cooldown window', 'is_correct' => true, 'explanation' => 'Cooldown protects rotating equipment.'],
                ['choice_text' => 'Shift handover', 'is_correct' => false, 'explanation' => 'Administrative handover is not the technical limit.'],
            ],
        ], $user);

        $this->assertSame(1, $domain->refresh()->total_question_count);
        $this->assertTrue($question->choices->contains('is_correct', true));

        $attempt = $quizService->startAttempt($domain, $user, 1, 80);
        $wrongChoice = $question->choices->firstWhere('is_correct', false);
        $submitted = $quizService->submitAttempt($attempt, [$question->id => $wrongChoice->id]);
        $this->assertFalse($submitted->is_passed);
        $this->assertNotNull($submitted->next_attempt_allowed_at);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Quiz retry cooldown is still active.');
        $quizService->startAttempt($domain, $user, 1, 80);
    }

    public function test_knowledge_domain_quiz_question_count_setting_drives_attempt_snapshots(): void
    {
        Queue::fake();

        $user = User::factory()->professional()->create();
        $domain = KnowledgeDomain::query()->create([
            'name' => 'Excitation Systems',
            'slug' => 'excitation-systems',
            'description' => 'Generator excitation domain.',
            'quiz_question_count' => 2,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $quizService = app(KnowledgeDomainQuizService::class);

        $this->createServiceQuestion($quizService, $domain, 'What protects generator field winding?');
        $this->createServiceQuestion($quizService, $domain, 'Which device regulates excitation voltage?');
        $this->createServiceQuestion($quizService, $domain, 'Which alarm indicates brush wear?');

        $attempt = $quizService->startAttempt($domain, $user);

        $this->assertSame(2, $attempt->total_questions);
        $this->assertSame(2, $attempt->max_possible_score);
        $this->assertCount(2, $attempt->attemptQuestions);
        $this->assertSame(3, $domain->refresh()->total_question_count);
    }

    public function test_quiz_attempt_falls_back_to_available_active_questions(): void
    {
        Queue::fake();

        $user = User::factory()->professional()->create();
        $domain = KnowledgeDomain::query()->create([
            'name' => 'Boiler Water Chemistry',
            'slug' => 'boiler-water-chemistry',
            'description' => 'Chemistry controls domain.',
            'quiz_question_count' => 5,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $quizService = app(KnowledgeDomainQuizService::class);

        $this->createServiceQuestion($quizService, $domain, 'Which test tracks dissolved oxygen?');
        $this->createServiceQuestion($quizService, $domain, 'Which limit protects steam purity?');
        $this->createServiceQuestion($quizService, $domain, 'Which draft question is excluded?', 'draft');

        $attempt = $quizService->startAttempt($domain, $user);

        $this->assertSame(2, $attempt->total_questions);
        $this->assertSame(2, $attempt->max_possible_score);
        $this->assertCount(2, $attempt->attemptQuestions);
    }
    public function test_rank_promotion_evaluates_mandatory_domain_passes(): void
    {
        $user = User::factory()->professional()->create();
        $plantType = $this->plantType();
        $domain = KnowledgeDomain::query()->create([
            'name' => 'Transformer Diagnostics',
            'slug' => 'transformer-diagnostics',
            'description' => 'Transformer core domain.',
            'plant_type_id' => $plantType->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $currentTier = ExpertiseRankTier::factory()->active()->create([
            'name' => 'Associate',
            'slug' => 'associate',
            'rank_order' => 10,
            'default_cap_percentage' => 35,
        ]);
        $nextTier = ExpertiseRankTier::factory()->active()->create([
            'name' => 'Professional',
            'slug' => 'professional',
            'rank_order' => 20,
            'required_quiz_count' => 1,
            'required_mandatory_quiz_count' => 1,
            'default_cap_percentage' => 55,
        ]);
        UserExpertiseRank::query()->create([
            'user_id' => $user->id,
            'rank_tier_id' => $currentTier->id,
            'promotion_source' => 'admin_manual_review',
            'effective_at' => now()->subMonth(),
            'is_current' => true,
        ]);
        MandatoryQuizDomain::factory()->active()->create([
            'plant_type_id' => $plantType->id,
            'knowledge_domain_id' => $domain->id,
        ]);
        $quizId = \Illuminate\Support\Facades\DB::table('quizzes')->insertGetId([
            'knowledge_domain_id' => $domain->id,
            'title' => 'Transformer Diagnostics Baseline',
            'slug' => 'transformer-diagnostics-baseline',
            'status' => 'published',
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $attempt = QuizAttempt::query()->create([
            'quiz_id' => $quizId,
            'user_id' => $user->id,
            'attempt_number' => 1,
            'score' => 1,
            'max_possible_score' => 1,
            'knowledge_domain_id' => $domain->id,
            'total_questions' => 1,
            'correct_count' => 1,
            'score_percentage' => 100,
            'pass_threshold' => 80,
            'is_passed' => true,
            'started_at' => now()->subMinutes(5),
            'submitted_at' => now(),
            'counted_for_rank_promotion' => false,
        ]);

        $promotion = app(ExpertiseRankService::class)->evaluatePromotion($user, $plantType->id);

        $this->assertNotNull($promotion);
        $this->assertSame($nextTier->id, $promotion->rank_tier_id);
        $this->assertDatabaseHas('rank_promotion_quiz_logs', [
            'user_id' => $user->id,
            'quiz_attempt_id' => $attempt->id,
            'knowledge_domain_id' => $domain->id,
            'is_mandatory' => true,
        ]);
        $this->assertFalse($currentTier->userExpertiseRanks()->where('user_id', $user->id)->first()->is_current);
    }

    public function test_library_api_authorization_nested_quiz_routes_and_admin_sidebar_constraints(): void
    {
        $admin = User::factory()->admin()->create();
        $professional = User::factory()->professional()->create();
        $category = LibraryCategory::factory()->create();
        $domain = KnowledgeDomain::query()->create([
            'name' => 'Arc Flash Safety',
            'slug' => 'arc-flash-safety',
            'description' => 'Safety domain.',
            'quiz_question_count' => 1,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->postJson('/api/v1/library/items', [])->assertUnauthorized();

        $this->actingAs($admin)->post(route('admin.dashboard.library.knowledge-domains.store'), [
            'name' => 'Relay Coordination',
            'slug' => 'relay-coordination',
            'description' => 'Protection relay quiz domain.',
            'plant_type_id' => null,
            'icon' => 'bi bi-lightning-charge',
            'quiz_question_count' => 12,
            'is_active' => 1,
            'sort_order' => 2,
        ])->assertRedirect();
        $createdDomain = KnowledgeDomain::query()->where('slug', 'relay-coordination')->firstOrFail();
        $this->assertSame(12, $createdDomain->quiz_question_count);

        $this->actingAs($admin)->put(route('admin.dashboard.library.knowledge-domains.update', $createdDomain), [
            'name' => 'Relay Coordination',
            'slug' => 'relay-coordination',
            'description' => 'Protection relay quiz domain.',
            'plant_type_id' => null,
            'icon' => 'bi bi-lightning-charge',
            'quiz_question_count' => 7,
            'is_active' => 1,
            'sort_order' => 2,
        ])->assertRedirect();
        $this->assertSame(7, $createdDomain->refresh()->quiz_question_count);
        $this->actingAs($professional)->postJson('/api/v1/library/items', [
            'category_id' => $category->id,
            'title' => 'Restricted',
            'slug' => 'restricted',
            'content_type' => 'document',
        ])->assertForbidden();
        $this->actingAs($admin)->postJson('/api/v1/library/items', [
            'category_id' => 999999,
            'access_level' => 'invalid',
            'content_type' => 'bad-type',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'access_level', 'content_type']);

        $this->actingAs($admin)->postJson("/api/v1/library/knowledge-domains/{$domain->id}/quiz-questions", [
            'question_text' => 'Which permit confirms arc flash boundaries?',
            'difficulty_level' => 'easy',
            'status' => 'active',
            'choices' => [
                ['choice_text' => 'Energized work permit', 'is_correct' => true, 'explanation' => 'Correct authorization.'],
                ['choice_text' => 'Warehouse request', 'is_correct' => false, 'explanation' => 'Not a safety permit.'],
            ],
        ])->assertSuccessful()
            ->assertJsonPath('data.knowledge_domain_id', $domain->id)
            ->assertJsonCount(2, 'data.choices');

        $this->assertFalse(collect(Route::getRoutes())->contains(
            fn ($route): bool => str_contains($route->uri(), 'dashboard/library/quiz-questions')
                || str_contains($route->uri(), 'dashboard/library/questions')
        ));

        $this->actingAs($admin)
            ->get(route('admin.dashboard.library.knowledge-domains.edit', $domain))
            ->assertOk()
            ->assertSee('Library Items')
            ->assertSee('Knowledge Domains')
            ->assertSee('Ranks Tiers')
            ->assertSee('Embedded Quiz Question Manager')
            ->assertDontSee('top-level Quiz Questions');

        $this->actingAs($admin)
            ->get('/dashboard/library/rank-tiers')
            ->assertOk()
            ->assertDontSee('Promotion Rule Visibility')
            ->assertDontSee('Mandatory Quiz Domains')
            ->assertDontSee('Quiz Questions');
    }

    public function test_rank_tiers_index_uses_database_seed_data_and_status_filters(): void
    {
        $admin = User::factory()->admin()->create();
        ExpertiseRankTier::factory()->create([
            'name' => 'Legacy Specialist',
            'slug' => 'legacy-specialist',
            'status' => 'active',
            'is_active' => true,
            'rank_order' => 99,
        ]);

        $this->seed(\Database\Seeders\LibrarySeeder::class);

        $baselineNames = ExpertiseRankTier::query()
            ->where('status', 'active')
            ->orderBy('rank_order')
            ->pluck('name')
            ->all();

        $this->assertSame([
            'Unverified user',
            'Industry Professional',
            'Experienced Professional',
            'Senior Industry Expert',
        ], $baselineNames);

        $this->actingAs($admin)
            ->get(route('admin.dashboard.library.rank-tiers.index'))
            ->assertOk()
            ->assertSeeInOrder($baselineNames)
            ->assertDontSee('Legacy Specialist')
            ->assertDontSee('Mandatory Quiz Domains')
            ->assertDontSee('Promotion Rule Visibility');

        $this->actingAs($admin)
            ->get(route('admin.dashboard.library.rank-tiers.index', ['status' => 'deleted']))
            ->assertOk()
            ->assertSee('Legacy Specialist')
            ->assertDontSee('Unverified user');
    }

    public function test_rank_tiers_clone_delete_and_status_transitions(): void
    {
        $admin = User::factory()->admin()->create();
        $tier = ExpertiseRankTier::factory()->active()->create([
            'name' => 'Control Room Expert',
            'slug' => 'control-room-expert',
            'rank_order' => 40,
        ]);
        ExpertiseRankTier::factory()->active()->create([
            'name' => 'Senior Dispatcher',
            'slug' => 'senior-dispatcher',
            'rank_order' => 50,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.dashboard.library.rank-tiers.clone', $tier))
            ->assertRedirect(route('admin.dashboard.library.rank-tiers.index', ['status' => 'draft']));

        $clone = ExpertiseRankTier::query()->where('slug', 'control-room-expert-copy')->firstOrFail();
        $this->assertSame('Control Room Expert Copy', $clone->name);
        $this->assertSame(51, $clone->rank_order);
        $this->assertSame('draft', $clone->status);
        $this->assertFalse($clone->is_active);

        $this->actingAs($admin)
            ->delete(route('admin.dashboard.library.rank-tiers.destroy', $tier))
            ->assertRedirect(route('admin.dashboard.library.rank-tiers.index', ['status' => 'deleted']));

        $this->assertDatabaseHas('expertise_rank_tiers', [
            'id' => $tier->id,
            'status' => 'deleted',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.dashboard.library.rank-tiers.status', $tier), ['status' => 'active'])
            ->assertRedirect(route('admin.dashboard.library.rank-tiers.index', ['status' => 'active']));

        $this->assertDatabaseHas('expertise_rank_tiers', [
            'id' => $tier->id,
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.dashboard.library.rank-tiers.status', $tier), ['status' => 'draft'])
            ->assertRedirect(route('admin.dashboard.library.rank-tiers.index', ['status' => 'draft']));

        $this->assertDatabaseHas('expertise_rank_tiers', [
            'id' => $tier->id,
            'status' => 'draft',
            'is_active' => false,
        ]);
    }

    public function test_rank_tiers_requires_authorized_admin_access(): void
    {
        $professional = User::factory()->professional()->create();

        $this->get(route('admin.dashboard.library.rank-tiers.index'))
            ->assertRedirect(route('login'));

        $this->actingAs($professional)
            ->get(route('admin.dashboard.library.rank-tiers.index'))
            ->assertForbidden();
    }
    public function test_rank_tiers_listing_displays_database_numeric_values(): void
    {
        $admin = User::factory()->admin()->create();
        $tier = ExpertiseRankTier::factory()->active()->create([
            'name' => 'Grid Reliability Fellow',
            'slug' => 'grid-reliability-fellow',
            'rank_order' => 77,
            'default_cap_percentage' => 47.25,
            'min_years_experience' => 11,
            'required_quiz_count' => 13,
            'required_mandatory_quiz_count' => 4,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard.library.rank-tiers.index'))
            ->assertOk()
            ->assertSeeInOrder([
                $tier->name,
                $tier->slug,
                '#77',
                'Active',
                '47.25%',
                '11 years',
                '13',
                '4',
            ]);
    }

    public function test_rank_tier_edit_page_omits_removed_configuration_panels(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard.library.rank-tiers.edit', 301))
            ->assertOk()
            ->assertDontSee('Mandatory Quiz Domain Configuration')
            ->assertDontSee('Promotion Rule Preview');
    }
    private function createServiceQuestion(KnowledgeDomainQuizService $quizService, KnowledgeDomain $domain, string $questionText, string $status = 'active'): void
    {
        $quizService->createQuestion($domain, [
            'question_text' => $questionText,
            'difficulty_level' => 'medium',
            'status' => $status,
            'choices' => [
                ['choice_text' => 'Correct answer', 'is_correct' => true, 'explanation' => 'Expected choice.'],
                ['choice_text' => 'Incorrect answer', 'is_correct' => false, 'explanation' => 'Distractor choice.'],
            ],
        ]);
    }
    private function plantType(): PlantType
    {
        return PlantType::query()->firstOrCreate(
            ['slug' => 'library-test-plant'],
            [
                'name' => 'Library Test Plant',
                'description' => 'Plant context for Library tests.',
                'is_active' => true,
                'sort_order' => 1,
            ],
        );
    }
}
