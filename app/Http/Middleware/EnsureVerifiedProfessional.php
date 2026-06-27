<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVerifiedProfessional
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Authentication required.');
        }

        if ($user->role !== 'professional' || ! $user->is_verified) {
            abort(403, 'Verified professional access is required.');
        }

        return $next($request);
    }
}
