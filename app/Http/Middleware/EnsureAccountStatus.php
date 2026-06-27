<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountStatus
{
    public function handle(Request $request, Closure $next, string $status = 'active'): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Authentication required.');
        }

        if ($user->status !== $status) {
            abort(403, 'Account status does not allow this action.');
        }

        return $next($request);
    }
}
