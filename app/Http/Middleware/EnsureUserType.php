<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserType
{
    /**
     * Ensure the authenticated user matches one of the allowed user_type values.
     * Also check if user is disabled and block access if so.
     */
    public function handle(Request $request, Closure $next, string ...$types): Response
    {
        $user = $request->user();

        $allowed = array_map('intval', $types);
        $current = (int) optional($user)->user_type;

        if (! $user || ! in_array($current, $allowed, true)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        // Check if user is disabled - block access and logout
        if ($user->disabled) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('login')->with('status', 'Your account has been disabled. Please contact an administrator.');
        }

        return $next($request);
    }
}


