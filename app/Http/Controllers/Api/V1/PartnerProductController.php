<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PartnerProductRequest;
use App\Http\Resources\PartnerProductResource;
use App\Models\PartnerProduct;
use App\Models\PartnerProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PartnerProductController extends Controller
{
    public function index(Request $request, PartnerProfile $partnerProfile): AnonymousResourceCollection
    {
        $query = $partnerProfile->products()->latest();

        if ($request->user()->role !== 'admin') {
            $query->active();
        }

        return PartnerProductResource::collection($query->paginate(20));
    }

    public function store(PartnerProductRequest $request, PartnerProfile $partnerProfile): PartnerProductResource
    {
        return PartnerProductResource::make($partnerProfile->products()->create($request->validated()));
    }

    public function show(PartnerProfile $partnerProfile, PartnerProduct $partnerProduct): PartnerProductResource
    {
        $this->ensureBelongsToPartner($partnerProfile, $partnerProduct);

        return PartnerProductResource::make($partnerProduct);
    }

    public function update(PartnerProductRequest $request, PartnerProfile $partnerProfile, PartnerProduct $partnerProduct): PartnerProductResource
    {
        $this->ensureBelongsToPartner($partnerProfile, $partnerProduct);
        $partnerProduct->fill($request->validated());
        $partnerProduct->save();

        return PartnerProductResource::make($partnerProduct);
    }

    public function destroy(Request $request, PartnerProfile $partnerProfile, PartnerProduct $partnerProduct): Response
    {
        if ($request->user()?->role !== 'admin') {
            abort(Response::HTTP_FORBIDDEN);
        }

        $this->ensureBelongsToPartner($partnerProfile, $partnerProduct);
        $partnerProduct->delete();

        return response()->noContent();
    }

    private function ensureBelongsToPartner(PartnerProfile $partnerProfile, PartnerProduct $partnerProduct): void
    {
        if ((int) $partnerProduct->partner_id !== (int) $partnerProfile->id) {
            abort(Response::HTTP_NOT_FOUND);
        }
    }
}
