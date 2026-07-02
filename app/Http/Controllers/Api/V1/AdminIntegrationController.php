<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminIntegrationRequest;
use App\Http\Resources\AdminIntegrationResource;
use App\Models\AdminIntegration;
use App\Models\User;
use App\Services\Admin\AdminIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminIntegrationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeAdmin($request);

        return AdminIntegrationResource::collection(AdminIntegration::query()->latest()->paginate($request->integer('per_page', 15)));
    }

    public function store(AdminIntegrationRequest $request, AdminIntegrationService $service): AdminIntegrationResource
    {
        $admin = $request->filled('user_id') ? User::findOrFail($request->integer('user_id')) : $request->user();

        return new AdminIntegrationResource($service->connect($admin, $request->validated()));
    }

    public function show(Request $request, AdminIntegration $adminIntegration): AdminIntegrationResource
    {
        $this->authorizeAdmin($request);

        return new AdminIntegrationResource($adminIntegration);
    }

    public function destroy(Request $request, AdminIntegration $adminIntegration, AdminIntegrationService $service): AdminIntegrationResource
    {
        $this->authorizeAdmin($request);
        $resource = new AdminIntegrationResource($adminIntegration);
        $service->disconnect($adminIntegration);

        return $resource;
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403);
    }
}
