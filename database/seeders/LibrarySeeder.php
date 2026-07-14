<?php

namespace Database\Seeders;

use App\Models\ExpertiseRankTier;
use App\Models\KnowledgeDomain;
use App\Models\LibraryAccessRule;
use App\Models\LibraryCategory;
use App\Models\LibraryItem;
use App\Models\MandatoryQuizDomain;
use App\Models\PlantType;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionChoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LibrarySeeder extends Seeder
{
    public function run(): void
    {
        $user = $this->libraryUser();
        $plantTypes = PlantType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(3)
            ->get();

        if ($plantTypes->isEmpty()) {
            $plantTypes = collect([
                PlantType::query()->firstOrCreate(
                    ['slug' => 'library-demo-plant'],
                    [
                        'name' => 'Library Demo Plant',
                        'description' => 'Demo plant type used by library seed data.',
                        'is_active' => true,
                        'sort_order' => 999,
                    ]
                ),
            ]);
        }

        $categories = $this->seedCategories();
        $this->seedAccessRules($user);
        $domains = $this->seedKnowledgeDomains($plantTypes, $user);
        $this->seedLibraryItems($categories, $domains, $user);
        $this->seedQuizQuestions($domains, $user);
        $this->seedExpertiseRankTiers();
        $this->seedMandatoryQuizDomains($plantTypes, $domains);
    }

    private function libraryUser(): User
    {
        return User::query()->firstOrCreate(
            ['email' => 'library.admin@example.test'],
            [
                'username' => 'library_admin',
                'first_name' => 'Library',
                'last_name' => 'Admin',
                'password' => bcrypt(Str::random(32)),
                'role' => 'admin',
                'status' => 'active',
                'is_verified' => true,
                'verified_at' => now(),
            ]
        );
    }

    private function seedCategories(): array
    {
        $records = [
            [
                'title' => 'Operating Procedures',
                'slug' => 'operating-procedures',
                'sort_order' => 10,
                'children' => [
                    ['title' => 'Startup and Shutdown', 'slug' => 'startup-and-shutdown', 'sort_order' => 11],
                    ['title' => 'Troubleshooting Guides', 'slug' => 'troubleshooting-guides', 'sort_order' => 12],
                ],
            ],
            [
                'title' => 'Process Safety',
                'slug' => 'process-safety',
                'sort_order' => 20,
                'children' => [
                    ['title' => 'Hazard Reviews', 'slug' => 'hazard-reviews', 'sort_order' => 21],
                ],
            ],
            [
                'title' => 'Equipment Reliability',
                'slug' => 'equipment-reliability',
                'sort_order' => 30,
                'children' => [],
            ],
        ];

        $categories = [];

        foreach ($records as $record) {
            $parent = LibraryCategory::query()->firstOrCreate(
                ['slug' => $record['slug']],
                [
                    'title' => $record['title'],
                    'parent_id' => null,
                    'sort_order' => $record['sort_order'],
                ]
            );

            $categories[] = $parent;

            foreach ($record['children'] as $child) {
                $categories[] = LibraryCategory::query()->firstOrCreate(
                    ['slug' => $child['slug']],
                    [
                        'title' => $child['title'],
                        'parent_id' => $parent->id,
                        'sort_order' => $child['sort_order'],
                    ]
                );
            }
        }

        return $categories;
    }

    private function seedAccessRules(User $user): void
    {
        $rules = [
            [
                'partner_tier' => 'gold',
                'can_download' => false,
                'can_copy_paste' => false,
                'requires_watermark' => true,
                'max_downloads_per_month' => 5,
            ],
            [
                'partner_tier' => 'diamond',
                'can_download' => true,
                'can_copy_paste' => false,
                'requires_watermark' => true,
                'max_downloads_per_month' => 20,
            ],
            [
                'partner_tier' => 'platinum',
                'can_download' => true,
                'can_copy_paste' => true,
                'requires_watermark' => false,
                'max_downloads_per_month' => null,
            ],
        ];

        foreach ($rules as $rule) {
            LibraryAccessRule::query()->firstOrCreate(
                ['partner_tier' => $rule['partner_tier']],
                [
                    'can_view' => true,
                    'can_download' => $rule['can_download'],
                    'can_copy_paste' => $rule['can_copy_paste'],
                    'requires_watermark' => $rule['requires_watermark'],
                    'max_downloads_per_month' => $rule['max_downloads_per_month'],
                    'notes' => Str::headline($rule['partner_tier']).' partner library access baseline.',
                    'updated_by' => $user->id,
                ]
            );
        }
    }

    private function seedKnowledgeDomains($plantTypes, User $user): array
    {
        $records = [
            [
                'name' => 'Process Safety Fundamentals',
                'slug' => 'process-safety-fundamentals',
                'description' => 'Core safety concepts for technical library readers.',
                'icon' => 'shield-check',
                'sort_order' => 10,
            ],
            [
                'name' => 'Operations Troubleshooting',
                'slug' => 'operations-troubleshooting',
                'description' => 'Operational diagnosis and response patterns.',
                'icon' => 'activity',
                'sort_order' => 20,
            ],
            [
                'name' => 'Equipment Reliability',
                'slug' => 'equipment-reliability',
                'description' => 'Reliability and maintenance practices for critical equipment.',
                'icon' => 'settings',
                'sort_order' => 30,
            ],
        ];

        $domains = [];

        foreach ($records as $index => $record) {
            $plantType = $plantTypes->values()->get($index % $plantTypes->count());

            $domains[] = KnowledgeDomain::query()->firstOrCreate(
                ['slug' => $record['slug']],
                [
                    'name' => $record['name'],
                    'description' => $record['description'],
                    'status' => 'active',
                    'created_by' => $user->id,
                    'plant_type_id' => $plantType?->id,
                    'icon' => $record['icon'],
                    'total_question_count' => 2,
                    'is_active' => true,
                    'sort_order' => $record['sort_order'],
                ]
            );
        }

        return $domains;
    }

    private function seedLibraryItems(array $categories, array $domains, User $user): void
    {
        foreach ($domains as $index => $domain) {
            $category = $categories[$index % count($categories)];

            LibraryItem::query()->firstOrCreate(
                ['slug' => $domain->slug.'-field-guide'],
                [
                    'category_id' => $category->id,
                    'user_id' => $user->id,
                    'title' => $domain->name.' Field Guide',
                    'summary' => 'Demo technical guide for visual library checks.',
                    'content' => 'Approved demo content for browsing, search, and quiz context checks.',
                    'plant_type_id' => $domain->plant_type_id,
                    'author' => 'Charley Technical Team',
                    'source' => 'Internal demo dataset',
                    'published_year' => now()->year,
                    'access_level' => 'professional_only',
                    'download_allowed' => false,
                    'copy_paste_disabled' => true,
                    'download_count' => 0,
                    'status' => 'published',
                    'is_ai_trainable' => true,
                    'content_type' => 'article',
                    'item_type' => 'handbook',
                    'view_count' => 0,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'year' => now()->year,
                    'file_media_id' => null,
                ]
            );
        }
    }

    private function seedQuizQuestions(array $domains, User $user): void
    {
        foreach ($domains as $domain) {
            $quizId = $this->quizIdForDomain($domain, $user);

            $question = QuizQuestion::query()->firstOrCreate(
                [
                    'knowledge_domain_id' => $domain->id,
                    'question_text' => 'What is the first review step for '.$domain->name.'?',
                ],
                [
                    'quiz_id' => $quizId,
                    'question_type' => 'single_choice',
                    'options' => ['Confirm source approval', 'Skip document control', 'Download unrestricted files'],
                    'correct_answer' => ['Confirm source approval'],
                    'points' => 1,
                    'explanation' => 'Approved source review keeps library and AI training data trustworthy.',
                    'sort_order' => 1,
                    'question_image_media_id' => null,
                    'difficulty_level' => 'medium',
                    'status' => 'active',
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]
            );

            foreach ([
                ['choice_text' => 'Confirm source approval', 'is_correct' => true, 'sort_order' => 1],
                ['choice_text' => 'Skip document control', 'is_correct' => false, 'sort_order' => 2],
                ['choice_text' => 'Download unrestricted files', 'is_correct' => false, 'sort_order' => 3],
            ] as $choice) {
                QuizQuestionChoice::query()->firstOrCreate(
                    [
                        'question_id' => $question->id,
                        'choice_text' => $choice['choice_text'],
                    ],
                    [
                        'is_correct' => $choice['is_correct'],
                        'explanation' => $choice['is_correct']
                            ? 'Correct: approval is required before public display or AI use.'
                            : 'Incorrect: the library workflow must preserve governance controls.',
                        'sort_order' => $choice['sort_order'],
                    ]
                );
            }
        }
    }

    private function quizIdForDomain(KnowledgeDomain $domain, User $user): ?int
    {
        if (! Schema::hasTable('quizzes')) {
            return null;
        }

        $quiz = new class extends Model
        {
            protected $table = 'quizzes';

            protected $fillable = [
                'knowledge_domain_id',
                'title',
                'slug',
                'description',
                'time_limit_minutes',
                'max_attempts_per_user',
                'status',
                'created_by',
            ];
        };

        return $quiz->newQuery()->firstOrCreate(
            ['slug' => $domain->slug.'-baseline-check'],
            [
                'knowledge_domain_id' => $domain->id,
                'title' => $domain->name.' Baseline Check',
                'description' => 'Demo quiz for library UI validation.',
                'time_limit_minutes' => 20,
                'max_attempts_per_user' => 3,
                'status' => 'published',
                'created_by' => $user->id,
            ]
        )->id;
    }

    private function seedExpertiseRankTiers(): void
    {
        $records = [
            [
                'name' => 'Unverified user',
                'slug' => 'unverified-user',
                'min_years_experience' => null,
                'default_cap_percentage' => 0,
                'rank_order' => 0,
                'required_quiz_count' => 0,
                'required_mandatory_quiz_count' => 0,
            ],
            [
                'name' => 'Industry Professional',
                'slug' => 'industry-professional',
                'min_years_experience' => 0,
                'default_cap_percentage' => 30,
                'rank_order' => 10,
                'required_quiz_count' => 10,
                'required_mandatory_quiz_count' => 3,
            ],
            [
                'name' => 'Experienced Professional',
                'slug' => 'experienced-professional',
                'min_years_experience' => 8,
                'default_cap_percentage' => 50,
                'rank_order' => 20,
                'required_quiz_count' => 10,
                'required_mandatory_quiz_count' => 3,
            ],
            [
                'name' => 'Senior Industry Expert',
                'slug' => 'senior-industry-expert',
                'min_years_experience' => 15,
                'default_cap_percentage' => 70,
                'rank_order' => 30,
                'required_quiz_count' => 10,
                'required_mandatory_quiz_count' => 3,
            ],
        ];

        $baselineSlugs = collect($records)->pluck('slug')->all();

        ExpertiseRankTier::query()
            ->whereNotIn('slug', $baselineSlugs)
            ->get()
            ->each(function (ExpertiseRankTier $tier): void {
                $tier->forceFill([
                    'rank_order' => 10000 + $tier->id,
                    'status' => 'deleted',
                    'is_active' => false,
                ])->save();
            });

        foreach ($records as $tier) {
            $baseline = [
                'name' => $tier['name'],
                'min_years_experience' => $tier['min_years_experience'],
                'default_cap_percentage' => $tier['default_cap_percentage'],
                'rank_order' => $tier['rank_order'],
                'required_quiz_count' => $tier['required_quiz_count'],
                'required_mandatory_quiz_count' => $tier['required_mandatory_quiz_count'],
                'status' => 'active',
                'is_active' => true,
            ];

            $record = ExpertiseRankTier::query()->firstOrCreate(
                ['slug' => $tier['slug']],
                $baseline
            );

            $record->fill([
                'name' => $tier['name'],
                'min_years_experience' => $tier['min_years_experience'],
                'default_cap_percentage' => $tier['default_cap_percentage'],
                'rank_order' => $tier['rank_order'],
                'required_quiz_count' => $tier['required_quiz_count'],
                'required_mandatory_quiz_count' => $tier['required_mandatory_quiz_count'],
                'status' => 'active',
                'is_active' => true,
            ])->save();
        }
    }

    private function seedMandatoryQuizDomains($plantTypes, array $domains): void
    {
        foreach ($plantTypes as $index => $plantType) {
            $domain = $domains[$index % count($domains)];

            MandatoryQuizDomain::query()->firstOrCreate(
                [
                    'plant_type_id' => $plantType->id,
                    'knowledge_domain_id' => $domain->id,
                ],
                ['is_active' => true]
            );
        }
    }
}
