<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LibraryAccessRuleRequest;
use App\Http\Requests\LibraryAdminRequest;
use App\Http\Resources\LibraryAccessRuleResource;
use App\Models\LibraryAccessRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class LibraryAccessRuleController extends Controller
{
    public function index(LibraryAdminRequest $request): AnonymousResourceCollection
    {
        return LibraryAccessRuleResource::collection(
            LibraryAccessRule::query()->orderBy('partner_tier')->get(),
        );
    }

    public function store(LibraryAccessRuleRequest $request): JsonResponse
    {
        $data = $this->ruleData($request);
        $data['updated_by'] = $request->user()?->id;

        return (new LibraryAccessRuleResource(
            LibraryAccessRule::query()->updateOrCreate(['partner_tier' => $data['partner_tier']], $data),
        ))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(LibraryAccessRuleRequest $request, LibraryAccessRule $libraryAccessRule): LibraryAccessRuleResource
    {
        $data = $this->ruleData($request);
        $data['updated_by'] = $request->user()?->id;
        $libraryAccessRule->update($data);

        return new LibraryAccessRuleResource($libraryAccessRule->refresh());
    }

    private function ruleData(LibraryAccessRuleRequest $request): array
    {
        $validated = $request->validated();

        return [
            'partner_tier' => $validated['partner_tier'],
            'can_view' => $request->boolean('can_view'),
            'can_download' => $request->boolean('can_download'),
            'can_copy_paste' => $request->boolean('can_copy_paste'),
            'requires_watermark' => $request->boolean('requires_watermark', true),
            'max_downloads_per_month' => $validated['max_downloads_per_month'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];
    }
}
