<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Iam\RevokeAdminProfileSessionAction;
use App\Actions\Iam\RevokeOtherAdminProfileSessionsAction;
use App\Actions\Iam\StoreEngineerAction;
use App\Actions\Iam\StorePartnerAction;
use App\Actions\Iam\UpdateEngineerAction;
use App\Actions\Iam\UpdatePartnerAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\AdministratorManagementViewDataService;
use App\Services\Admin\AdminProfileViewDataService;
use App\Services\Admin\CreateEngineerViewDataService;
use App\Services\Admin\CreatePartnerViewDataService;
use App\Services\Admin\EditEngineerViewDataService;
use App\Services\Admin\EditPartnerViewDataService;
use App\Services\Admin\EngineerManagementViewDataService;
use App\Services\Admin\IamUserDetailViewDataService;
use App\Services\Admin\PartnerManagementViewDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IamUserController extends Controller
{
    public function index(Request $request, AdministratorManagementViewDataService $viewData): View
    {
        return view('iam.users', $viewData->data($request->query()));
    }

    public function show(User $user, IamUserDetailViewDataService $viewData): View
    {
        $payload = $viewData->payload($user);

        return view($payload['view'], $payload['data']);
    }

    public function engineers(Request $request, EngineerManagementViewDataService $viewData): View
    {
        return view('iam.users.engineers', $viewData->data($request->query()));
    }

    public function partners(Request $request, PartnerManagementViewDataService $viewData): View
    {
        return view('iam.users.partners', $viewData->data($request->query()));
    }

    public function createPartner(CreatePartnerViewDataService $viewData): View
    {
        return view('iam.users.create-partner', $viewData->data(old('keywords')));
    }

    public function storePartner(Request $request, StorePartnerAction $storePartner): RedirectResponse
    {
        $data = $request->validate($storePartner->rules());
        $data['activate_account'] = $request->boolean('activate_account');
        $data['require_email_verification'] = $request->boolean('require_email_verification');
        $result = $storePartner->execute($data, $request->file('logo_file'), $request->user()?->id);

        return redirect()->route('admin.dashboard.iam.users.show', $result->user)->with('status', 'Partner account created.')->with('subscription_id', $result->subscription?->id);
    }

    public function createEngineer(CreateEngineerViewDataService $viewData): View
    {
        return view('iam.users.create-engineer', $viewData->data());
    }

    public function createAdmin(): View
    {
        return view('iam.users.create-admin');
    }

    public function editEngineer(User $user, EditEngineerViewDataService $viewData): View
    {
        if (! in_array($user->role, ['professional', 'unverified_member'], true)) {
            abort(404);
        }

        return view('iam.users.edit-engineer', $viewData->data($user));
    }

    public function editPartner(User $user, EditPartnerViewDataService $viewData): View
    {
        if ($user->role !== 'partner') {
            abort(404);
        }

        return view('iam.users.edit-partner', $viewData->data($user));
    }

    public function adminProfile(Request $request, AdminProfileViewDataService $viewData): View
    {
        return view('iam.users.admin-profile', $viewData->data($request->user(), (string) $request->session()->getId()));
    }

    public function revokeAdminProfileSession(
        Request $request,
        string $session,
        AdminProfileViewDataService $viewData,
        RevokeAdminProfileSessionAction $revokeSession
    ): RedirectResponse {
        $revokeSession->execute(
            $request->user(),
            $session,
            $viewData->sessionIdToPreserve($request->user(), (string) $request->session()->getId())
        );

        return redirect()
            ->route('admin.dashboard.iam.users.admin-profile', ['section' => 'sessions'])
            ->with('status', 'Session revoked.');
    }

    public function revokeOtherAdminProfileSessions(
        Request $request,
        AdminProfileViewDataService $viewData,
        RevokeOtherAdminProfileSessionsAction $revokeOtherSessions
    ): RedirectResponse {
        $revokeOtherSessions->execute(
            $request->user(),
            $viewData->sessionIdToPreserve($request->user(), (string) $request->session()->getId())
        );

        return redirect()
            ->route('admin.dashboard.iam.users.admin-profile', ['section' => 'sessions'])
            ->with('status', 'Other sessions revoked.');
    }

    public function storeEngineer(Request $request, StoreEngineerAction $storeEngineer): RedirectResponse
    {
        $data = $request->validate($storeEngineer->rules());
        $data['verification_intent'] = $request->boolean('verification_intent');
        $result = $storeEngineer->execute($data);

        return redirect()->route('admin.dashboard.iam.users.show', $result->user)->with('status', 'Engineer account created.');
    }

    public function updateEngineer(Request $request, User $user, UpdateEngineerAction $updateEngineer): RedirectResponse
    {
        if (! in_array($user->role, ['professional', 'unverified_member'], true)) {
            abort(404);
        }

        $data = $request->validate($updateEngineer->rules($user));
        $data['is_discoverable'] = $request->boolean('is_discoverable');
        $result = $updateEngineer->execute($data, $user, $request->file('profile_photo'), $request->user()?->id);

        return redirect()->route('admin.dashboard.iam.users.show', $result->user)->with('status', 'Engineer profile updated.');
    }

    public function updatePartner(Request $request, User $user, UpdatePartnerAction $updatePartner): RedirectResponse
    {
        if ($user->role !== 'partner') {
            abort(404);
        }

        $data = $request->validate($updatePartner->rules($user));
        $data['feed_highlight_enabled'] = $request->boolean('feed_highlight_enabled');
        $result = $updatePartner->execute($data, $user, $request->file('logo_file'), $request->user()?->id);

        return redirect()->route('admin.dashboard.iam.users.show', $result->user)->with('status', 'Partner profile updated.');
    }

    public function storeAdmin(Request $request): RedirectResponse
    {
        return redirect()->route('admin.dashboard.iam.users');
    }
}
