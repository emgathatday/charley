<?php

namespace App\Policies;

use App\Models\LibraryItem;
use App\Models\User;
use App\Services\Library\LibraryAccessService;
use Illuminate\Auth\Access\Response;

class LibraryItemPolicy
{
    public function before(?User $user): ?bool
    {
        return $user?->role === 'admin' ? true : null;
    }

    public function viewAny(?User $user): Response
    {
        return Response::allow();
    }

    public function view(?User $user, LibraryItem $libraryItem): Response
    {
        if (! $this->isPublishedAndApproved($libraryItem)) {
            return Response::deny('Library item is not available.');
        }

        return app(LibraryAccessService::class)->canView($libraryItem, $user, $this->partnerTier())
            ? Response::allow()
            : Response::deny('You are not allowed to view this library item.');
    }

    public function download(User $user, LibraryItem $libraryItem): Response
    {
        return app(LibraryAccessService::class)->canDownload($libraryItem, $user, $this->partnerTier())
            ? Response::allow()
            : Response::deny('You are not allowed to download this library item.');
    }

    public function copyPaste(User $user, LibraryItem $libraryItem): Response
    {
        return app(LibraryAccessService::class)->canCopyPaste($libraryItem, $user, $this->partnerTier())
            ? Response::allow()
            : Response::deny('You are not allowed to copy this library item.');
    }

    public function manage(User $user): Response
    {
        return Response::deny('Only admins can manage library items.');
    }

    public function approve(User $user, LibraryItem $libraryItem): Response
    {
        return Response::deny('Only admins can approve library items.');
    }

    public function archive(User $user, LibraryItem $libraryItem): Response
    {
        return Response::deny('Only admins can archive library items.');
    }

    private function isPublishedAndApproved(LibraryItem $libraryItem): bool
    {
        return $libraryItem->status === LibraryItem::STATUS_PUBLISHED && $libraryItem->approved_at !== null;
    }

    private function partnerTier(): ?string
    {
        return request()->input('partner_tier');
    }
}
