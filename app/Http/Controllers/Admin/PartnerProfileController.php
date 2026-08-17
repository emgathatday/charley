<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Models\PartnerProfile;
use App\Models\PlantType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PartnerProfileController extends Controller
{
    public function index(): View
    {
        return view('admin.partner-profiles.index', [
            'stats' => $this->dashboardStats(),
            'partnerProfiles' => PartnerProfile::query()
                ->with(['plantTypes', 'logoMedia', 'activePartnerSubscription.tier'])
                ->withCount(['products', 'presentations', 'members'])
                ->when(request('approval_status'), fn ($query, $status) => $query->where('approval_status', $status))
                ->when(request('plant_type_id'), fn ($query, $plantTypeId) => $query->forPlantType((int) $plantTypeId))
                ->when(request('company_type'), fn ($query, $companyType) => $query->where('company_type', $companyType))
                ->when(request('search'), function ($query, $search): void {
                    $query->where(function ($query) use ($search): void {
                        $query->where('company_name', 'like', '%'.$search.'%')
                            ->orWhere('company_type', 'like', '%'.$search.'%')
                            ->orWhere('country', 'like', '%'.$search.'%');
                    });
                })
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'plantTypes' => PlantType::query()->sorted()->get(),
            'companyTypes' => $this->companyTypes(),
        ]);
    }

    public function show(PartnerProfile $partnerProfile): View
    {
        return view('admin.partner-profiles.show', [
            'partnerProfile' => $partnerProfile->load([
                'logoMedia',
                'activePartnerSubscription.tier',
                'plantTypes',
                'products.imageMedia',
                'products.datasheetMedia',
                'presentations.plantType',
                'presentations.fileMedia',
                'members.user',
            ]),
        ]);
    }

    public function verification(PartnerProfile $partnerProfile): View
    {
        return view('admin.partner-profiles.verification-detail', [
            'partnerProfile' => $partnerProfile->load(['user', 'logoMedia', 'plantTypes', 'products.imageMedia', 'products.datasheetMedia', 'presentations.plantType', 'presentations.fileMedia', 'members.user']),
        ]);
    }

    public function create(): View
    {
        return view('admin.partner-profiles.create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $validated = $this->validatedPartnerProfile($request);
            $partnerProfile = PartnerProfile::query()->create($this->profilePayload($validated));
            $this->syncPlantTypes($partnerProfile, $validated);
        });

        return redirect()
            ->route('admin.dashboard.partner-profiles.index')
            ->with('status', 'Partner profile created. TODO: wire media preview uploads after media picker contract is finalized.');
    }

    public function edit(PartnerProfile $partnerProfile): View
    {
        return view('admin.partner-profiles.edit', [
            'partnerProfile' => $partnerProfile->load(['plantTypes', 'logoMedia']),
            ...$this->formOptions($partnerProfile),
        ]);
    }

    public function update(Request $request, PartnerProfile $partnerProfile): RedirectResponse
    {
        DB::transaction(function () use ($request, $partnerProfile): void {
            $validated = $this->validatedPartnerProfile($request, $partnerProfile);
            $partnerProfile->fill($this->profilePayload($validated));
            $partnerProfile->save();
            $this->syncPlantTypes($partnerProfile, $validated);
        });

        return redirect()
            ->route('admin.dashboard.partner-profiles.edit', $partnerProfile)
            ->with('status', 'Partner profile updated.');
    }

    public function destroy(PartnerProfile $partnerProfile): RedirectResponse
    {
        $partnerProfile->delete();

        return redirect()
            ->route('admin.dashboard.partner-profiles.index')
            ->with('status', 'Partner profile deleted.');
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
            ->route('admin.dashboard.partner-profiles.verification', $partnerProfile)
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
            'mediaFiles' => MediaFile::query()->latest()->limit(50)->get(['id', 'original_name', 'file_category']),
            'companyTypes' => $this->companyTypes(),
        ];
    }

    private function validatedPartnerProfile(Request $request, ?PartnerProfile $partnerProfile = null): array
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id', Rule::unique('partner_profiles', 'user_id')->ignore($partnerProfile?->id)],
            'company_name' => ['required', 'string', 'max:255'],
            'logo_media_id' => ['nullable', 'integer', 'exists:media_files,id'],
            'overview' => ['nullable', 'string'],
            'partner_tier' => ['nullable', 'in:free,silver,gold,platinum,diamond'],
            'plant_type_id' => ['nullable', 'integer', 'exists:plant_types,id'],
            'company_type' => ['nullable', 'string', 'max:255'],
            'plant_type_ids' => ['nullable', 'array'],
            'plant_type_ids.*' => ['integer', 'exists:plant_types,id'],
            'primary_plant_type_id' => ['nullable', 'integer', 'exists:plant_types,id'],
            'keywords' => ['nullable', 'array'],
            'keywords.*' => ['nullable', 'string', 'max:255'],
            'references' => ['nullable', 'array'],
            'references.*.project' => ['nullable', 'string', 'max:255'],
            'references.*.year' => ['nullable', 'integer', 'min:1800', 'max:'.now()->year],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'layout_template' => ['required', 'in:layout_1,layout_2,layout_3'],
            'feed_highlight_enabled' => ['required', 'boolean'],
            'approval_status' => ['required', 'in:pending,approved,rejected,suspended'],
        ]);

        $validated['keywords'] = collect($validated['keywords'] ?? [])
            ->filter(fn (?string $keyword): bool => filled($keyword))
            ->values()
            ->all();
        $validated['references'] = collect($validated['references'] ?? [])
            ->filter(fn (array $reference): bool => filled($reference['project'] ?? null) || filled($reference['year'] ?? null))
            ->values()
            ->all();
        $validated['verified_at'] = $validated['approval_status'] === 'approved'
            ? ($partnerProfile?->verified_at ?? now())
            : null;

        return $validated;
    }

    private function profilePayload(array $validated): array
    {
        return collect($validated)->only([
            'user_id',
            'company_name',
            'logo_media_id',
            'overview',
            'partner_tier',
            'plant_type_id',
            'company_type',
            'keywords',
            'references',
            'contact_email',
            'phone',
            'country',
            'website',
            'layout_template',
            'feed_highlight_enabled',
            'approval_status',
            'verified_at',
        ])->all();
    }

    private function syncPlantTypes(PartnerProfile $partnerProfile, array $validated): void
    {
        $plantTypeIds = collect($validated['plant_type_ids'] ?? [])
            ->map(fn ($plantTypeId): int => (int) $plantTypeId)
            ->unique()
            ->values();
        if (isset($validated['plant_type_id'])) {
            $plantTypeIds->prepend((int) $validated['plant_type_id']);
            $plantTypeIds = $plantTypeIds->unique()->values();
        }

        $primaryPlantTypeId = isset($validated['primary_plant_type_id'])
            ? (int) $validated['primary_plant_type_id']
            : (isset($validated['plant_type_id']) ? (int) $validated['plant_type_id'] : null);

        if ($primaryPlantTypeId && ! $plantTypeIds->contains($primaryPlantTypeId)) {
            $plantTypeIds->prepend($primaryPlantTypeId);
        }

        $partnerProfile->forceFill(['plant_type_id' => $primaryPlantTypeId])->save();

        $partnerProfile->plantTypes()->sync(
            $plantTypeIds->mapWithKeys(fn (int $plantTypeId, int $index): array => [
                $plantTypeId => [
                    'is_primary' => $primaryPlantTypeId ? $plantTypeId === $primaryPlantTypeId : $index === 0,
                    'sort_order' => $index,
                ],
            ])->all()
        );
    }

    private function dashboardStats(): array
    {
        return [
            'total' => PartnerProfile::query()->count(),
            'approved' => PartnerProfile::query()->where('approval_status', 'approved')->count(),
            'pending' => PartnerProfile::query()->where('approval_status', 'pending')->count(),
            'plant_pivot_links' => DB::table('partner_profile_plant_type')->count(),
        ];
    }

    private function companyTypes(): array
    {
        $existingTypes = PartnerProfile::query()
            ->whereNotNull('company_type')
            ->distinct()
            ->orderBy('company_type')
            ->pluck('company_type')
            ->all();

        return collect(['Licensor', 'Vendor', 'Catalyst supplier', 'Service provider', 'Manufacturing partner'])
            ->merge($existingTypes)
            ->unique()
            ->values()
            ->all();
    }
}
