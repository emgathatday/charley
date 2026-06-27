<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IamUserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->withCount('verificationRequests')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = (string) $request->string('search');

                $query->where(function ($query) use ($search): void {
                    $query->where('username', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', (string) $request->string('role')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->string('status')))
            ->when($request->filled('verified'), fn ($query) => $query->where('is_verified', $request->boolean('verified')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total_users' => User::count(),
            'verified_professionals' => User::where('role', 'professional')->where('is_verified', true)->count(),
            'pending_reviews' => VerificationRequest::where('status', 'pending')->count(),
            'security_flags' => User::where(function ($query): void {
                $query->where('status', '!=', 'active')
                    ->orWhere('login_attempts', '>', 0)
                    ->orWhereNotNull('locked_until')
                    ->orWhereNotNull('self_frozen_at');
            })->count(),
        ];

        return view('iam.users', [
            'users' => $users,
            'stats' => $stats,
            'filters' => $request->only(['search', 'role', 'status', 'verified']),
        ]);
    }
}

