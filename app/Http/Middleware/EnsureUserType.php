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
     * 
     * Special case: Superadmins (type 2) can access user routes (type 0) if they have a location in session.
     */
    public function handle(Request $request, Closure $next, string ...$types): Response
    {
        $user = $request->user();

        $allowed = array_map('intval', $types);
        $current = (int) optional($user)->user_type;

        // Check if user type is allowed
        $isAllowed = in_array($current, $allowed, true);
        
        // Special case: Superadmins (type 2) can access user routes (type 0) if they have a location in session
        if (!$isAllowed && $current === 2 && in_array(0, $allowed, true)) {
            $hasLocation = $request->session()->has('location_id');
            if ($hasLocation) {
                $isAllowed = true;
            }
        }

        if (! $user || ! $isAllowed) {
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


