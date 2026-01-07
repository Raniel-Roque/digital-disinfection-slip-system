<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class CustomThrottleRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $maxAttempts = 5, $decayMinutes = 15): Response
    {
        $key = 'login:' . $request->ip();

        // Check if rate limit is exceeded
        $executed = RateLimiter::attempt(
            $key,
            $maxAttempts,
            function () use ($next, $request) {
                // Rate limit not exceeded, proceed with request
        return $next($request);
            },
            $decayMinutes * 60 // Convert minutes to seconds
        );

        if (!$executed) {
            // Rate limit exceeded, get wait time and throw ValidationException
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);

            throw ValidationException::withMessages([
                'username' => "Too many login attempts. Please wait {$minutes} minute(s) before trying again.",
                'password' => '',
            ]);
        }

        // Return the response from the closure
        return $executed;
    }
}
