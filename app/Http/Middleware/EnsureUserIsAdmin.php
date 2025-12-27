<?php
// app/Http/Middleware/EnsureUserIsAdmin.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsAdmin
{
  public function handle(Request $request, Closure $next)
{
    $user = $request->user();

    // adjust strings to match your stored role values
    $isAdmin = $user && (
        (method_exists($user, 'hasRole') && $user->hasRole('admin')) ||
        (method_exists($user, 'hasRole') && $user->hasRole('administrator')) ||
        ($user->role ?? '') === 'administrator' ||
        ($user->role ?? '') === 'admin'
    );

    if (! $isAdmin) {
        \Log::warning('Unauthorized admin access attempt', [
            'user_id' => optional($user)->id,
            'path' => $request->path(),
            'ip' => $request->ip(),
        ]);
        abort(403, 'Access denied.');
    }

    return $next($request);
}

}
