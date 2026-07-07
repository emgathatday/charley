<?php

namespace App\Services\Library;

use App\Models\LibraryAccessLog;
use App\Models\LibraryAccessRule;
use App\Models\LibraryItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LibraryAccessService
{
    public function ruleForTier(?string $partnerTier): ?LibraryAccessRule
    {
        if (! $partnerTier) {
            return null;
        }

        return LibraryAccessRule::query()->forTier($partnerTier)->first();
    }

    public function canView(LibraryItem $item, ?User $user = null, ?string $partnerTier = null): bool
    {
        if ($item->status !== LibraryItem::STATUS_PUBLISHED || ! $item->approved_at) {
            return false;
        }

        if ($item->access_level === 'public') {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($item->access_level === 'member') {
            return true;
        }

        $rule = $this->ruleForTier($partnerTier);

        return match ($item->access_level) {
            'professional_only' => in_array($user->role, ['professional', 'partner', 'admin'], true) || (bool) $rule?->can_view,
            'partner_only' => (bool) $rule?->can_view,
            'admin_only' => false,
            default => false,
        };
    }

    public function canDownload(LibraryItem $item, ?User $user = null, ?string $partnerTier = null): bool
    {
        if (! $this->canView($item, $user, $partnerTier) || ! $item->download_allowed || ! $item->file_media_id) {
            return false;
        }

        if ($user?->role === 'admin') {
            return true;
        }

        return (bool) $this->ruleForTier($partnerTier)?->can_download;
    }

    public function requiresWatermark(LibraryItem $item, ?string $partnerTier = null): bool
    {
        if (! $item->file_media_id) {
            return false;
        }

        return (bool) ($this->ruleForTier($partnerTier)?->requires_watermark ?? true);
    }

    public function canCopyPaste(LibraryItem $item, ?User $user = null, ?string $partnerTier = null): bool
    {
        if ($item->copy_paste_disabled || ! $this->canView($item, $user, $partnerTier)) {
            return false;
        }

        if ($user?->role === 'admin') {
            return true;
        }

        return (bool) $this->ruleForTier($partnerTier)?->can_copy_paste;
    }

    public function assertCanView(LibraryItem $item, ?User $user = null, ?string $partnerTier = null): void
    {
        if (! $this->canView($item, $user, $partnerTier)) {
            throw ValidationException::withMessages([
                'library_item' => 'This library item is not available for the current access level.',
            ]);
        }
    }

    public function assertCanDownload(LibraryItem $item, ?User $user = null, ?string $partnerTier = null): void
    {
        if (! $this->canDownload($item, $user, $partnerTier)) {
            throw ValidationException::withMessages([
                'library_item' => 'This library item cannot be downloaded for the current access level.',
            ]);
        }
    }

    public function recordAccess(LibraryItem $item, User $user, string $action, string $ipAddress): LibraryAccessLog
    {
        if (! in_array($action, LibraryAccessLog::ACTIONS, true)) {
            throw ValidationException::withMessages([
                'action' => 'The library access action is not supported.',
            ]);
        }

        return DB::transaction(function () use ($item, $user, $action, $ipAddress): LibraryAccessLog {
            if ($action === LibraryAccessLog::ACTION_VIEW) {
                $item->increment('view_count');
            }

            if ($action === LibraryAccessLog::ACTION_DOWNLOAD) {
                $item->increment('download_count');
            }

            return LibraryAccessLog::query()->create([
                'library_item_id' => $item->id,
                'user_id' => $user->id,
                'action' => $action,
                'ip_address' => $ipAddress,
                'created_at' => now(),
            ]);
        });
    }
}
