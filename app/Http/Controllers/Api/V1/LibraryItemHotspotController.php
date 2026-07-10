<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\KnowledgeDomain\LibraryItemHotspotRequest;
use App\Http\Resources\LibraryItemHotspotResource;
use App\Models\KnowledgeDomain;
use App\Models\LibraryItem;
use App\Models\LibraryItemHotspot;
use App\Services\Library\LibraryItemHotspotService;
use Illuminate\Http\Request;

class LibraryItemHotspotController extends Controller
{
    public function __construct(private readonly LibraryItemHotspotService $libraryItemHotspotService)
    {
    }

    public function index(Request $request, LibraryItem $libraryItem)
    {
        $query = LibraryItemHotspot::query()
            ->with('knowledgeDomain.rankTiers')
            ->where('library_item_id', $libraryItem->id)
            ->ordered();

        return LibraryItemHotspotResource::collection($query->get());
    }

    public function store(LibraryItemHotspotRequest $request, LibraryItem $libraryItem)
    {
        $data = $request->validated();
        $knowledgeDomain = KnowledgeDomain::query()->findOrFail($data['knowledge_domain_id']);
        unset($data['knowledge_domain_id']);

        return new LibraryItemHotspotResource(
            $this->libraryItemHotspotService->create($libraryItem, $knowledgeDomain, $data)->load('knowledgeDomain'),
        );
    }

    public function update(LibraryItemHotspotRequest $request, LibraryItemHotspot $libraryItemHotspot)
    {
        return new LibraryItemHotspotResource(
            $this->libraryItemHotspotService->update($libraryItemHotspot, $request->validated()),
        );
    }

    public function destroy(LibraryItemHotspot $libraryItemHotspot)
    {
        $this->libraryItemHotspotService->delete($libraryItemHotspot);

        return response()->json(['deleted' => true]);
    }
}