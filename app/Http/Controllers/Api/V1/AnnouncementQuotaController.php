<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnnouncementQuotaRequest;
use App\Http\Resources\AnnouncementQuotaResource;
use App\Models\AnnouncementQuota;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class AnnouncementQuotaController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $quotas = AnnouncementQuota::query()
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('period'), fn ($query) => $query->where('period', $request->string('period')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return AnnouncementQuotaResource::collection($quotas);
    }

    public function store(AnnouncementQuotaRequest $request): AnnouncementQuotaResource
    {
        $quota = AnnouncementQuota::updateOrCreate(
            [
                'user_id' => $request->integer('user_id') ?: $request->user()->id,
                'period' => $request->input('period'),
            ],
            [
                'used_count' => $request->integer('used_count', 0),
                'quota_limit' => $request->integer('quota_limit'),
            ],
        );

        return new AnnouncementQuotaResource($quota);
    }

    public function show(AnnouncementQuota $announcementQuota): AnnouncementQuotaResource
    {
        return new AnnouncementQuotaResource($announcementQuota);
    }

    public function update(AnnouncementQuotaRequest $request, AnnouncementQuota $announcementQuota): AnnouncementQuotaResource
    {
        $announcementQuota->update($request->validated());

        return new AnnouncementQuotaResource($announcementQuota);
    }

    public function consume(Request $request, AnnouncementQuota $announcementQuota): AnnouncementQuotaResource
    {
        abort_unless($announcementQuota->user_id === $request->user()->id || $this->canManage($request), 403);

        if ($announcementQuota->used_count >= $announcementQuota->quota_limit) {
            throw ValidationException::withMessages([
                'quota' => ['Announcement quota has been exhausted for this period.'],
            ]);
        }

        $announcementQuota->increment('used_count');

        return new AnnouncementQuotaResource($announcementQuota->refresh());
    }

    private function canManage(Request $request): bool
    {
        return in_array($request->user()?->role, ['admin', 'super_admin'], true);
    }
}
