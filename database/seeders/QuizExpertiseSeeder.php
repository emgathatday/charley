<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuizExpertiseSeeder extends Seeder
{
    public function run(): void
    {
        $adminUserId = DB::table('users')->where('role', 'admin')->value('id')
            ?? DB::table('users')->value('id');
        $sampleUserId = DB::table('users')->where('role', 'professional')->value('id')
            ?? DB::table('users')->where('is_verified', true)->value('id')
            ?? DB::table('users')->value('id');
        $plantTypeId = DB::table('plant_types')->where('is_active', true)->orderBy('sort_order')->value('id')
            ?? DB::table('plant_types')->value('id');
        $targetLevelId = DB::table('expertise_levels')->where('code', 'experienced_professional')->value('id')
            ?? DB::table('expertise_levels')->orderBy('sort_order')->value('id');
        $baseLevelId = DB::table('expertise_levels')->where('code', 'industry_professional')->value('id')
            ?? $targetLevelId;

        $quizModel = new QuizExpertiseQuizSeedModel();
        $quiz = $quizModel->newQuery()->firstOrCreate(
            ['slug' => 'library-process-safety-foundations'],
            [
                'handbook_category_id' => null,
                'plant_type_id' => $plantTypeId,
                'title' => 'Library Process Safety Foundations',
                'description' => 'Safe demo quiz for validating Library section knowledge and expertise-rank promotion flow.',
                'passing_score_percent' => 70,
                'target_expertise_level_id' => $targetLevelId,
                'time_limit_minutes' => 30,
                'max_attempts_per_user' => 3,
                'status' => 'published',
                'created_by' => $adminUserId,
            ],
        );

        $questionModel = new QuizExpertiseQuestionSeedModel();
        foreach ($this->questions($quiz->id) as $question) {
            $questionModel->newQuery()->firstOrCreate(
                [
                    'quiz_id' => $quiz->id,
                    'sort_order' => $question['sort_order'],
                ],
                $question,
            );
        }

        if (! $sampleUserId || ! $baseLevelId) {
            return;
        }

        $rankModel = new QuizExpertiseRankSeedModel();
        $rankModel->newQuery()->firstOrCreate(
            [
                'user_id' => $sampleUserId,
                'plant_type_id' => $plantTypeId,
                'handbook_category_id' => null,
                'source' => 'cv_review',
            ],
            [
                'expertise_level_id' => $baseLevelId,
                'assigned_by' => $adminUserId,
                'quiz_attempt_id' => null,
                'notes' => 'Safe demo expertise rank assigned for Library quiz flow validation.',
                'is_current' => true,
                'assigned_at' => now(),
            ],
        );

        $attempt = (new QuizExpertiseAttemptSeedModel())->newQuery()->firstOrCreate(
            [
                'quiz_id' => $quiz->id,
                'user_id' => $sampleUserId,
                'attempt_number' => 1,
            ],
            [
                'answers_submitted' => null,
                'score' => 2,
                'max_possible_score' => 2,
                'score_percent' => 100,
                'passed' => true,
                'started_at' => now()->subMinutes(20),
                'completed_at' => now()->subMinutes(15),
            ],
        );

        if ($targetLevelId) {
            $rankModel->newQuery()->firstOrCreate(
                [
                    'user_id' => $sampleUserId,
                    'plant_type_id' => $plantTypeId,
                    'handbook_category_id' => null,
                    'source' => 'quiz_pass',
                    'quiz_attempt_id' => $attempt->id,
                ],
                [
                    'expertise_level_id' => $targetLevelId,
                    'assigned_by' => null,
                    'notes' => 'Safe demo quiz-pass expertise rank for Library extension.',
                    'is_current' => true,
                    'assigned_at' => now()->subMinutes(15),
                ],
            );
        }
    }

    private function questions(int $quizId): array
    {
        return [
            [
                'quiz_id' => $quizId,
                'question_text' => 'Which source should Charley AI use for Library answers?',
                'question_type' => 'single_choice',
                'options' => [
                    'Approved Library and Q&A content',
                    'Unreviewed vendor brochures',
                    'Private user messages',
                    'Unlicensed article titles',
                ],
                'correct_answer' => [0],
                'points' => 1,
                'explanation' => 'Charley AI must rely on approved technical content with safe citation handling.',
                'sort_order' => 10,
            ],
            [
                'quiz_id' => $quizId,
                'question_text' => 'Expertise rank is separate from contribution reputation points.',
                'question_type' => 'true_false',
                'options' => ['True', 'False'],
                'correct_answer' => [0],
                'points' => 1,
                'explanation' => 'Expertise rank tracks verified experience or quiz-pass knowledge by scope.',
                'sort_order' => 20,
            ],
        ];
    }
}

class QuizExpertiseQuizSeedModel extends Model
{
    protected $table = 'quizzes';

    protected $guarded = [];
}

class QuizExpertiseQuestionSeedModel extends Model
{
    protected $table = 'quiz_questions';

    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'array',
    ];
}

class QuizExpertiseAttemptSeedModel extends Model
{
    protected $table = 'quiz_attempts';

    protected $guarded = [];

    protected $casts = [
        'answers_submitted' => 'array',
        'passed' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}

class QuizExpertiseRankSeedModel extends Model
{
    protected $table = 'user_expertise_ranks';

    protected $guarded = [];

    protected $casts = [
        'is_current' => 'boolean',
        'assigned_at' => 'datetime',
    ];
}
