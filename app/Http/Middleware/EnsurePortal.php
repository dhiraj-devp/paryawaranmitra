<?php

namespace App\Http\Middleware;

use App\Enums\Portal;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortal
{
    public function handle(Request $request, Closure $next, string $portal): Response
    {
        $expected = Portal::from($portal);
        $user = $request->user($expected->value);

        if (! $user instanceof User || $user->portal !== $expected || ! $user->is_active) {
            Auth::guard($expected->value)->logout();

            abort(403);
        }

        Auth::shouldUse($expected->value);

        return $next($request);
    }
}
