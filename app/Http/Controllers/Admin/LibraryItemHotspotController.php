<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeDomain;
use App\Models\LibraryItem;
use App\Models\LibraryItemHotspot;
use App\Services\Library\LibraryItemHotspotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LibraryItemHotspotController extends Controller
{
    public function __construct(
        private readonly LibraryItemHotspotService $hotspotService,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['item', 'domain']);

        return view('admin.library.hotspots.index', [
            'filters' => $filters,
            'stats' => [
                'items' => LibraryItem::query()->count(),
                'hotspots' => LibraryItemHotspot::query()->count(),
                'domains' => KnowledgeDomain::query()->active()->count(),
            ],
            'hotspots' => LibraryItemHotspot::query()
                ->with(['libraryItem', 'knowledgeDomain'])
                ->when($filters['item'] ?? null, fn ($query, string $item) => $query->where('library_item_id', $item))
                ->when($filters['domain'] ?? null, fn ($query, string $domain) => $query->where('knowledge_domain_id', $domain))
                ->ordered()
                ->paginate(20)
                ->withQueryString(),
            'items' => LibraryItem::query()->orderBy('title')->get(['id', 'title', 'slug', 'status']),
            'domains' => KnowledgeDomain::query()->active()->orderBy('name')->get(),
            'shapes' => LibraryItemHotspot::SHAPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->hotspotData($request);
        $libraryItem = LibraryItem::query()->findOrFail($data['library_item_id']);
        $knowledgeDomain = KnowledgeDomain::query()->findOrFail($data['knowledge_domain_id']);

        $this->hotspotService->create($libraryItem, $knowledgeDomain, $data);

        return redirect()->route('admin.dashboard.library.hotspots.index')->with('status', 'Hotspot created.');
    }

    public function update(Request $request, LibraryItemHotspot $libraryItemHotspot): RedirectResponse
    {
        $data = $this->hotspotData($request);
        $this->hotspotService->update($libraryItemHotspot, $data);

        return redirect()->route('admin.dashboard.library.hotspots.index')->with('status', 'Hotspot updated.');
    }

    public function destroy(LibraryItemHotspot $libraryItemHotspot): RedirectResponse
    {
        $this->hotspotService->delete($libraryItemHotspot);

        return redirect()->route('admin.dashboard.library.hotspots.index')->with('status', 'Hotspot removed.');
    }

    private function hotspotData(Request $request): array
    {
        $data = $request->validate([
            'library_item_id' => ['required', 'integer', 'exists:library_items,id'],
            'knowledge_domain_id' => ['required', 'integer', 'exists:knowledge_domains,id'],
            'label' => ['nullable', 'string', 'max:255'],
            'shape_type' => ['required', 'string', Rule::in(LibraryItemHotspot::SHAPES)],
            'coordinates' => ['required'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['coordinates'] = $this->decodeCoordinates($data['coordinates']);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }

    private function decodeCoordinates(mixed $coordinates): array
    {
        if (is_array($coordinates)) {
            return $coordinates;
        }

        if (! is_string($coordinates)) {
            throw ValidationException::withMessages(['coordinates' => 'Coordinates must be JSON.']);
        }

        $decoded = json_decode($coordinates, true);
        if (! is_array($decoded) || $decoded === []) {
            throw ValidationException::withMessages(['coordinates' => 'Coordinates must be a non-empty JSON array.']);
        }

        return $decoded;
    }
}
