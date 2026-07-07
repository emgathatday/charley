<?php

namespace App\Http\Controllers;

use App\Models\LibraryAccessLog;
use App\Models\LibraryCategory;
use App\Models\LibraryItem;
use App\Services\Library\LibraryAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LibraryPageController extends Controller
{
    public function __construct(
        private readonly LibraryAccessService $access,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['category_id', 'content_type', 'access_level', 'search']);

        return view('library.index', [
            'categories' => LibraryCategory::query()->withCount('items')->ordered()->get(),
            'contentTypes' => LibraryItem::CONTENT_TYPES,
            'accessLevels' => LibraryItem::ACCESS_LEVELS,
            'filters' => $filters,
            'items' => $this->publishedItems($filters)->paginate(12)->withQueryString(),
            'featuredItems' => $this->publishedItems()->limit(4)->get(),
        ]);
    }

    public function category(Request $request, LibraryCategory $category): View
    {
        $filters = array_merge($request->only(['content_type', 'access_level', 'search']), [
            'category_id' => $category->id,
        ]);

        return view('library.category', [
            'category' => $category->load('parent'),
            'categories' => LibraryCategory::query()->withCount('items')->ordered()->get(),
            'contentTypes' => LibraryItem::CONTENT_TYPES,
            'accessLevels' => LibraryItem::ACCESS_LEVELS,
            'filters' => $filters,
            'items' => $this->publishedItems($filters)->paginate(12)->withQueryString(),
            'featuredItems' => collect(),
        ]);
    }

    public function show(Request $request, LibraryItem $libraryItem): View
    {
        $tier = $request->string('partner_tier')->toString() ?: null;
        $libraryItem->load(['category', 'plantType', 'fileMedia']);

        if (! $this->access->canView($libraryItem, $request->user(), $tier)) {
            return view('library.access-denied', [
                'item' => $libraryItem,
                'partnerTier' => $tier,
                'reason' => 'This item is protected by the current library access rules.',
            ]);
        }

        return view('library.show', [
            'item' => $libraryItem,
            'partnerTier' => $tier,
            'canDownload' => $this->access->canDownload($libraryItem, $request->user(), $tier),
            'canCopyPaste' => $this->access->canCopyPaste($libraryItem, $request->user(), $tier),
            'requiresWatermark' => $this->access->requiresWatermark($libraryItem, $tier),
            'relatedItems' => $this->publishedItems(['category_id' => $libraryItem->category_id])
                ->whereKeyNot($libraryItem->id)
                ->limit(3)
                ->get(),
        ]);
    }

    public function preview(Request $request, LibraryItem $libraryItem): View
    {
        $tier = $request->string('partner_tier')->toString() ?: null;
        $libraryItem->load(['category', 'fileMedia']);

        if (! $this->access->canView($libraryItem, $request->user(), $tier)) {
            return view('library.access-denied', [
                'item' => $libraryItem,
                'partnerTier' => $tier,
                'reason' => 'Preview is not available for this access level.',
            ]);
        }

        return view('library.preview', [
            'item' => $libraryItem,
            'partnerTier' => $tier,
            'requiresWatermark' => $this->access->requiresWatermark($libraryItem, $tier),
            'canDownload' => $this->access->canDownload($libraryItem, $request->user(), $tier),
        ]);
    }

    public function download(Request $request, LibraryItem $libraryItem): View|RedirectResponse
    {
        $tier = $request->string('partner_tier')->toString() ?: null;
        $libraryItem->load('fileMedia');

        if (! $this->access->canDownload($libraryItem, $request->user(), $tier)) {
            return view('library.access-denied', [
                'item' => $libraryItem,
                'partnerTier' => $tier,
                'reason' => 'Download is disabled for this item or access level.',
            ]);
        }

        $this->access->recordAccess($libraryItem, $request->user(), LibraryAccessLog::ACTION_DOWNLOAD, $request->ip());

        return view('library.download', [
            'item' => $libraryItem,
            'partnerTier' => $tier,
            'requiresWatermark' => $this->access->requiresWatermark($libraryItem, $tier),
        ]);
    }

    public function recordView(Request $request, LibraryItem $libraryItem): RedirectResponse
    {
        $tier = $request->string('partner_tier')->toString() ?: null;

        $this->access->assertCanView($libraryItem, $request->user(), $tier);
        $this->access->recordAccess($libraryItem, $request->user(), LibraryAccessLog::ACTION_VIEW, $request->ip());

        return redirect()
            ->route('library.items.show', ['libraryItem' => $libraryItem, 'partner_tier' => $tier])
            ->with('status', 'Library view recorded.');
    }

    private function publishedItems(array $filters = []): Builder
    {
        return LibraryItem::query()
            ->with(['category', 'plantType', 'fileMedia'])
            ->published()
            ->approved()
            ->when($filters['category_id'] ?? null, fn (Builder $query, int|string $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['content_type'] ?? null, fn (Builder $query, string $contentType) => $query->where('content_type', $contentType))
            ->when($filters['access_level'] ?? null, fn (Builder $query, string $accessLevel) => $query->where('access_level', $accessLevel))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(function (Builder $inner) use ($search): void {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%");
            }))
            ->latest('published_year')
            ->latest('updated_at');
    }
}
