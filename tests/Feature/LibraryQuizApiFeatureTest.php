<?php

namespace Tests\Feature;

use App\Models\KnowledgeDomain;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryQuizApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_quiz_show_hides_answers_from_members_and_returns_answers_to_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $domain = KnowledgeDomain::query()->create(['name' => 'Safety', 'slug' => 'safety', 'status' => KnowledgeDomain::STATUS_ACTIVE]);
        $quiz = Quiz::query()->create(['knowledge_domain_id' => $domain->id, 'title' => 'Safety Quiz', 'slug' => 'safety-quiz', 'status' => Quiz::STATUS_PUBLISHED]);
        QuizQuestion::query()->create(['quiz_id' => $quiz->id, 'question_text' => 'Pick', 'question_type' => 'single_choice', 'options' => ['A', 'B'], 'correct_answer' => '0', 'points' => 1, 'sort_order' => 1]);

        $this->getJson('/api/v1/library/quizzes/safety-quiz')->assertOk()->assertJsonPath('data.questions.0.correct_answer', null);
        $this->actingAs($admin)->getJson('/api/v1/library/quizzes/safety-quiz')->assertOk()->assertJsonPath('data.questions.0.correct_answer', '0');
    }

    public function test_draft_quiz_is_hidden_from_public_but_visible_to_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $domain = KnowledgeDomain::query()->create(['name' => 'Draft Domain', 'slug' => 'draft-domain', 'status' => KnowledgeDomain::STATUS_ACTIVE]);
        Quiz::query()->create(['knowledge_domain_id' => $domain->id, 'title' => 'Draft Quiz', 'slug' => 'draft-quiz', 'status' => Quiz::STATUS_DRAFT]);

        $this->getJson('/api/v1/library/quizzes/draft-quiz')->assertNotFound();
        $this->actingAs($admin)->getJson('/api/v1/library/quizzes/draft-quiz')->assertOk()->assertJsonPath('data.slug', 'draft-quiz');
    }
}
