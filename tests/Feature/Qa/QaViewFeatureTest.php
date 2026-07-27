<?php

namespace Tests\Feature\Qa;

use App\Models\QaModerationRule;
use App\Models\QaModerationWarning;
use App\Models\QaUserWarningSummary;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QaViewFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('qa_user_warning_summaries');
        Schema::dropIfExists('qa_moderation_warnings');
        Schema::dropIfExists('qa_moderation_rules');
        Schema::dropIfExists('answers');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_admin_moderation_rules_route_renders(): void
    {
        $admin = $this->user('admin@example.test', 'admin');
        QaModerationRule::create([
            'name' => 'Blocked keywords',
            'rule_type' => 'keyword',
            'target_type' => 'both',
            'config' => ['keywords' => ['bypass']],
            'severity' => 'high',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.qa.moderation-rules'));

        $response->assertOk();
        $response->assertSee('Blocked keywords');
        $response->assertSee('keyword');
    }

    public function test_admin_warning_review_route_renders_and_confirm_review_updates_summary(): void
    {
        $admin = $this->user('admin@example.test', 'admin');
        $user = $this->user('member@example.test');
        $question = Question::create([
            'user_id' => $user->id,
            'title' => 'Flagged process question',
            'body' => 'Needs moderation review.',
            'is_anonymous' => false,
            'status' => 'pending',
        ]);
        $warning = QaModerationWarning::create([
            'user_id' => $user->id,
            'warnable_type' => 'question',
            'warnable_id' => $question->id,
            'source' => 'system_rule',
            'severity' => 'high',
            'reason' => 'Matched blocked keyword: bypass.',
            'evidence' => ['keyword' => 'bypass'],
            'status' => 'pending_review',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.qa.warnings'));

        $response->assertOk();
        $response->assertSee('Matched blocked keyword: bypass.');
        $response->assertSee('Flagged process question');

        $reviewResponse = $this->actingAs($admin)->post(route('admin.dashboard.qa.warnings.review', [$warning, 'confirmed']));
        $reviewResponse->assertRedirect();

        $summary = QaUserWarningSummary::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(1, $summary->confirmed_warning_count);
        $this->assertFalse($summary->is_frozen);
    }

    private function user(string $email, string $role = 'professional'): User
    {
        return User::create([
            'username' => str_replace(['@example.test', '.'], ['', '-'], $email),
            'first_name' => 'QA',
            'last_name' => 'User',
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => $role,
            'status' => 'active',
            'is_verified' => true,
            'login_attempts' => 0,
            'mfa_enabled' => false,
        ]);
    }

    private function createTestSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('username')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role');
            $table->string('status')->default('active');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('verification_expires_at')->nullable();
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
            $table->smallInteger('login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->boolean('mfa_enabled')->default(false);
            $table->string('mfa_secret')->nullable();
            $table->json('mfa_recovery_codes')->nullable();
            $table->timestamp('self_frozen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('posted_by_admin_id')->nullable();
            $table->foreignId('on_behalf_of_partner_id')->nullable();
            $table->foreignId('weekly_theme_id')->nullable();
            $table->foreignId('plant_type_id')->nullable();
            $table->string('title');
            $table->text('body');
            $table->boolean('is_anonymous')->default(false);
            $table->string('status')->default('pending');
            $table->json('attachment_media_ids')->nullable();
            $table->timestamps();
        });

        Schema::create('answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('question_id');
            $table->foreignId('user_id');
            $table->boolean('is_anonymous')->default(false);
            $table->text('body');
            $table->boolean('is_admin_featured')->default(false);
            $table->string('confidence_level')->nullable();
            $table->integer('admin_rank_order')->nullable();
            $table->json('attachment_media_ids')->nullable();
            $table->timestamps();
        });

        Schema::create('qa_moderation_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('rule_type');
            $table->string('target_type');
            $table->json('config');
            $table->string('severity');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('qa_moderation_warnings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('warnable_type');
            $table->unsignedBigInteger('warnable_id');
            $table->string('source');
            $table->string('severity');
            $table->text('reason');
            $table->json('evidence')->nullable();
            $table->string('status')->default('pending_review');
            $table->foreignId('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('qa_user_warning_summaries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->integer('confirmed_warning_count')->default(0);
            $table->timestamp('last_warning_at')->nullable();
            $table->boolean('is_frozen')->default(false);
            $table->timestamp('frozen_at')->nullable();
            $table->text('frozen_reason')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
}
