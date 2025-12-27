<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $request->expectsJson()
                ? abort(401,'Unauthenticated.')
                : redirect()->guest(route('login'));
        }

        // Allow if user has administrator role
        if ($user->hasRole('administrator')) {
            return $next($request);
        }

        // Allow if Gate says they can manage users
        if (Gate::check('manage-users')) {
            return $next($request);
        }

        return $request->expectsJson()
            ? abort(403,'This action is unauthorized.')
            : abort(403,'This action is unauthorized.');
    }
}
