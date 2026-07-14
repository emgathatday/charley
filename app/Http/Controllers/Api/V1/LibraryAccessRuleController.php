<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LibraryAccessRuleResource;
use App\Models\LibraryAccessRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LibraryAccessRuleController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return LibraryAccessRuleResource::collection(LibraryAccessRule::query()->orderBy('partner_tier')->paginate(50));
    }

    public function store(Request $request): LibraryAccessRuleResource
    {
        return LibraryAccessRuleResource::make(LibraryAccessRule::query()->create($this->validated($request) + ['updated_by' => $request->user()->id]));
    }

    public function update(Request $request, LibraryAccessRule $libraryAccessRule): LibraryAccessRuleResource
    {
        $libraryAccessRule->fill($this->validated($request) + ['updated_by' => $request->user()->id])->save();

        return LibraryAccessRuleResource::make($libraryAccessRule->refresh());
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'partner_tier' => ['sometimes', 'in:gold,diamond,platinum'],
            'can_view' => ['sometimes', 'boolean'],
            'can_download' => ['sometimes', 'boolean'],
            'can_copy_paste' => ['sometimes', 'boolean'],
            'requires_watermark' => ['sometimes', 'boolean'],
            'max_downloads_per_month' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
