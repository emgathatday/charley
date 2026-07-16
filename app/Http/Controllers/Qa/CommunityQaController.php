<?php

namespace App\Http\Controllers\Qa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CommunityQaController extends Controller
{
    public function index(Request $request)
    {
        return view('qa.index', [
            ...$this->filters(),
            'questions' => $this->questions($request),
            'leaders' => $this->leaderboard(),
            'filters' => $request->only(['plant_type_id', 'weekly_theme_id']),
        ]);
    }

    public function ask()
    {
        return view('qa.ask', [
            ...$this->filters(),
            'leaders' => $this->leaderboard(),
        ]);
    }

    public function show(string $slug)
    {
        $questionId = (int) Str::afterLast($slug, '-');
        $question = null;
        $answers = collect();

        if ($questionId > 0 && Schema::hasTable('questions')) {
            $question = DB::table('questions')
                ->leftJoin('plant_types', 'plant_types.id', '=', 'questions.plant_type_id')
                ->leftJoin('weekly_themes', 'weekly_themes.id', '=', 'questions.weekly_theme_id')
                ->select('questions.*', 'plant_types.name as plant_name', 'weekly_themes.title as theme_title')
                ->where('questions.id', $questionId)
                ->where('questions.status', 'published')
                ->first();
        }

        if (! $question) {
            $demoQuestion = collect($this->demoQuestions())->firstWhere('slug', $slug);
            abort_if(! $demoQuestion, 404);

            return view('qa.show', [
                ...$this->filters(),
                'question' => $demoQuestion,
                'answers' => collect($this->demoAnswers()),
                'leaders' => $this->leaderboard(),
            ]);
        }

        if (Schema::hasTable('answers')) {
            $answers = DB::table('answers')
                ->leftJoin('users', 'users.id', '=', 'answers.user_id')
                ->select('answers.*', 'users.username as user_username', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'users.email as user_email')
                ->where('question_id', $question->id)
                ->orderByDesc('is_admin_featured')
                ->orderBy('admin_rank_order')
                ->oldest('answers.created_at')
                ->get();
        }

        return view('qa.show', [
            ...$this->filters(),
            'question' => [
                'id' => $question->id,
                'title' => $question->title,
                'body' => $question->body,
                'plant' => $question->plant_name ?: 'General',
                'theme' => $question->theme_title ?: 'Open discussion',
                'anonymous' => (bool) $question->is_anonymous,
                'domains' => $this->domains($question->id),
                'media' => $this->media(json_decode($question->attachment_media_ids ?: '[]', true)),
            ],
            'answers' => $answers->map(fn ($answer): array => [
                'author' => $answer->is_anonymous ? 'Anonymous member' : $this->userDisplayName($answer, $answer->user_id),
                'featured' => (bool) $answer->is_admin_featured,
                'confidence' => $answer->confidence_level ?: 'unrated',
                'body' => $answer->body,
                'media' => $this->media(json_decode($answer->attachment_media_ids ?: '[]', true)),
            ]),
            'leaders' => $this->leaderboard(),
        ]);
    }

    private function filters(): array
    {
        $plantTypes = Schema::hasTable('plant_types')
            ? DB::table('plant_types')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()
            : collect();
        $weeklyThemes = Schema::hasTable('weekly_themes')
            ? DB::table('weekly_themes')->where('status', 'active')->orderByDesc('week_start_date')->get()
            : collect();
        $knowledgeDomains = Schema::hasTable('knowledge_domains')
            ? DB::table('knowledge_domains')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()
            : collect();

        return [
            'plantTypes' => $plantTypes->isNotEmpty() ? $plantTypes : collect($this->demoPlantTypes()),
            'weeklyThemes' => $weeklyThemes->isNotEmpty() ? $weeklyThemes : collect($this->demoWeeklyThemes()),
            'knowledgeDomains' => $knowledgeDomains->isNotEmpty() ? $knowledgeDomains : collect($this->demoKnowledgeDomains()),
        ];
    }

