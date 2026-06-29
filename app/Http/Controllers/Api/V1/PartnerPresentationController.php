<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PartnerPresentationRequest;
use App\Http\Resources\PartnerPresentationResource;
use App\Models\PartnerPresentation;
use App\Models\PartnerProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PartnerPresentationController extends Controller
{
    public function index(Request $request, PartnerProfile $partnerProfile): AnonymousResourceCollection
    {
        $query = $partnerProfile->presentations()->latest();

        if ($request->user()->role !== 'admin') {
            $query->approved();
        }

        return PartnerPresentationResource::collection($query->paginate(20));
    }

    public function store(PartnerPresentationRequest $request, PartnerProfile $partnerProfile): PartnerPresentationResource
    {
        return PartnerPresentationResource::make($partnerProfile->presentations()->create($request->validated()));
    }

    public function show(PartnerProfile $partnerProfile, PartnerPresentation $partnerPresentation): PartnerPresentationResource
    {
        $this->ensureBelongsToPartner($partnerProfile, $partnerPresentation);

        return PartnerPresentationResource::make($partnerPresentation);
    }

    public function update(PartnerPresentationRequest $request, PartnerProfile $partnerProfile, PartnerPresentation $partnerPresentation): PartnerPresentationResource
    {
        $this->ensureBelongsToPartner($partnerProfile, $partnerPresentation);
        $partnerPresentation->fill($request->validated());
        $partnerPresentation->save();

        return PartnerPresentationResource::make($partnerPresentation);
    }

    public function destroy(Request $request, PartnerProfile $partnerProfile, PartnerPresentation $partnerPresentation): Response
    {
        if ($request->user()?->role !== 'admin') {
            abort(Response::HTTP_FORBIDDEN);
        }

        $this->ensureBelongsToPartner($partnerProfile, $partnerPresentation);
        $partnerPresentation->delete();

        return response()->noContent();
    }

    private function ensureBelongsToPartner(PartnerProfile $partnerProfile, PartnerPresentation $partnerPresentation): void
    {
        if ((int) $partnerPresentation->partner_id !== (int) $partnerProfile->id) {
            abort(Response::HTTP_NOT_FOUND);
        }
    }
}
