<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PartnerProfileRequest;
use App\Http\Resources\PartnerProfileResource;
use App\Models\PartnerProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PartnerProfileController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = PartnerProfile::query()
            ->with(['plantType'])
            ->withCount(['products', 'presentations', 'members'])
            ->latest();

        if ($request->user()->role !== 'admin') {
            $query->approved();
        } elseif ($request->filled('approval_status')) {
            $query->where('approval_status', $request->string('approval_status')->toString());
        }

        if ($request->filled('plant_type_id')) {
            $query->where('plant_type_id', (int) $request->integer('plant_type_id'));
        }

        if ($request->filled('partner_tier')) {
            $query->where('partner_tier', $request->string('partner_tier')->toString());
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->toString().'%';
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('company_name', 'like', $search)
                    ->orWhere('overview', 'like', $search)
                    ->orWhere('country', 'like', $search);
            });
        }

        return PartnerProfileResource::collection($query->paginate(20));
    }

    public function store(PartnerProfileRequest $request): PartnerProfileResource
    {
        $partnerProfile = PartnerProfile::query()->create($request->validated());

        return PartnerProfileResource::make($partnerProfile->load(['plantType']));
    }

    public function show(Request $request, PartnerProfile $partnerProfile): PartnerProfileResource
    {
        if ($request->user()->role !== 'admin' && $partnerProfile->approval_status !== 'approved') {
            abort(Response::HTTP_NOT_FOUND);
        }

        return PartnerProfileResource::make(
            $partnerProfile->load(['plantType', 'products', 'presentations', 'members'])
        );
    }

    public function update(PartnerProfileRequest $request, PartnerProfile $partnerProfile): PartnerProfileResource
    {
        $partnerProfile->fill($request->validated());
        $partnerProfile->save();

        return PartnerProfileResource::make($partnerProfile->load(['plantType']));
    }

    public function destroy(Request $request, PartnerProfile $partnerProfile): Response
    {
        $this->ensureAdmin($request);

        $partnerProfile->delete();

        return response()->noContent();
    }

    public function approve(Request $request, PartnerProfile $partnerProfile): PartnerProfileResource
    {
        $this->ensureAdmin($request);

        $partnerProfile->fill([
            'approval_status' => 'approved',
            'verified_at' => $partnerProfile->verified_at ?? now(),
        ]);
        $partnerProfile->save();

        return PartnerProfileResource::make($partnerProfile);
    }

    public function reject(Request $request, PartnerProfile $partnerProfile): PartnerProfileResource
    {
        $this->ensureAdmin($request);

        $partnerProfile->fill([
            'approval_status' => 'rejected',
            'verified_at' => null,
        ]);
        $partnerProfile->save();

        return PartnerProfileResource::make($partnerProfile);
    }

    public function suspend(Request $request, PartnerProfile $partnerProfile): PartnerProfileResource
    {
        $this->ensureAdmin($request);

        $partnerProfile->fill(['approval_status' => 'suspended']);
        $partnerProfile->save();

        return PartnerProfileResource::make($partnerProfile);
    }

    private function ensureAdmin(Request $request): void
    {
        if ($request->user()?->role !== 'admin') {
            abort(Response::HTTP_FORBIDDEN, 'You are not allowed to manage partner profiles.');
        }
    }
}
