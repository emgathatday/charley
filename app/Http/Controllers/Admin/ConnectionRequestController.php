<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewConnectionRequestRequest;
use App\Models\ConnectionRequest;
use App\Services\PartnerConnectionRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ConnectionRequestController extends Controller
{
    public function __construct(private readonly PartnerConnectionRequestService $connectionRequestService) {}

    public function index(Request $request): View
    {
        $connectionRequests = ConnectionRequest::query()
            ->with(['requester', 'targetUser', 'reviewer', 'connection'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.profiles.connection-requests.index', [
            'connectionRequests' => $connectionRequests,
            'stats' => [
                'total' => ConnectionRequest::query()->count(),
                'pending' => ConnectionRequest::query()->where('status', 'pending')->count(),
                'approved' => ConnectionRequest::query()->where('status', 'approved')->count(),
                'closed' => ConnectionRequest::query()->whereIn('status', ['rejected', 'cancelled'])->count(),
            ],
        ]);
    }

    public function approve(ReviewConnectionRequestRequest $request, ConnectionRequest $connectionRequest): RedirectResponse
    {
        try {
            $this->connectionRequestService->approve(
                $connectionRequest,
                $request->user(),
                $request->validated('admin_note')
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['connection_request' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.dashboard.profiles.connection-requests.index')
            ->with('status', 'Connection request approved and pending connection created.');
    }

    public function reject(ReviewConnectionRequestRequest $request, ConnectionRequest $connectionRequest): RedirectResponse
    {
        try {
            $this->connectionRequestService->reject(
                $connectionRequest,
                $request->user(),
                $request->validated('admin_note')
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['connection_request' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.dashboard.profiles.connection-requests.index')
            ->with('status', 'Connection request rejected.');
    }
}
