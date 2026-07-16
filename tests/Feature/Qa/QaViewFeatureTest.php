<?php

namespace Tests\Feature\Qa;

use App\Models\Answer;
use App\Models\MonthlyLeaderboardSnapshot;
use App\Models\PlantType;
use App\Models\PointTransaction;
use App\Models\QaModerationRule;
use App\Models\QaModerationWarning;
use App\Models\QaUserWarningSummary;
use App\Models\Question;
use App\Models\User;
use App\Models\UserReputation;
use App\Models\WeeklyTheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QaViewFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_qa_views_render_without_blade_errors(): void
    {
        $question = Question::factory()->published()->create([
            'title' => 'How do I troubleshoot condenser fouling?',
            'is_anonymous' => false,
        ]);
        Answer::factory()->featured()->create(['question_id' => $question->id]);

        $this->get(route('qa.community.index'))
            ->assertOk()
            ->assertSee('content-wrapper', false)
            ->assertSee('<section class="content-header">', false)
            ->assertSee('<section class="content">', false)
            ->assertDontSee('ddiv', false)
            ->assertDontSee('dh1', false)
            ->assertDontSee('fas ', false)
            ->assertDontSee('far ', false)
            ->assertSee('bi bi-plus-circle', false)
            ->assertSee('Q&A Community');

        $this->get(route('qa.community.ask'))
            ->assertOk()
            ->assertSee('content-wrapper', false)
            ->assertSee('<section class="content">', false)
            ->assertDontSee('ddiv', false)
            ->assertDontSee('dh1', false)
            ->assertDontSee('fas ', false)
            ->assertDontSee('far ', false)
            ->assertSee('<h1 class="m-0">Ask Question</h1>', false)
            ->assertSee('bi bi-send', false)
            ->assertSee('Ask Question');

        $this->get(route('qa.community.show', Str::slug($question->title).'-'.$question->id))
            ->assertOk()
            ->assertSee('content-wrapper', false)
            ->assertSee('<section class="content">', false)
            ->assertDontSee('ddiv', false)
            ->assertDontSee('dh1', false)
            ->assertDontSee('fas ', false)
            ->assertDontSee('far ', false)
            ->assertSee('bi bi-arrow-left', false)
            ->assertSee('bi bi-reply', false)
            ->assertSee('Question Detail')
            ->assertSee($question->title);
    }

    public function test_admin_qa_views_render_without_blade_or_sidebar_errors(): void
    {
        $admin = User::factory()->admin()->create();
        $question = Question::factory()->create([
            'title' => 'Why is pump vibration trending upward?',
            'status' => 'flagged',
            'is_anonymous' => true,
        ]);
        Answer::factory()->featured()->create(['question_id' => $question->id]);

        $routes = [
            'admin.dashboard.qa.index' => 'QA',
            'admin.dashboard.qa.questions' => 'Question Review Queue',
            'admin.dashboard.qa.answers' => 'Answer Moderation',
            'admin.dashboard.qa.weekly-themes' => 'Create Weekly Theme',
            'admin.dashboard.qa.reputation' => 'Manual Reputation Adjustment',
            'admin.dashboard.qa.leaderboard' => 'Leaderboard Controls',
            'admin.dashboard.qa.leaderboard-report' => 'Monthly Leaderboard Report',
            'admin.dashboard.qa.flagged' => $question->title,
            'admin.dashboard.qa.moderation-rules' => 'Rule Pre-check Configuration',
            'admin.dashboard.qa.warnings' => 'Warning Review Queue',
        ];

        foreach ($routes as $route => $expectedText) {
            $this->actingAs($admin)
                ->get(route($route))
                ->assertOk()
                ->assertSee($expectedText, false)
                ->assertSee('<div class="app-content-header">', false)
                ->assertSee('<div class="app-content">', false)
                ->assertSee('<div class="container-fluid">', false)
                ->assertDontSee('ddiv', false)
                ->assertDontSee('dh1', false)
                ->assertDontSee('fas ', false)
                ->assertDontSee('far ', false)
                ->assertSee('bi bi-question-circle', false)
                ->assertSee('bi bi-circle', false)
                ->assertDontSee('admin.dashboard.qa.questions', false)
                ->assertSee('Flagged Content', false);
        }

        $this->actingAs($admin)
            ->get(route('admin.dashboard.qa.index'))
            ->assertOk()
            ->assertSee('info-box', false)
            ->assertSee('info-box-icon', false)
            ->assertSee('info-box-content', false)
            ->assertSee('info-box-text', false)
            ->assertSee('info-box-number', false)
            ->assertDontSee('small-box', false)
            ->assertSee(route('admin.dashboard.qa.questions.show', $question->id), false)
            ->assertSee('Quick Change Status', false)
            ->assertSee('Answers', false)
            ->assertSee('name="status"', false)
            ->assertSee(route('admin.dashboard.qa.questions.demo-status', $question->id), false)
            ->assertDontSee('<th>Theme</th>', false)
            ->assertDontSee('Dashboard Theme Assignment');

        $this->actingAs($admin)
            ->get(route('admin.dashboard.qa.questions.show', $question->id))
            ->assertOk()
            ->assertSee('QA Question Detail')
            ->assertSee('Plant Type:')
            ->assertSee('Author Profile')
            ->assertSee('Role')
            ->assertSee('Email')
            ->assertSee('Question Status')
            ->assertSee('btn-check', false)
            ->assertSee('question_status_active', false)
            ->assertSee('question_status_draft', false)
            ->assertSee('question_status_unactive', false)
            ->assertDontSee('id="question_status" name="status" class="form-select"', false)
            ->assertSee('Send Warning')
            ->assertSee('Status Warning History')
            ->assertSee('active')
            ->assertSee('draft')
            ->assertSee('unactive')
            ->assertSee('Approval Controls', false);
    }

    public function test_admin_qa_dashboard_filters_preserve_values_and_filter_demo_questions(): void
    {
        $admin = User::factory()->admin()->create();
        $dateFrom = now()->subDays(3)->toDateString();
        $dateTo = now()->subDay()->toDateString();

        $this->actingAs($admin)
            ->get(route('admin.dashboard.qa.index', [
                'status' => 'flagged',
                'plant_type_id' => 3,
                'weekly_theme_id' => 9302,
                'keyword' => 'exchanger',
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]))
            ->assertOk()
            ->assertSee('Question Review Filters')
            ->assertSee('value="exchanger"', false)
            ->assertSee('value="'.$dateFrom.'"', false)
            ->assertSee('value="'.$dateTo.'"', false)
            ->assertSee('<option value="flagged" selected>Flagged</option>', false)
            ->assertSee('<option value="3" selected>Utilities and steam</option>', false)
            ->assertSee('<option value="9302" selected>Energy optimization quick wins</option>', false)
            ->assertSee('Demo: exchanger fouling trend needs review')
            ->assertDontSee('Demo: compressor vibration after startup')
            ->assertSee(route('admin.dashboard.qa.questions.show', 9103), false)
            ->assertSee('View', false)
            ->assertSee('Quick Change Status', false)
            ->assertSee('Answers', false)
            ->assertDontSee('<th>Theme</th>', false);
    }

    public function test_weekly_theme_creation_and_assignment_ui_is_clear(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard.qa.weekly-themes'))
            ->assertOk()
            ->assertSee('Create Weekly Theme')
            ->assertSee('name="title"', false)
            ->assertSee('name="description"', false)
            ->assertSee('name="week_start_date"', false)
            ->assertSee('name="week_end_date"', false)
            ->assertSee('name="status"', false)
            ->assertSee('Theme Calendar And Assignments')
            ->assertSee('Assign Question To Theme')
            ->assertSee('assigned')
            ->assertSee('Remove')
            ->assertSee(route('admin.dashboard.qa.weekly-themes.assign-question', 9301), false)
            ->assertSee(route('admin.dashboard.qa.weekly-themes.remove-question', [9301, 9101]), false);

        $this->actingAs($admin)
            ->get(route('admin.dashboard.qa.questions.show', 9101))
            ->assertOk()
            ->assertSee('Theme Assignment')
            ->assertSee('Current weekly theme')
            ->assertSee('Assign Weekly Theme')
            ->assertSee(route('admin.dashboard.qa.weekly-themes.assign-question', 9301), false);

        $this->actingAs($admin)
            ->get(route('admin.dashboard.qa.index'))
            ->assertOk()
            ->assertDontSee('Dashboard Theme Assignment')
            ->assertDontSee('id="dashboard_question_id"', false);
    }

    public function test_admin_reputation_workflow_shows_rows_ledger_and_adjustment_controls(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard.qa.reputation', [
                'source_type' => 'manual_adjustment',
                'keyword' => 'Carlos',
            ]))
            ->assertOk()
            ->assertSee('Reputation Filters')
            ->assertSee('value="Carlos"', false)
            ->assertSee('<option value="manual_adjustment" selected>Manual Adjustment</option>', false)
            ->assertSee('User Reputation Rows')
            ->assertSee('Total Points')
            ->assertSee('Current Star Rank')
            ->assertSee('Recent Point Transactions')
            ->assertSee('Manual Reputation Adjustment')
            ->assertSee('name="reason"', false)
            ->assertSee('Performed by current admin')
            ->assertSee('points_direction_positive', false)
            ->assertSee('points_direction_negative', false)
            ->assertSee('Carlos Rivera')
            ->assertSee('duplicate answer adjustment')
            ->assertDontSee('ddiv', false)
            ->assertDontSee('dh1', false)
            ->assertDontSee('fas ', false)
            ->assertDontSee('far ', false);
    }

    public function test_qa_routes_prefer_real_data_before_demo_fallbacks(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->create([
            'first_name' => 'Rina',
            'last_name' => 'Patel',
            'email' => 'rina.real@example.test',
        ]);
        $plant = PlantType::create([
            'name' => 'Methanol plant',
            'slug' => 'methanol-plant',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $theme = WeeklyTheme::factory()->active()->create([
            'title' => 'Real Reliability Week',
            'created_by_admin_id' => $admin->id,
        ]);
        $published = Question::factory()->published()->create([
            'user_id' => $author->id,
            'plant_type_id' => $plant->id,
            'weekly_theme_id' => $theme->id,
            'title' => 'Real compressor seal leak',
            'body' => 'Real public question body for route binding.',
            'is_anonymous' => false,
        ]);
        $flagged = Question::factory()->create([
            'user_id' => $author->id,
            'plant_type_id' => $plant->id,
            'weekly_theme_id' => $theme->id,
            'title' => 'Real flagged corrosion case',
            'status' => 'flagged',
        ]);
        $hidden = Question::factory()->create([
            'user_id' => $author->id,
            'plant_type_id' => $plant->id,
            'weekly_theme_id' => $theme->id,
            'title' => 'Real hidden analyzer case',
            'status' => 'hidden',
        ]);
        Answer::factory()->featured()->create([
            'question_id' => $published->id,
            'user_id' => $author->id,
            'body' => 'Real answer: inspect the seal flush plan before restart.',
        ]);
        Answer::factory()->create([
            'question_id' => $published->id,
            'user_id' => $author->id,
            'body' => 'Real answer: verify the seal pot pressure trend.',
        ]);
        UserReputation::factory()->create([
            'user_id' => $author->id,
            'total_points' => 777,
            'current_star_rank' => 3,
        ]);
        PointTransaction::factory()->manualAdjustment()->create([
            'user_id' => $author->id,
            'points' => -15,
            'reason' => 'Real manual correction for duplicate answer.',
            'performed_by' => $admin->id,
        ]);
        MonthlyLeaderboardSnapshot::factory()->create([
            'user_id' => $author->id,
            'year_month' => now()->format('Y-m'),
            'rank_position' => 1,
            'total_points_in_month' => 777,
        ]);

        $this->get(route('qa.community.index', ['plant_type_id' => $plant->id, 'weekly_theme_id' => $theme->id]))
            ->assertOk()
            ->assertSee('Real compressor seal leak')
            ->assertSee('Methanol plant')
            ->assertSee('Real Reliability Week')
            ->assertDontSee('Demo question:', false);

        $this->get(route('qa.community.show', Str::slug($published->title).'-'.$published->id))
            ->assertOk()
            ->assertSee('Real compressor seal leak')
            ->assertSee('Real answer: inspect the seal flush plan before restart.')
            ->assertSee('Methanol plant');

        $this->actingAs($admin)
            ->get(route('admin.dashboard.qa.index', ['keyword' => 'seal leak']))
            ->assertOk()
            ->assertSee('Real compressor seal leak')
            ->assertSee('Quick Change Status', false)
            ->assertSee('>2</span>', false)
            ->assertDontSee('<th>Theme</th>', false)
            ->assertDontSee('Demo: compressor vibration after startup');

        $this->actingAs($admin)
            ->get(route('admin.dashboard.qa.answers', ['question_id' => $published->id]))
            ->assertOk()
            ->assertSee('Real answer: inspect the seal flush plan before restart.')
            ->assertDontSee('Demo answer:', false);

        $this->actingAs($admin)
            ->get(route('admin.dashboard.qa.weekly-themes'))
            ->assertOk()
            ->assertSee('Real Reliability Week')
            ->assertSee('Real compressor seal leak');

        $this->actingAs($admin)
            ->get(route('admin.dashboard.qa.reputation', ['keyword' => 'Rina']))
            ->assertOk()
            ->assertSee('Rina Patel')
            ->assertSee('777')
            ->assertSee('Real manual correction for duplicate answer.');

        $this->actingAs($admin)
            ->get(route('admin.dashboard.qa.leaderboard-report', ['year_month' => now()->format('Y-m')]))
            ->assertOk()
            ->assertSee('Rina Patel')
            ->assertSee('777');

        $this->actingAs($admin)
            ->get(route('admin.dashboard.qa.flagged'))
            ->assertOk()
            ->assertSee($flagged->title)
            ->assertDontSee($hidden->title);

        $this->actingAs($admin)
            ->get(route('admin.dashboard.qa.flagged', ['flag_status' => 'hidden']))
            ->assertOk()
            ->assertSee($hidden->title)
            ->assertDontSee($flagged->title);
    }

    public function test_admin_moderation_rules_and_warning_review_views_render(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->create(['first_name' => 'Nora', 'last_name' => 'Le']);
        $question = Question::factory()->create([
            'user_id' => $author->id,
            'title' => 'Moderation context question',
            'status' => 'flagged',
        ]);
        $rule = QaModerationRule::create([
            'name' => 'Real keyword guard',
            'rule_type' => 'keyword',
            'target_type' => 'both',
            'config' => ['keywords' => ['bypass']],
            'severity' => 'high',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $warning = QaModerationWarning::create([
            'user_id' => $author->id,
            'warnable_type' => 'question',
            'warnable_id' => $question->id,
            'source' => 'system_rule',
            'severity' => 'high',
            'reason' => 'Matched blocked keyword: bypass.',
            'evidence' => ['rule_id' => $rule->id, 'keyword' => 'bypass'],
            'status' => 'pending_review',
        ]);
        QaUserWarningSummary::create([
            'user_id' => $author->id,
            'confirmed_warning_count' => 2,
            'last_warning_at' => now(),
            'is_frozen' => false,
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard.qa.moderation-rules'))
            ->assertOk()
            ->assertSee('QA Moderation Rules')
            ->assertSee('Rule Pre-check Configuration')
            ->assertSee('keyword')
            ->assertSee('max_links')
            ->assertSee('min_length')
            ->assertSee('regex')
            ->assertSee('attachment_type')
            ->assertSee('custom')
            ->assertSee('Runs before AI')
            ->assertSee('Real keyword guard')
            ->assertSee(route('admin.dashboard.qa.moderation-rules.toggle', $rule->id), false);

        $this->actingAs($admin)
            ->get(route('admin.dashboard.qa.warnings'))
            ->assertOk()
            ->assertSee('QA Warning Review')
            ->assertSee('Warning Review Queue')
            ->assertSee('system_rule')
            ->assertSee('Matched blocked keyword: bypass.')
            ->assertSee('Moderation context question')
            ->assertSee('2/3 confirmed')
            ->assertSee(route('admin.dashboard.qa.warnings.review', [$warning->id, 'confirmed']), false)
            ->assertSee('Confirmed warnings drive freeze')
            ->assertDontSee('ddiv', false)
            ->assertDontSee('dh1', false)
            ->assertDontSee('fas ', false)
            ->assertDontSee('far ', false);
    }

    public function test_admin_sidebar_has_single_source_of_truth(): void
    {
        $canonicalSidebar = file_get_contents(resource_path('views/components/sidebar.blade.php'));
        $legacySidebar = trim(file_get_contents(resource_path('views/admin/layouts/sidebar.blade.php')));

        $this->assertSame("@include('components.sidebar')", $legacySidebar);
        $this->assertStringContainsString("route('admin.dashboard.qa.index')", $canonicalSidebar);
        $this->assertStringContainsString("route('admin.dashboard.qa.answers')", $canonicalSidebar);
        $this->assertStringContainsString("route('admin.dashboard.qa.moderation-rules')", $canonicalSidebar);
        $this->assertStringContainsString("route('admin.dashboard.qa.warnings')", $canonicalSidebar);
        $this->assertStringNotContainsString("route('admin.dashboard.qa.questions')", $canonicalSidebar);
    }

    public function test_qa_views_include_demo_content_when_tables_are_empty(): void
    {
        $admin = User::factory()->admin()->create();

        $this->get(route('qa.community.index'))
            ->assertOk()
            ->assertSee('Demo question:', false)
            ->assertSee('Compressor vibration after turnaround')
            ->assertSee('Monthly Leaderboard');

        $this->actingAs($admin)
            ->get(route('admin.dashboard.qa.index'))
            ->assertOk()
            ->assertSee('Demo: compressor vibration after startup')
            ->assertSee('Aisha Tran');

        $this->actingAs($admin)
            ->get(route('admin.dashboard.qa.answers'))
            ->assertOk()
            ->assertSee('Demo answer:', false);

        $this->actingAs($admin)
            ->get(route('admin.dashboard.qa.weekly-themes'))
            ->assertOk()
            ->assertSee('Rotating equipment reliability')
            ->assertSee('Energy optimization quick wins');

        $this->actingAs($admin)
            ->get(route('admin.dashboard.qa.flagged'))
            ->assertOk()
            ->assertSee('Demo: exchanger fouling trend needs review');
    }
}
