<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PartnerMemberRequest;
use App\Http\Resources\PartnerMemberResource;
use App\Models\PartnerMember;
use App\Models\PartnerProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PartnerMemberController extends Controller
{
    public function index(PartnerProfile $partnerProfile): AnonymousResourceCollection
    {
        return PartnerMemberResource::collection($partnerProfile->members()->latest('joined_at')->paginate(20));
    }

    public function store(PartnerMemberRequest $request, PartnerProfile $partnerProfile): PartnerMemberResource
    {
        return PartnerMemberResource::make($partnerProfile->members()->create($request->validated()));
    }

    public function show(PartnerProfile $partnerProfile, PartnerMember $partnerMember): PartnerMemberResource
    {
        $this->ensureBelongsToPartner($partnerProfile, $partnerMember);

        return PartnerMemberResource::make($partnerMember);
    }

    public function update(PartnerMemberRequest $request, PartnerProfile $partnerProfile, PartnerMember $partnerMember): PartnerMemberResource
    {
        $this->ensureBelongsToPartner($partnerProfile, $partnerMember);
        $partnerMember->fill($request->validated());
        $partnerMember->save();

        return PartnerMemberResource::make($partnerMember);
    }

    public function destroy(Request $request, PartnerProfile $partnerProfile, PartnerMember $partnerMember): Response
    {
        if ($request->user()?->role !== 'admin') {
            abort(Response::HTTP_FORBIDDEN);
        }

        $this->ensureBelongsToPartner($partnerProfile, $partnerMember);
        $partnerMember->delete();

        return response()->noContent();
    }

    private function ensureBelongsToPartner(PartnerProfile $partnerProfile, PartnerMember $partnerMember): void
    {
        if ((int) $partnerMember->partner_id !== (int) $partnerProfile->id) {
            abort(Response::HTTP_NOT_FOUND);
        }
    }
}
