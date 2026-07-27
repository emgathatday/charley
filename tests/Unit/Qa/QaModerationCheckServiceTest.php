<?php

namespace Tests\Unit\Qa;

use App\Models\Answer;
use App\Models\QaModerationRule;
use App\Models\QaModerationWarning;
use App\Models\Question;
use App\Models\User;
use App\Services\Qa\Moderation\QaModerationProvider;
use App\Services\Qa\QaModerationCheckService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QaModerationCheckServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('qa_moderation_warnings');
        Schema::dropIfExists('qa_moderation_rules');
        Schema::dropIfExists('answers');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_keyword_rule_creates_system_rule_warning_and_skips_ai_provider(): void
    {
        $user = $this->user();
        $question = Question::create([
            'user_id' => $user->id,
            'title' => 'Unsafe bypass request',
            'body' => 'How can I bypass this safety interlock?',
            'is_anonymous' => false,
            'status' => 'pending',
        ]);
        $this->rule('Blocked keyword', 'keyword', ['keywords' => ['bypass']], 'question', 'high');
        $aiProvider = new CountingModerationProvider(['source' => 'ai', 'reason' => 'AI should not run.']);

        $warning = (new QaModerationCheckService(aiProvider: $aiProvider))->checkQuestion($question);

        $this->assertInstanceOf(QaModerationWarning::class, $warning);
        $this->assertSame('system_rule', $warning->source);
        $this->assertSame('pending_review', $warning->status);
        $this->assertSame('high', $warning->severity);
        $this->assertSame(0, $aiProvider->calls);
    }

    public function test_max_links_min_length_and_regex_rules_match_expected_content(): void
    {
        $user = $this->user();
        $question = Question::create([
            'user_id' => $user->id,
            'title' => 'External references',
            'body' => 'See http://one.test and https://two.test',
            'is_anonymous' => false,
            'status' => 'pending',
        ]);
        $this->rule('Maximum links', 'max_links', ['max_links' => 1], 'question', 'medium');

        $linkWarning = (new QaModerationCheckService)->checkQuestion($question);
        $this->assertSame('max_links', $linkWarning->evidence['rule_type']);
        $this->assertSame(2, $linkWarning->evidence['link_count']);

        QaModerationRule::query()->delete();
        $answer = Answer::create([
            'question_id' => $question->id,
            'user_id' => $user->id,
            'body' => 'short',
            'is_anonymous' => false,
            'is_admin_featured' => false,
        ]);
        $this->rule('Minimum answer detail', 'min_length', ['min_length' => 30], 'answer', 'low');

        $lengthWarning = (new QaModerationCheckService)->checkAnswer($answer);
        $this->assertSame('min_length', $lengthWarning->evidence['rule_type']);
        $this->assertSame(5, $lengthWarning->evidence['length']);

        QaModerationRule::query()->delete();
        $this->rule('Regex guard', 'regex', ['pattern' => '/secret-[0-9]+/i'], 'both', 'medium');
        $question->forceFill(['title' => 'Token leak', 'body' => 'The code is secret-12345.'])->save();

        $regexWarning = (new QaModerationCheckService)->checkQuestion($question->refresh());
        $this->assertSame('regex', $regexWarning->evidence['rule_type']);
        $this->assertSame('/secret-[0-9]+/i', $regexWarning->evidence['pattern']);
    }

    public function test_ai_provider_is_called_only_after_active_rules_pass_and_ai_is_enabled(): void
    {
        config(['qa.ai_moderation_enabled' => true]);

        $user = $this->user();
        $question = Question::create([
            'user_id' => $user->id,
            'title' => 'Normal operating question',
            'body' => 'What checks are recommended before startup?',
            'is_anonymous' => false,
            'status' => 'pending',
        ]);
        $this->rule('Blocked keyword', 'keyword', ['keywords' => ['bypass']], 'question', 'high');
        $aiProvider = new CountingModerationProvider([
            'severity' => 'medium',
            'reason' => 'AI placeholder flagged the content.',
            'evidence' => ['provider' => 'fake'],
        ]);

        $warning = (new QaModerationCheckService(aiProvider: $aiProvider))->checkQuestion($question);

        $this->assertSame(1, $aiProvider->calls);
        $this->assertSame('ai', $warning->source);
        $this->assertSame('AI placeholder flagged the content.', $warning->reason);
    }

    private function user(): User
    {
        return User::create([
            'username' => 'qa-user-'.User::query()->count(),
            'first_name' => 'QA',
            'last_name' => 'User',
            'email' => 'qa-user-'.User::query()->count().'@example.test',
            'password' => Hash::make('password'),
            'role' => 'professional',
            'status' => 'active',
            'is_verified' => true,
            'login_attempts' => 0,
            'mfa_enabled' => false,
        ]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function rule(string $name, string $type, array $config, string $target = 'both', string $severity = 'medium'): QaModerationRule
    {
        return QaModerationRule::create([
            'name' => $name,
            'rule_type' => $type,
            'target_type' => $target,
            'config' => $config,
            'severity' => $severity,
            'is_active' => true,
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
    }
}

class CountingModerationProvider implements QaModerationProvider
{
    public int $calls = 0;

    /**
     * @param array<string, mixed>|null $risk
     */
    public function __construct(private readonly ?array $risk) {}

    public function check(array $payload): ?array
    {
        $this->calls++;

        return $this->risk;
    }
}
