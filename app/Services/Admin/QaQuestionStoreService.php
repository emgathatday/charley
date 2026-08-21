<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QaQuestionStoreService
{
    public function store(array $data, int $actorId): int
    {
        return DB::transaction(function () use ($actorId, $data): int {
            $questionId = (int) DB::table('questions')->insertGetId($this->questionPayload($data, $actorId));
            $this->syncDomains($questionId, $data['knowledge_domain_ids'] ?? []);

            return $questionId;
        });
    }

    private function questionPayload(array $data, int $actorId): array
    {
        $payload = [
            'user_id' => $actorId,
            'posted_by_admin_id' => $actorId,
            'on_behalf_of_partner_id' => $data['on_behalf_of_partner_id'] ?? null,
            'weekly_theme_id' => $data['weekly_theme_id'] ?? null,
            'plant_type_id' => $data['plant_type_id'] ?? null,
            'title' => $data['title'],
            'body' => $data['body'],
            'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
            'status' => $data['status'] ?? 'pending',
            'attachment_media_ids' => $this->mediaIdsJson($data['attachment_media_ids'] ?? []),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('questions', 'question_mode')) {
            $payload['question_mode'] = $this->questionMode($data);
        }

        return $payload;
    }

    private function syncDomains(int $questionId, array $domainIds): void
    {
        if (! Schema::hasTable('question_domain_links') || $domainIds === []) {
            return;
        }

        $rows = collect($domainIds)
            ->unique()
            ->map(fn (int $domainId): array => [
                'question_id' => $questionId,
                'knowledge_domain_id' => $domainId,
            ])
            ->all();

        DB::table('question_domain_links')->insert($rows);
    }

    private function mediaIdsJson(array $mediaIds): ?string
    {
        $ids = collect($mediaIds)->unique()->values()->all();

        return $ids === [] ? null : json_encode($ids);
    }

    private function questionMode(array $data): string
    {
        if (! empty($data['on_behalf_of_partner_id'])) {
            return 'admin_on_behalf';
        }

        return $data['question_mode'] ?? 'admin_seed';
    }
}
