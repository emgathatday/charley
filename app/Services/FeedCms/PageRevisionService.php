<?php

namespace App\Services\FeedCms;

use App\Models\Page;
use App\Models\PageRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PageRevisionService
{
    public function createRevision(Page $page, User $changedBy, ?string $summary = null): PageRevision
    {
        return DB::transaction(fn (): PageRevision => PageRevision::query()->create([
            'page_id' => $page->id,
            'content_blocks' => $page->content_blocks,
            'changed_by' => $changedBy->id,
            'change_summary' => $summary,
            'created_at' => now(),
        ]));
    }

    public function rollback(Page $page, PageRevision $revision, User $changedBy): Page
    {
        return DB::transaction(function () use ($page, $revision, $changedBy): Page {
            abort_unless($revision->page_id === $page->id, 422, 'Revision does not belong to this page.');

            $this->createRevision($page, $changedBy, 'Snapshot before rollback.');

            $page->forceFill([
                'content_blocks' => $revision->content_blocks,
            ])->save();

            $this->createRevision($page, $changedBy, "Rolled back to revision #{$revision->id}.");

            return $page->refresh();
        });
    }
}
