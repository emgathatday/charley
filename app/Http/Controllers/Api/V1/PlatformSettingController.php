<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminPlatformSettingRequest;
use App\Http\Resources\PlatformSettingResource;
use App\Models\PlatformSetting;
use App\Services\Admin\PlatformSettingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlatformSettingController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeAdmin($request);

        return PlatformSettingResource::collection(PlatformSetting::query()->orderBy('group')->orderBy('key')->paginate($request->integer('per_page', 15)));
    }

    public function store(AdminPlatformSettingRequest $request, PlatformSettingService $service): PlatformSettingResource
    {
        return new PlatformSettingResource($service->set(...$request->safe()->only(['key', 'value', 'group', 'description'])));
    }

    public function show(Request $request, PlatformSetting $platformSetting): PlatformSettingResource
    {
        $this->authorizeAdmin($request);

        return new PlatformSettingResource($platformSetting);
    }

    public function update(AdminPlatformSettingRequest $request, PlatformSetting $platformSetting, PlatformSettingService $service): PlatformSettingResource
    {
        $data = $request->validated();
        $platformSetting->update([
            'key' => $data['key'],
            'value' => $data['value'],
            'group' => $data['group'],
            'description' => $data['description'] ?? null,
        ]);

        return new PlatformSettingResource($platformSetting);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403);
    }
}
