<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // Check if user is logged in and has is_admin flag
        if (! $request->user() || ! $request->user()->is_admin) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}