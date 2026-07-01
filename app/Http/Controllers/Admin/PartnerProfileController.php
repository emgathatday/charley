<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Models\PartnerProfile;
use App\Models\PlantType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PartnerProfileController extends Controller
{
    public function index(): View
    {
        return view('admin.partner-profiles.index', [
            'partnerProfiles' => PartnerProfile::query()
                ->with(['plantType', 'products', 'presentations', 'members'])
                ->withCount(['products', 'presentations', 'members'])
                ->when(request('approval_status'), fn ($query, $status) => $query->where('approval_status', $status))
                ->when(request('plant_type_id'), fn ($query, $plantTypeId) => $query->where('plant_type_id', $plantTypeId))
                ->when(request('search'), function ($query, $search): void {
                    $query->where('company_name', 'like', '%'.$search.'%');
                })
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'plantTypes' => PlantType::query()->sorted()->get(),
        ]);
    }

    public function show(PartnerProfile $partnerProfile): View
    {
        return view('admin.partner-profiles.show', [
            'partnerProfile' => $partnerProfile->load(['plantType', 'products', 'presentations', 'members.user']),
        ]);
    }

    public function create(): View
    {
        return view('admin.partner-profiles.create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        PartnerProfile::query()->create($this->validatedPartnerProfile($request));

        return redirect()
            ->route('admin.dashboard.partner-profiles.index')
            ->with('status', 'Partner profile created.');
    }

    public function edit(PartnerProfile $partnerProfile): View
    {
        return view('admin.partner-profiles.edit', [
            'partnerProfile' => $partnerProfile,
            ...$this->formOptions($partnerProfile),
        ]);
    }

    public function update(Request $request, PartnerProfile $partnerProfile): RedirectResponse
    {
        $partnerProfile->fill($this->validatedPartnerProfile($request, $partnerProfile));
        $partnerProfile->save();

        return redirect()
            ->route('admin.dashboard.partner-profiles.edit', $partnerProfile)
            ->with('status', 'Partner profile updated.');
    }

    public function approve(PartnerProfile $partnerProfile): RedirectResponse
    {
        $partnerProfile->fill([
            'approval_status' => 'approved',
            'verified_at' => $partnerProfile->verified_at ?? now(),
        ]);
        $partnerProfile->save();

        return redirect()
            ->route('admin.dashboard.partner-profiles.show', $partnerProfile)
            ->with('status', 'Partner profile approved.');
    }

    public function reject(PartnerProfile $partnerProfile): RedirectResponse
    {
        $partnerProfile->fill([
            'approval_status' => 'rejected',
            'verified_at' => null,
        ]);
        $partnerProfile->save();

        return redirect()
            ->route('admin.dashboard.partner-profiles.show', $partnerProfile)
            ->with('status', 'Partner profile rejected.');
    }

    private function formOptions(?PartnerProfile $partnerProfile = null): array
    {
        return [
            'users' => User::query()
                ->when(
                    $partnerProfile === null,
                    fn ($query) => $query->whereNotIn('id', PartnerProfile::query()->select('user_id')),
                    fn ($query) => $query->where(function ($query) use ($partnerProfile): void {
                        $query->where('id', $partnerProfile->user_id)
                            ->orWhereNotIn('id', PartnerProfile::query()->select('user_id'));
                    })
                )
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->orderBy('email')
                ->get(['id', 'username', 'first_name', 'last_name', 'email']),
            'plantTypes' => PlantType::query()->sorted()->get(['id', 'name']),
            'mediaFiles' => MediaFile::query()->latest()->limit(50)->get(['id', 'original_name']),
        ];
    }

    private function validatedPartnerProfile(Request $request, ?PartnerProfile $partnerProfile = null): array
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id', Rule::unique('partner_profiles', 'user_id')->ignore($partnerProfile?->id)],
            'company_name' => ['required', 'string', 'max:255'],
            'logo_media_id' => ['nullable', 'integer', 'exists:media_files,id'],
            'overview' => ['nullable', 'string'],
            'partner_tier' => ['nullable', 'in:gold,diamond,platinum'],
            'plant_type_id' => ['nullable', 'integer', 'exists:plant_types,id'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'layout_template' => ['required', 'in:layout_1,layout_2,layout_3'],
            'feed_highlight_enabled' => ['required', 'boolean'],
            'approval_status' => ['required', 'in:pending,approved,rejected,suspended'],
        ]);

        $validated['verified_at'] = $validated['approval_status'] === 'approved'
            ? ($partnerProfile?->verified_at ?? now())
            : null;

        return $validated;
    }
}
