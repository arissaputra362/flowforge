<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = auth()->user();

        \Log::debug("Check Roler Middlewware");
        \Log::debug(json_encode($user));
        \Log::debug($user->role);

        if (!$user) {
            abort(401);
        }

        // cek apakah role user termasuk yang diizinkan
        if (!$user->hasAnyRole($roles)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
