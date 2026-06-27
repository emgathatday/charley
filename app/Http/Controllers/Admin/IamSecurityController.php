<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IamSecurityController extends Controller
{
    public function show(?User $user = null): View
    {
        $user ??= User::query()->latest()->firstOrFail();
        $user->load(['verificationRequests' => fn ($query) => $query->latest()->limit(5)]);

        return view('iam.user-security', [
            'user' => $user,
            'latestVerification' => $user->verificationRequests->first(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(['admin', 'unverified_member', 'professional', 'partner'])],
            'status' => ['required', Rule::in(['active', 'suspended', 'frozen'])],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $user->forceFill([
            'role' => $validated['role'],
            'status' => $validated['status'],
            'self_frozen_at' => $validated['status'] === 'frozen' ? ($user->self_frozen_at ?? now()) : null,
        ])->save();

        return back()->with('status', 'Account security controls updated.');
    }
}

