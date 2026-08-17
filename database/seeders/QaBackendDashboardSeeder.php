<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class QaBackendDashboardSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('questions') || ! Schema::hasTable('answers') || ! Schema::hasTable('users')) {
            return;
        }

        $now = now();
        $users = $this->users();
        $plantTypeIds = $this->plantTypeIds();
        $weeklyThemeId = $this->weeklyThemeId();
        $attachmentMediaIds = $this->attachmentMediaIds();

        $questions = [
            [
                'user_id' => $users['asker'],
                'weekly_theme_id' => $weeklyThemeId,
                'plant_type_id' => $plantTypeIds[0] ?? null,
                'title' => 'QA Demo: syngas compressor vibration after restart',
                'body' => 'The syngas compressor vibration rises during the first hour after restart. Operations need a review checklist before increasing load.',
                'is_anonymous' => false,
                'status' => 'published',
                'attachment_media_ids' => $attachmentMediaIds,
                'created_at' => $now->copy()->subDays(3),
                'updated_at' => $now,
            ],
            [
                'user_id' => $users['pending'],
                'weekly_theme_id' => $weeklyThemeId,
                'plant_type_id' => $plantTypeIds[1] ?? ($plantTypeIds[0] ?? null),
                'title' => 'QA Demo: pending approval for steam trap survey',
                'body' => 'A maintenance team wants to validate steam trap survey data before the question is published to the community.',
                'is_anonymous' => true,
                'status' => 'pending',
                'attachment_media_ids' => null,
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now,
            ],
            [
                'user_id' => $users['blocked'],
                'weekly_theme_id' => null,
                'plant_type_id' => $plantTypeIds[2] ?? ($plantTypeIds[0] ?? null),
                'title' => 'QA Demo: blocked exchanger chemical cleaning claim',
                'body' => 'This question includes an unsafe chemical cleaning recommendation and stays hidden until a moderator reviews the operating context.',
                'is_anonymous' => false,
                'status' => 'hidden',
                'attachment_media_ids' => null,
                'created_at' => $now->copy()->subDay(),
                'updated_at' => $now,
            ],
        ];

        $questionIds = [];

        foreach ($questions as $question) {
            DB::table('questions')->updateOrInsert(
                ['title' => $question['title']],
                $question,
            );

            $questionIds[$question['title']] = (int) DB::table('questions')->where('title', $question['title'])->value('id');
        }

        $activeQuestionId = $questionIds['QA Demo: syngas compressor vibration after restart'];

        $answers = [
            [
                'question_id' => $activeQuestionId,
                'user_id' => $users['expert'],
                'is_anonymous' => false,
                'body' => 'Trend bearing temperature beside the vibration spectrum, verify lube oil differential pressure, then inspect coupling alignment before raising load.',
                'is_admin_featured' => true,
                'confidence_level' => 'high',
                'admin_rank_order' => 1,
                'attachment_media_ids' => null,
                'created_at' => $now->copy()->subDays(2)->addHours(2),
                'updated_at' => $now,
            ],
            [
                'question_id' => $activeQuestionId,
                'user_id' => $users['asker'],
                'is_anonymous' => false,
                'body' => 'Compare the restart profile against the last stable startup and capture vibration at each load hold point.',
                'is_admin_featured' => false,
                'confidence_level' => 'medium',
                'admin_rank_order' => null,
                'attachment_media_ids' => null,
                'created_at' => $now->copy()->subDays(2)->addHours(4),
                'updated_at' => $now,
            ],
            [
                'question_id' => $activeQuestionId,
                'user_id' => $users['anonymous'],
                'is_anonymous' => true,
                'body' => 'Anonymous field note: check whether casing drain temperatures changed after the restart sequence.',
                'is_admin_featured' => false,
                'confidence_level' => null,
                'admin_rank_order' => null,
                'attachment_media_ids' => null,
                'created_at' => $now->copy()->subDays(2)->addHours(6),
                'updated_at' => $now,
            ],
            [
                'question_id' => $activeQuestionId,
                'user_id' => $users['warned'],
                'is_anonymous' => false,
                'body' => 'Bypass the trip check temporarily and continue loading while watching the vibration trend.',
                'is_admin_featured' => false,
                'confidence_level' => 'low',
                'admin_rank_order' => null,
                'attachment_media_ids' => null,
                'created_at' => $now->copy()->subDays(2)->addHours(8),
                'updated_at' => $now,
            ],
        ];

        foreach ($answers as $answer) {
            DB::table('answers')->updateOrInsert(
                ['question_id' => $answer['question_id'], 'body' => $answer['body']],
                $answer,
            );
        }

        $this->seedDomainLinks($questionIds);
        $this->seedWarning($activeQuestionId, $users['warned']);
    }

    /**
     * @return array<string, int>
     */
    private function users(): array
    {
        $demoUsers = [
            'asker' => ['username' => 'qa.demo.asker', 'first_name' => 'QA', 'last_name' => 'Asker', 'email' => 'qa.demo.asker@example.test'],
            'expert' => ['username' => 'qa.demo.expert', 'first_name' => 'QA', 'last_name' => 'Expert', 'email' => 'qa.demo.expert@example.test'],
            'anonymous' => ['username' => 'qa.demo.anonymous', 'first_name' => 'QA', 'last_name' => 'Anonymous', 'email' => 'qa.demo.anonymous@example.test'],
            'warned' => ['username' => 'qa.demo.warned', 'first_name' => 'QA', 'last_name' => 'Warned', 'email' => 'qa.demo.warned@example.test'],
            'pending' => ['username' => 'qa.demo.pending', 'first_name' => 'QA', 'last_name' => 'Pending', 'email' => 'qa.demo.pending@example.test'],
            'blocked' => ['username' => 'qa.demo.blocked', 'first_name' => 'QA', 'last_name' => 'Blocked', 'email' => 'qa.demo.blocked@example.test'],
        ];

        $userIds = [];

        foreach ($demoUsers as $key => $user) {
            $existingUserId = DB::table('users')->where('username', $user['username'])->value('id');

            DB::table('users')->updateOrInsert(
                ['username' => $user['username']],
                [
                    ...$user,
                    'password' => Hash::make('password'),
                    'role' => 'professional',
                    'status' => 'active',
                    'is_verified' => true,
                    'verified_at' => now(),
                    'login_attempts' => 0,
                    'mfa_enabled' => false,
                    'created_at' => $existingUserId ? DB::table('users')->where('id', $existingUserId)->value('created_at') : now(),
                    'updated_at' => now(),
                ],
            );

            $userIds[$key] = (int) DB::table('users')->where('username', $user['username'])->value('id');
        }

        return $userIds;
    }

    /**
     * @return array<int, int>
     */
    private function plantTypeIds(): array
    {
        if (! Schema::hasTable('plant_types')) {
            return [];
        }

        return DB::table('plant_types')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(3)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function weeklyThemeId(): ?int
    {
        if (! Schema::hasTable('weekly_themes')) {
            return null;
        }

        $themeId = DB::table('weekly_themes')
            ->where('status', 'active')
            ->orderByDesc('week_start_date')
            ->value('id');

        return $themeId ? (int) $themeId : null;
    }

    private function attachmentMediaIds(): ?string
    {
        if (! Schema::hasTable('media_files')) {
            return null;
        }

        $mediaIds = DB::table('media_files')
            ->orderBy('id')
            ->limit(2)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return $mediaIds === [] ? null : json_encode($mediaIds);
    }

    /**
     * @param  array<string, int>  $questionIds
     */
    private function seedDomainLinks(array $questionIds): void
    {
        if (! Schema::hasTable('question_domain_links') || ! Schema::hasTable('knowledge_domains')) {
            return;
        }

        $domainIds = DB::table('knowledge_domains')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(3)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($domainIds === []) {
            return;
        }

        $index = 0;

        foreach ($questionIds as $questionId) {
            DB::table('question_domain_links')->updateOrInsert([
                'question_id' => $questionId,
                'knowledge_domain_id' => $domainIds[$index % count($domainIds)],
            ]);

            $index++;
        }
    }

    private function seedWarning(int $activeQuestionId, int $warnedUserId): void
    {
        if (! Schema::hasTable('qa_moderation_warnings')) {
            return;
        }

        $answerId = DB::table('answers')
            ->where('question_id', $activeQuestionId)
            ->where('user_id', $warnedUserId)
            ->where('body', 'Bypass the trip check temporarily and continue loading while watching the vibration trend.')
            ->value('id');

        if (! $answerId) {
            return;
        }

        DB::table('qa_moderation_warnings')->updateOrInsert(
            [
                'warnable_type' => 'answer',
                'warnable_id' => (int) $answerId,
                'source' => 'system_rule',
            ],
            [
                'user_id' => $warnedUserId,
                'severity' => 'high',
                'reason' => 'Unsafe operating instruction detected in demo answer.',
                'evidence' => json_encode(['keyword' => 'bypass trip check', 'seeder' => static::class]),
                'status' => 'pending_review',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
}
