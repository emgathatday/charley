<?php

namespace Tests\Feature;

use App\Models\DomainRankTier;
use App\Models\KnowledgeDomain;
use App\Models\LibraryCategory;
use App\Models\LibraryItem;
use App\Models\LibraryItemHotspot;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeDomainApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_api_lists_domains_rank_tiers_quizzes_and_hotspots(): void
    {
        $domain = $this->domain(['name' => 'Process Safety', 'slug' => 'process-safety']);
        $tier = $this->tier($domain, ['name' => 'Operator', 'min_points' => 1]);
        $quiz = $this->quiz($domain, ['title' => 'Safety Quiz', 'slug' => 'safety-quiz']);
        $question = $this->question($quiz);
        $item = $this->item();
        LibraryItemHotspot::query()->create(['library_item_id' => $item->id, 'knowledge_domain_id' => $domain->id, 'label' => 'Pump', 'shape_type' => 'rect', 'coordinates' => ['x' => 1], 'sort_order' => 1]);

        $this->getJson('/api/v1/library/knowledge-domains')->assertOk()->assertJsonPath('data.0.slug', 'process-safety');
        $this->getJson('/api/v1/library/knowledge-domains/process-safety/rank-tiers')->assertOk()->assertJsonPath('data.0.name', 'Operator');
        $this->getJson('/api/v1/library/quizzes')->assertOk()->assertJsonPath('data.0.slug', 'safety-quiz');
        $this->getJson('/api/v1/library/quizzes/safety-quiz')->assertOk()->assertJsonPath('data.questions.0.correct_answer', null);
        $this->getJson("/api/v1/library/items/{$item->id}/hotspots")->assertOk()->assertJsonPath('data.0.display_label', 'Pump');
    }

    public function test_admin_domain_api_requires_auth_admin_and_validates_payloads(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->professional()->create();

        $this->postJson('/api/v1/library/admin/knowledge-domains', [])->assertUnauthorized();
        $this->actingAs($user)->postJson('/api/v1/library/admin/knowledge-domains', [])->assertForbidden();

        $this->actingAs($admin)->postJson('/api/v1/library/admin/knowledge-domains', [
            'name' => '', 'slug' => '', 'status' => 'bad',
            'rank_tiers' => [['name' => '', 'min_points' => -1, 'sort_order' => 0]],
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'slug', 'status', 'rank_tiers.0.name', 'rank_tiers.0.min_points', 'rank_tiers.0.sort_order']);

        $this->actingAs($admin)->postJson('/api/v1/library/admin/knowledge-domains', [
            'name' => 'Operations', 'slug' => 'operations', 'status' => KnowledgeDomain::STATUS_ACTIVE,
            'rank_tiers' => [['name' => 'Starter', 'min_points' => 0, 'sort_order' => 1]],
        ])->assertCreated()->assertJsonPath('data.slug', 'operations')->assertJsonPath('data.rank_tiers.0.name', 'Starter');

        $this->actingAs($admin)->postJson('/api/v1/library/admin/knowledge-domains/operations/archive')->assertOk()->assertJsonPath('data.status', KnowledgeDomain::STATUS_ARCHIVED);
    }

    public function test_quiz_attempts_domain_points_and_admin_quiz_crud(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->professional()->create();
        $domain = $this->domain();
        $tier = $this->tier($domain, ['min_points' => 2]);
        $quiz = $this->quiz($domain, ['max_attempts_per_user' => 2]);
        $question = $this->question($quiz, ['correct_answer' => '1', 'points' => 2]);

        $this->postJson("/api/v1/library/quizzes/{$quiz->slug}/attempts", ['answers' => []])->assertUnauthorized();
        $this->actingAs($user)->postJson("/api/v1/library/quizzes/{$quiz->slug}/attempts", ['answers' => [$question->id => '1']])
            ->assertCreated()->assertJsonPath('data.score', 2);
        $this->actingAs($user)->getJson('/api/v1/library/domain-points')->assertOk()->assertJsonPath('data.0.current_rank_tier_id', $tier->id);

        $this->actingAs($admin)->postJson('/api/v1/library/admin/quizzes', ['knowledge_domain_id' => 999999, 'title' => '', 'slug' => ''])
            ->assertUnprocessable()->assertJsonValidationErrors(['knowledge_domain_id', 'title', 'slug']);
        $this->actingAs($admin)->postJson('/api/v1/library/admin/quizzes', ['knowledge_domain_id' => $domain->id, 'title' => 'Admin Quiz', 'slug' => 'admin-quiz', 'status' => Quiz::STATUS_PUBLISHED])
            ->assertCreated()->assertJsonPath('data.slug', 'admin-quiz');
    }

    private function domain(array $attributes = []): KnowledgeDomain
    {
        return KnowledgeDomain::query()->create(array_merge(['name' => 'Domain', 'slug' => 'domain-'.uniqid(), 'status' => KnowledgeDomain::STATUS_ACTIVE], $attributes));
    }

    private function tier(KnowledgeDomain $domain, array $attributes = []): DomainRankTier
    {
        return DomainRankTier::query()->create(array_merge(['knowledge_domain_id' => $domain->id, 'name' => 'Tier', 'min_points' => 0, 'sort_order' => 1], $attributes));
    }

    private function quiz(KnowledgeDomain $domain, array $attributes = []): Quiz
    {
        return Quiz::query()->create(array_merge(['knowledge_domain_id' => $domain->id, 'title' => 'Quiz', 'slug' => 'quiz-'.uniqid(), 'status' => Quiz::STATUS_PUBLISHED], $attributes));
    }

    private function question(Quiz $quiz, array $attributes = []): QuizQuestion
    {
        return QuizQuestion::query()->create(array_merge(['quiz_id' => $quiz->id, 'question_text' => 'Question', 'question_type' => 'single_choice', 'options' => ['A', 'B'], 'correct_answer' => '0', 'points' => 1, 'sort_order' => 1], $attributes));
    }

    private function item(): LibraryItem
    {
        $category = LibraryCategory::query()->create(['title' => 'Category', 'slug' => 'category-'.uniqid(), 'sort_order' => 1]);
        return LibraryItem::query()->create(['category_id' => $category->id, 'title' => 'Item', 'slug' => 'item-'.uniqid(), 'content' => 'Body', 'access_level' => 'public', 'download_allowed' => false, 'copy_paste_disabled' => false, 'status' => 'published', 'is_ai_trainable' => true, 'content_type' => 'article', 'item_type' => 'article', 'download_count' => 0, 'view_count' => 0]);
    }
}
