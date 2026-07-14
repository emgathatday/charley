<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LibraryItemRequest;
use App\Http\Resources\LibraryAccessLogResource;
use App\Http\Resources\LibraryItemResource;
use App\Models\LibraryItem;
use App\Services\LibraryItemService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class LibraryItemController extends Controller
{
    public function __construct(private readonly LibraryItemService $libraryItems)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return LibraryItemResource::collection($this->libraryItems->search($request->query(), (int) $request->integer('per_page', 15)));
    }

    public function store(LibraryItemRequest $request): LibraryItemResource
    {
        return LibraryItemResource::make($this->libraryItems->create($request->validated(), $request->user()));
    }

    public function show(LibraryItem $libraryItem): LibraryItemResource
    {
        return LibraryItemResource::make($libraryItem->load(['category', 'plantType', 'fileMedia']));
    }

    public function update(LibraryItemRequest $request, LibraryItem $libraryItem): LibraryItemResource
    {
        return LibraryItemResource::make($this->libraryItems->update($libraryItem, $request->validated()));
    }

    public function destroy(LibraryItem $libraryItem): Response
    {
        $libraryItem->delete();

        return response()->noContent();
    }

    public function approve(Request $request, LibraryItem $libraryItem): LibraryItemResource
    {
        return LibraryItemResource::make($this->libraryItems->approve($libraryItem, $request->user()));
    }

    public function archive(LibraryItem $libraryItem): LibraryItemResource
    {
        return LibraryItemResource::make($this->libraryItems->archive($libraryItem));
    }

    public function view(Request $request, LibraryItem $libraryItem): LibraryAccessLogResource
    {
        return LibraryAccessLogResource::make($this->libraryItems->recordView(
            $libraryItem,
            $request->user(),
            $request->ip(),
            $request->string('partner_tier')->toString() ?: null,
        ));
    }

    public function download(Request $request, LibraryItem $libraryItem): LibraryAccessLogResource
    {
        return LibraryAccessLogResource::make($this->libraryItems->recordDownload(
            $libraryItem,
            $request->user(),
            $request->ip(),
            $request->string('partner_tier')->toString() ?: null,
        ));
    }
}
