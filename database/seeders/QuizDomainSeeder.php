<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuizDomainSeeder extends Seeder
{
    public function run(): void
    {
        $adminUserId = DB::table('users')->where('role', 'admin')->value('id')
            ?? DB::table('users')->value('id');
        $quizModel = new QuizDomainSeedQuizModel();
        $questionModel = new QuizDomainSeedQuestionModel();

        foreach ($this->quizzes($adminUserId) as $domainSlug => $quizzes) {
            $domainId = DB::table('knowledge_domains')->where('slug', $domainSlug)->value('id');
            if (! $domainId) {
                continue;
            }

            foreach ($quizzes as $quiz) {
                $questions = $quiz['questions'];
                unset($quiz['questions']);

                $quizRecord = $quizModel->newQuery()->firstOrCreate(
                    ['slug' => $quiz['slug']],
                    $quiz + ['knowledge_domain_id' => $domainId],
                );

                foreach ($questions as $question) {
                    $questionModel->newQuery()->firstOrCreate(
                        [
                            'quiz_id' => $quizRecord->id,
                            'sort_order' => $question['sort_order'],
                        ],
                        $question + ['quiz_id' => $quizRecord->id],
                    );
                }
            }
        }

        $this->seedDemoAttemptAndRank();
        $this->seedDemoHotspots();
    }

    private function quizzes(?int $adminUserId): array
    {
        return [
            'reformer' => [
                [
                    'title' => 'Reformer Operations Foundations',
                    'slug' => 'reformer-operations-foundations',
                    'description' => 'Demo quiz for checking core reformer operation and troubleshooting knowledge.',
                    'time_limit_minutes' => 30,
                    'max_attempts_per_user' => null,
                    'status' => 'published',
                    'created_by' => $adminUserId,
                    'questions' => [
                        [
                            'question_text' => 'Which trend should be reviewed first when primary reformer pressure drop rises quickly?',
                            'question_type' => 'single_choice',
                            'options' => ['Feed composition and inlet temperature', 'Office shift roster', 'Paint color history', 'Warehouse inventory'],
                            'correct_answer' => 0,
                            'points' => 15,
                            'explanation' => 'Operating trends and feed conditions are the first diagnostic context for pressure-drop changes.',
                            'sort_order' => 1,
                        ],
                        [
                            'question_text' => 'Tube skin temperature checks help protect catalyst tubes during operation.',
                            'question_type' => 'true_false',
                            'options' => ['True', 'False'],
                            'correct_answer' => 0,
                            'points' => 10,
                            'explanation' => 'Tube skin temperatures are key safeguards for detecting local overheating risk.',
                            'sort_order' => 2,
                        ],
                    ],
                ],
                [
                    'title' => 'Reformer Troubleshooting Advanced',
                    'slug' => 'reformer-troubleshooting-advanced',
                    'description' => 'Demo quiz for higher weighted reformer diagnostic scenarios.',
                    'time_limit_minutes' => 45,
                    'max_attempts_per_user' => 5,
                    'status' => 'published',
                    'created_by' => $adminUserId,
                    'questions' => [
                        [
                            'question_text' => 'Select checks that support a balanced reformer troubleshooting review.',
                            'question_type' => 'multiple_choice',
                            'options' => ['Burner pattern', 'Steam to carbon ratio', 'Catalyst loading records', 'Cafeteria menu'],
                            'correct_answer' => [0, 1, 2],
                            'points' => 25,
                            'explanation' => 'Combustion, ratio control and catalyst records all matter for diagnosis.',
                            'sort_order' => 1,
                        ],
                    ],
                ],
            ],
            'process-safety' => [
                [
                    'title' => 'Process Safety Startup Basics',
                    'slug' => 'process-safety-startup-basics',
                    'description' => 'Demo quiz for startup readiness and safeguard awareness.',
                    'time_limit_minutes' => 20,
                    'max_attempts_per_user' => null,
                    'status' => 'published',
                    'created_by' => $adminUserId,
                    'questions' => [
                        [
                            'question_text' => 'A startup checklist should be reviewed before introducing hazardous feed.',
                            'question_type' => 'true_false',
                            'options' => ['True', 'False'],
                            'correct_answer' => 0,
                            'points' => 10,
                            'explanation' => 'Checklist discipline helps confirm safeguards and readiness before startup.',
                            'sort_order' => 1,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function seedDemoAttemptAndRank(): void
    {
        $userId = DB::table('users')->where('role', '<>', 'admin')->value('id')
            ?? DB::table('users')->value('id');
        if (! $userId) {
            return;
        }

        $quiz = DB::table('quizzes')->where('slug', 'reformer-operations-foundations')->first();
        if (! $quiz) {
            return;
        }

        $maxScore = (int) DB::table('quiz_questions')->where('quiz_id', $quiz->id)->sum('points');
        $score = max(0, $maxScore - 5);
        $completedAt = now()->subMinutes(5);

        $attempt = (new QuizDomainSeedAttemptModel())->newQuery()->firstOrCreate(
            [
                'quiz_id' => $quiz->id,
                'user_id' => $userId,
                'attempt_number' => 1,
            ],
            [
                'answers_submitted' => ['1' => 0, '2' => 0],
                'score' => $score,
                'max_possible_score' => $maxScore,
                'started_at' => now()->subMinutes(25),
                'completed_at' => $completedAt,
            ],
        );

        (new QuizDomainSeedBestScoreModel())->newQuery()->firstOrCreate(
            [
                'user_id' => $userId,
                'quiz_id' => $quiz->id,
            ],
            [
                'best_score' => $score,
                'best_quiz_attempt_id' => $attempt->id,
                'achieved_at' => $completedAt,
            ],
        );

        $rankTierId = DB::table('domain_rank_tiers')
            ->where('knowledge_domain_id', $quiz->knowledge_domain_id)
            ->where('min_points', '<=', $score)
            ->orderByDesc('sort_order')
            ->value('id');

        (new QuizDomainSeedDomainPointModel())->newQuery()->firstOrCreate(
            [
                'user_id' => $userId,
                'knowledge_domain_id' => $quiz->knowledge_domain_id,
            ],
            [
                'total_points' => $score,
                'current_rank_tier_id' => $rankTierId,
                'last_recalculated_at' => now(),
            ],
        );
    }

    private function seedDemoHotspots(): void
    {
        $libraryItemId = DB::table('library_items')->where('status', 'published')->orderBy('id')->value('id')
            ?? DB::table('library_items')->orderBy('id')->value('id');
        if (! $libraryItemId) {
            return;
        }

        $hotspotModel = new QuizDomainSeedHotspotModel();
        foreach ([
            ['slug' => 'reformer', 'label' => 'Reformer section', 'sort_order' => 1, 'coordinates' => [[12, 18], [44, 18], [40, 48], [15, 45]]],
            ['slug' => 'process-safety', 'label' => 'Safety checkpoint', 'sort_order' => 2, 'coordinates' => ['x' => 52, 'y' => 20, 'width' => 18, 'height' => 22]],
        ] as $hotspot) {
            $domainId = DB::table('knowledge_domains')->where('slug', $hotspot['slug'])->value('id');
            if (! $domainId) {
                continue;
            }

            $hotspotModel->newQuery()->firstOrCreate(
                [
                    'library_item_id' => $libraryItemId,
                    'knowledge_domain_id' => $domainId,
                    'sort_order' => $hotspot['sort_order'],
                ],
                [
                    'label' => $hotspot['label'],
                    'shape_type' => $hotspot['slug'] === 'reformer' ? 'polygon' : 'rect',
                    'coordinates' => $hotspot['coordinates'],
                ],
            );
        }
    }
}

class QuizDomainSeedQuizModel extends Model
{
    protected $table = 'quizzes';

    protected $guarded = [];
}

class QuizDomainSeedQuestionModel extends Model
{
    protected $table = 'quiz_questions';

    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'array',
    ];
}

class QuizDomainSeedAttemptModel extends Model
{
    protected $table = 'quiz_attempts';

    protected $guarded = [];

    protected $casts = [
        'answers_submitted' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}

class QuizDomainSeedBestScoreModel extends Model
{
    protected $table = 'user_quiz_best_scores';

    protected $guarded = [];

    protected $casts = [
        'achieved_at' => 'datetime',
    ];
}

class QuizDomainSeedDomainPointModel extends Model
{
    protected $table = 'user_domain_points';

    protected $guarded = [];

    protected $casts = [
        'last_recalculated_at' => 'datetime',
    ];
}

class QuizDomainSeedHotspotModel extends Model
{
    protected $table = 'library_item_hotspots';

    protected $guarded = [];

    protected $casts = [
        'coordinates' => 'array',
    ];
}