<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpertDirectorySearchRequest;
use App\Http\Resources\SearchIndexEntryResource;
use App\Services\ProfileSearchIndexService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExpertDirectoryController extends Controller
{
    public function __construct(private readonly ProfileSearchIndexService $searchIndexService)
    {
    }

    public function index(ExpertDirectorySearchRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();
        $query = $this->searchIndexService->expertDirectoryQuery($data['q'] ?? null);

        if (isset($data['search_context'])) {
            $query->where('search_context', $data['search_context']);
        }

        if (array_key_exists('is_discoverable', $data)) {
            $query->where('is_discoverable', $data['is_discoverable']);
        }

        return SearchIndexEntryResource::collection(
            $query->latest('last_indexed_at')->paginate($data['per_page'] ?? 15)
        );
    }
}
