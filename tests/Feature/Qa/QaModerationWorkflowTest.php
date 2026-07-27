<?php

namespace Tests\Feature\Qa;

use App\Models\Answer;
use App\Models\QaModerationWarning;
use App\Models\QaUserWarningSummary;
use App\Models\Question;
use App\Models\User;
use App\Services\Qa\AnswerModerationService;
use App\Services\Qa\QuestionWorkflowService;
use App\Services\Qa\WarningFreezeService;
use InvalidArgumentException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QaModerationWorkflowTest extends TestCase
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

    public function test_only_confirmed_warnings_increment_summary_and_third_confirmed_warning_freezes_user(): void
    {
        $admin = $this->user('admin@example.test', 'admin');
        $user = $this->user('member@example.test');
        $question = $this->question($user);
        $service = new WarningFreezeService;

        $pending = $this->warning($user, $question, 'pending_review');
        $service->rebuildSummary($user->id);
        $this->assertSame(0, QaUserWarningSummary::where('user_id', $user->id)->first()->confirmed_warning_count);

        $service->markReviewed($pending, 'safe', $admin->id);
        $this->assertSame(0, QaUserWarningSummary::where('user_id', $user->id)->first()->confirmed_warning_count);

        $dismissed = $this->warning($user, $question, 'pending_review');
        $service->markReviewed($dismissed, 'dismissed', $admin->id);
        $this->assertSame(0, QaUserWarningSummary::where('user_id', $user->id)->first()->confirmed_warning_count);

        foreach (range(1, 3) as $index) {
            $service->confirmWarning($this->warning($user, $question, 'pending_review'), $admin->id);
        }

        $summary = QaUserWarningSummary::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(3, $summary->confirmed_warning_count);
        $this->assertTrue($summary->is_frozen);
        $this->assertNotNull($summary->frozen_at);
        $this->assertSame('Reached 3 confirmed Q&A moderation warnings.', $summary->frozen_reason);
    }

    public function test_frozen_user_cannot_submit_question_or_answer(): void
    {
        $user = $this->user('frozen@example.test');
        $author = $this->user('author@example.test');
        $question = $this->question($author, 'published');

        QaUserWarningSummary::create([
            'user_id' => $user->id,
            'confirmed_warning_count' => 3,
            'last_warning_at' => now(),
            'is_frozen' => true,
            'frozen_at' => now(),
            'frozen_reason' => 'Reached 3 confirmed Q&A moderation warnings.',
            'updated_at' => now(),
        ]);

        try {
            (new QuestionWorkflowService)->createQuestion([
                'user_id' => $user->id,
                'title' => 'Can I submit while frozen?',
                'body' => 'This should be rejected by the Q&A freeze guard.',
            ]);
            $this->fail('Frozen user was able to submit a question.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('User is frozen from Q&A submissions', $exception->getMessage());
        }

        $this->assertDatabaseMissing('questions', ['title' => 'Can I submit while frozen?']);

        try {
            (new AnswerModerationService)->createAnswer($question, [
                'user_id' => $user->id,
                'body' => 'This answer should be rejected by the Q&A freeze guard.',
            ]);
            $this->fail('Frozen user was able to submit an answer.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('User is frozen from Q&A submissions', $exception->getMessage());
        }

        $this->assertSame(0, Answer::where('user_id', $user->id)->count());
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

    private function question(User $user, string $status = 'pending'): Question
    {
        return Question::create([
            'user_id' => $user->id,
            'title' => 'Published process question',
            'body' => 'How should we review this Q&A workflow?',
            'is_anonymous' => false,
            'status' => $status,
        ]);
    }

    private function warning(User $user, Question $question, string $status): QaModerationWarning
    {
        return QaModerationWarning::create([
            'user_id' => $user->id,
            'warnable_type' => 'question',
            'warnable_id' => $question->id,
            'source' => 'system_rule',
            'severity' => 'medium',
            'reason' => 'Moderation warning.',
            'evidence' => ['rule_id' => 1],
            'status' => $status,
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