    private function questions(Request $request)
    {
        if (! Schema::hasTable('questions')) {
            return $this->filteredDemoQuestions($request);
        }

        $hasPublishedQuestions = DB::table('questions')->where('status', 'published')->exists();

        $questions = DB::table('questions')
            ->leftJoin('plant_types', 'plant_types.id', '=', 'questions.plant_type_id')
            ->leftJoin('weekly_themes', 'weekly_themes.id', '=', 'questions.weekly_theme_id')
            ->select('questions.*', 'plant_types.name as plant_name', 'weekly_themes.title as theme_title')
            ->where('questions.status', 'published')
            ->when($request->integer('plant_type_id'), fn ($query, $plantTypeId) => $query->where('questions.plant_type_id', $plantTypeId))
            ->when($request->integer('weekly_theme_id'), fn ($query, $weeklyThemeId) => $query->where('questions.weekly_theme_id', $weeklyThemeId))
            ->latest('questions.created_at')
            ->limit(20)
            ->get()
            ->map(fn ($question): array => [
                'id' => $question->id,
                'slug' => Str::slug($question->title).'-'.$question->id,
                'title' => $question->title,
                'body' => Str::limit($question->body, 180),
                'plant' => $question->plant_name ?: 'General',
                'theme' => $question->theme_title ?: 'Open discussion',
                'anonymous' => (bool) $question->is_anonymous,
                'answers' => Schema::hasTable('answers') ? DB::table('answers')->where('question_id', $question->id)->count() : 0,
                'domains' => $this->domains($question->id),
                'media' => $this->media(json_decode($question->attachment_media_ids ?: '[]', true)),
                'created_at' => $question->created_at,
            ]);

        return $questions->isNotEmpty() || $hasPublishedQuestions ? $questions : $this->filteredDemoQuestions($request);
    }

    private function domains(int $questionId): array
    {
        return Schema::hasTable('question_domain_links')
            ? DB::table('question_domain_links')
                ->join('knowledge_domains', 'knowledge_domains.id', '=', 'question_domain_links.knowledge_domain_id')
                ->where('question_domain_links.question_id', $questionId)
                ->orderBy('knowledge_domains.name')
                ->pluck('knowledge_domains.name')
                ->all()
            : [];
    }

