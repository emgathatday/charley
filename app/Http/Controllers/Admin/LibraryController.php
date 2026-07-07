<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LibraryAccessLog;
use App\Models\LibraryAccessRule;
use App\Models\LibraryCategory;
use App\Models\LibraryItem;
use App\Models\MediaFile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LibraryController extends Controller
{
    public function index(): View
    {
        return view('admin.library.index', [
            'stats' => $this->stats(),
            'pendingItems' => $this->itemsQuery(['status' => LibraryItem::STATUS_DRAFT])->limit(8)->get(),
            'recentLogs' => LibraryAccessLog::query()->with(['item', 'user'])->latest('created_at')->limit(8)->get(),
            'rules' => LibraryAccessRule::query()->orderBy('partner_tier')->get(),
        ]);
    }

    public function categories(): View
    {
        return view('admin.library.categories', [
            'stats' => $this->stats(),
            'categories' => LibraryCategory::query()->with('parent')->withCount('items')->ordered()->paginate(20),
        ]);
    }

    public function items(Request $request): View
    {
        $filters = $request->only(['status', 'access_level', 'content_type', 'search']);

        return view('admin.library.items', [
            'filters' => $filters,
            'stats' => $this->stats(),
            'items' => $this->itemsQuery($filters)->paginate(20)->withQueryString(),
            'statuses' => LibraryItem::STATUSES,
            'accessLevels' => LibraryItem::ACCESS_LEVELS,
            'contentTypes' => LibraryItem::CONTENT_TYPES,
        ]);
    }

    public function approvals(): View
    {
        return view('admin.library.approvals', [
            'stats' => $this->stats(),
            'items' => $this->itemsQuery(['status' => LibraryItem::STATUS_DRAFT])->paginate(20),
        ]);
    }

    public function accessRules(): View
    {
        return view('admin.library.access-rules', [
            'stats' => $this->stats(),
            'rules' => LibraryAccessRule::query()->with('updater')->orderBy('partner_tier')->get(),
            'partnerTiers' => LibraryAccessRule::PARTNER_TIERS,
        ]);
    }

    public function accessLogs(Request $request): View
    {
        $filters = $request->only(['action', 'search']);

        return view('admin.library.access-logs', [
            'filters' => $filters,
            'stats' => $this->stats(),
            'actions' => LibraryAccessLog::ACTIONS,
            'logs' => LibraryAccessLog::query()
                ->with(['item', 'user'])
                ->when($request->filled('action'), fn ($query) => $query->where('action', $request->input('action')))
                ->when($request->filled('search'), fn ($query) => $query->whereHas('item', fn ($itemQuery) => $itemQuery->where('title', 'like', "%{$request->input('search')}%")))
                ->latest('created_at')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function uploadMetadata(): View
    {
        return view('admin.library.upload-metadata', [
            'stats' => $this->stats(),
            'items' => LibraryItem::query()->with(['category', 'fileMedia'])->whereNotNull('file_media_id')->latest('updated_at')->paginate(20),
            'orphanMedia' => MediaFile::query()->where('is_orphan', true)->latest()->limit(10)->get(),
        ]);
    }

    private function itemsQuery(array $filters = []): mixed
    {
        return LibraryItem::query()
            ->with(['category', 'plantType', 'fileMedia', 'approver'])
            ->withCount('accessLogs')
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['access_level'] ?? null, fn ($query, string $accessLevel) => $query->where('access_level', $accessLevel))
            ->when($filters['content_type'] ?? null, fn ($query, string $contentType) => $query->where('content_type', $contentType))
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where('title', 'like', "%{$search}%"))
            ->latest('updated_at');
    }

    private function stats(): array
    {
        return [
            'categories' => LibraryCategory::query()->count(),
            'items' => LibraryItem::query()->count(),
            'pending' => LibraryItem::query()->whereNull('approved_at')->where('status', LibraryItem::STATUS_DRAFT)->count(),
            'published' => LibraryItem::query()->where('status', LibraryItem::STATUS_PUBLISHED)->count(),
            'downloads' => LibraryAccessLog::query()->where('action', LibraryAccessLog::ACTION_DOWNLOAD)->count(),
            'rules' => LibraryAccessRule::query()->count(),
        ];
    }
}
