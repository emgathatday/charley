<?php

namespace App\Services\FeedCms;

use App\Models\HomepageFeedPriority;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeedPriorityService
{
    public function priorities(): Collection
    {
        return HomepageFeedPriority::query()
            ->orderByDesc('priority_weight')
            ->orderBy('content_type')
            ->get();
    }

    public function score(string $contentType, int $baseScore = 0): int
    {
        $priority = HomepageFeedPriority::query()
            ->active()
            ->where('content_type', $contentType)
            ->first();

        if (! $priority) {
            return $baseScore;
        }

        $highlightBoost = $priority->is_highlighted ? 25 : 0;

        return $baseScore + $priority->priority_weight + $highlightBoost;
    }

    public function updatePriority(string $contentType, array $attributes, ?User $updatedBy = null): HomepageFeedPriority
    {
        if (! in_array($contentType, HomepageFeedPriority::CONTENT_TYPES, true)) {
            throw ValidationException::withMessages([
                'content_type' => 'The feed content type is not supported.',
            ]);
        }

        return DB::transaction(function () use ($contentType, $attributes, $updatedBy): HomepageFeedPriority {
            return HomepageFeedPriority::query()->updateOrCreate(
                ['content_type' => $contentType],
                [
                    'priority_weight' => $attributes['priority_weight'] ?? 0,
                    'is_highlighted' => $attributes['is_highlighted'] ?? false,
                    'highlight_color' => $attributes['highlight_color'] ?? null,
                    'is_active' => $attributes['is_active'] ?? true,
                    'updated_by' => $updatedBy?->id,
                ],
            );
        });
    }
}