    private function media(?array $ids): array
    {
        $ids = collect($ids ?? [])->filter()->unique()->values();

        if ($ids->isEmpty() || ! Schema::hasTable('media_files')) {
            return [];
        }

        return DB::table('media_files')
            ->whereIn('id', $ids)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($media): array => [
                'id' => $media->id,
                'name' => $media->original_name,
                'type' => $media->mime_type,
                'path' => $media->streaming_url ?: $media->path,
            ])
            ->all();
    }

    private function leaderboard(): array
    {
        if (! Schema::hasTable('monthly_leaderboard_snapshots')) {
            return $this->demoLeaders();
        }

        $leaders = DB::table('monthly_leaderboard_snapshots')
            ->leftJoin('users', 'users.id', '=', 'monthly_leaderboard_snapshots.user_id')
            ->select('monthly_leaderboard_snapshots.*', 'users.username as user_username', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'users.email as user_email')
            ->where('year_month', now()->format('Y-m'))
            ->orderBy('rank_position')
            ->limit(5)
            ->get()
            ->map(fn ($leader): array => [
                'name' => $this->userDisplayName($leader, $leader->user_id),
                'points' => $leader->total_points_in_month,
                'rank' => $leader->rank_position,
            ])
            ->all();

        if ($leaders) {
            return $leaders;
        }

        if (Schema::hasTable('user_reputation')) {
            $leaders = DB::table('user_reputation')
                ->leftJoin('users', 'users.id', '=', 'user_reputation.user_id')
                ->select('user_reputation.*', 'users.username as user_username', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'users.email as user_email')
                ->orderByDesc('user_reputation.total_points')
                ->limit(5)
                ->get()
                ->map(fn ($leader, $index): array => [
                    'name' => $this->userDisplayName($leader, $leader->user_id),
                    'points' => $leader->total_points,
                    'rank' => $index + 1,
                ])
                ->all();
        }

        return $leaders ?: $this->demoLeaders();
    }

    private function demoPlantTypes(): array
    {
        return [
            (object) ['id' => 101, 'name' => 'Ammonia plant'],
            (object) ['id' => 102, 'name' => 'Urea synthesis'],
            (object) ['id' => 103, 'name' => 'Utilities and steam'],
        ];
    }

    private function demoWeeklyThemes(): array
    {
        return [
            (object) ['id' => 201, 'title' => 'Rotating equipment reliability'],
            (object) ['id' => 202, 'title' => 'Process safety near misses'],
            (object) ['id' => 203, 'title' => 'Energy optimization'],
        ];
    }

    private function demoKnowledgeDomains(): array
    {
        return [
            (object) ['id' => 301, 'name' => 'Troubleshooting'],
            (object) ['id' => 302, 'name' => 'Maintenance'],
            (object) ['id' => 303, 'name' => 'Process control'],
        ];
    }

    private function demoQuestions(): array
    {
        return [
            ['id' => 9001, 'plant_type_id' => 101, 'weekly_theme_id' => 201, 'slug' => 'compressor-vibration-after-turnaround-9001', 'title' => 'Compressor vibration after turnaround', 'body' => 'Demo question: vibration rose after startup, with stable suction pressure but higher bearing temperature. What checks should the shift team prioritize?', 'plant' => 'Ammonia plant', 'theme' => 'Rotating equipment reliability', 'anonymous' => false, 'answers' => 4, 'domains' => ['Troubleshooting', 'Maintenance'], 'media' => [], 'created_at' => now()->subDays(2)],
            ['id' => 9002, 'plant_type_id' => 102, 'weekly_theme_id' => 203, 'slug' => 'steam-trap-losses-on-urea-unit-9002', 'title' => 'Steam trap losses on urea unit', 'body' => 'Demo question: several steam traps show continuous discharge during the morning round. How should we quantify the energy loss and prioritize repair?', 'plant' => 'Urea synthesis', 'theme' => 'Energy optimization', 'anonymous' => true, 'answers' => 2, 'domains' => ['Energy', 'Reliability'], 'media' => [], 'created_at' => now()->subDays(4)],
            ['id' => 9003, 'plant_type_id' => 103, 'weekly_theme_id' => null, 'slug' => 'cooling-water-fouling-indicators-9003', 'title' => 'Cooling water fouling indicators', 'body' => 'Demo question: exchanger approach temperature is drifting while flow appears unchanged. Which field readings help separate fouling from instrument error?', 'plant' => 'Utilities and steam', 'theme' => 'Open discussion', 'anonymous' => false, 'answers' => 3, 'domains' => ['Heat exchange', 'Operations'], 'media' => [], 'created_at' => now()->subWeek()],
        ];
    }

    private function filteredDemoQuestions(Request $request)
    {
        return collect($this->demoQuestions())
            ->when($request->integer('plant_type_id'), fn ($questions, $plantTypeId) => $questions->where('plant_type_id', $plantTypeId))
            ->when($request->integer('weekly_theme_id'), fn ($questions, $weeklyThemeId) => $questions->where('weekly_theme_id', $weeklyThemeId));
    }

    private function demoAnswers(): array
    {
        return [
            ['author' => 'Carlos Rivera', 'featured' => true, 'confidence' => 'high', 'body' => 'Trend bearing temperature with the vibration spectrum before increasing load.', 'media' => []],
            ['author' => 'Aisha Tran', 'featured' => false, 'confidence' => 'medium', 'body' => 'Compare the startup profile against the last clean run and verify lube oil differential pressure.', 'media' => []],
        ];
    }

    private function demoLeaders(): array
    {
        return [
            ['rank' => 1, 'name' => 'Aisha Tran', 'points' => 1480],
            ['rank' => 2, 'name' => 'Minh Nguyen', 'points' => 1265],
            ['rank' => 3, 'name' => 'Carlos Rivera', 'points' => 980],
            ['rank' => 4, 'name' => 'Priya Shah', 'points' => 845],
        ];
    }

    private function userDisplayName(object $row, ?int $userId = null): string
    {
        $fullName = trim(implode(' ', array_filter([
            $row->user_first_name ?? null,
            $row->user_last_name ?? null,
        ])));

        return $fullName
            ?: ($row->user_username ?? null)
            ?: ($row->user_email ?? null)
            ?: ($userId ? 'Member #'.$userId : 'Member');
    }
}
