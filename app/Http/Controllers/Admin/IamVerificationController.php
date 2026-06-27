<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VerificationRequest;
use App\Services\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IamVerificationController extends Controller
{
    public function __construct(private readonly VerificationService $verificationService)
    {
    }

    public function index(Request $request): View
    {
        $verificationRequests = VerificationRequest::query()
            ->with(['user', 'reviewer'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->string('status')))
            ->when($request->filled('method'), fn ($query) => $query->where('verification_method', (string) $request->string('method')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = (string) $request->string('search');

                $query->whereHas('user', function ($query) use ($search): void {
                    $query->where('username', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('iam.verification-queue', [
            'verificationRequests' => $verificationRequests,
            'filters' => $request->only(['search', 'status', 'method']),
            'queueStats' => [
                'pending' => VerificationRequest::where('status', 'pending')->count(),
                'more_info_required' => VerificationRequest::where('status', 'more_info_required')->count(),
                'approved' => VerificationRequest::where('status', 'approved')->count(),
                'rejected' => VerificationRequest::where('status', 'rejected')->count(),
            ],
        ]);
    }

    public function approve(Request $request, VerificationRequest $verificationRequest): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->verificationService->approve($verificationRequest, $request->user(), $validated['admin_notes'] ?? null);

        return back()->with('status', 'Verification request approved.');
    }

    public function reject(Request $request, VerificationRequest $verificationRequest): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => ['required', 'string', 'max:1000'],
        ]);

        $this->verificationService->reject($verificationRequest, $request->user(), $validated['admin_notes']);

        return back()->with('status', 'Verification request rejected.');
    }

    public function requestMoreInfo(Request $request, VerificationRequest $verificationRequest): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => ['required', 'string', 'max:1000'],
        ]);

        $verificationRequest->forceFill([
            'status' => 'more_info_required',
            'admin_notes' => $validated['admin_notes'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();

        return back()->with('status', 'More information requested.');
    }
}

