<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    /**
     * Show the password change page.
     */
    public function show(Request $request)
    {
        // If accessing directly (not from password verification redirect), start fresh
        $previousUrl = url()->previous();
        $isFromVerify = $previousUrl && str_contains($previousUrl, route('password.verify', [], false));
        
        if (!$isFromVerify && !$request->session()->has('password_changed')) {
            $request->session()->forget('password_verified');
        }
        
        return view('auth.change-password');
    }

    /**
     * Verify the current password before allowing password change.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
        ]);

        $user = Auth::user();
        $key = 'change-password:' . $user->id;

        // Check current password manually before validation
        if (!Hash::check($request->current_password, $user->password)) {
            // Wrong password - apply rate limiting
            $executed = RateLimiter::attempt(
                $key,
                $perMinute = 5, // 5 attempts per minute
                function () {
                    // This callback is only executed if rate limit is not exceeded
                }
            );

            if (!$executed) {
                $seconds = RateLimiter::availableIn($key);
                $request->merge(['current_password' => '']);
                throw ValidationException::withMessages([
                    'current_password' => "Too many incorrect password attempts. Please try again in {$seconds} seconds.",
                ]);
            }

            // Password is wrong - throw validation error
            $request->merge(['current_password' => '']);
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        // Current password is correct - clear rate limiter
        RateLimiter::clear($key);

        return redirect()->route('password.change')->with('password_verified', true);
    }

    /**
     * Update the user's password.
     * Follows NIST 800-63B guidelines:
     * - Minimum 8 characters
     * - No complexity requirements
     * - Check against common/breached passwords
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $key = 'change-password:' . $user->id;

        // Validate required fields first
        $request->validate([
            'current_password' => ['required'],
            'password' => ['required'],
            'password_confirmation' => ['required'],
        ]);

        // Check current password manually before validation
        if (!Hash::check($request->current_password, $user->password)) {
            // Wrong password - apply rate limiting
            $executed = RateLimiter::attempt(
                $key,
                $perMinute = 5, // 5 attempts per minute
                function () {
                    // This callback is only executed if rate limit is not exceeded
                }
            );

            if (!$executed) {
                $seconds = RateLimiter::availableIn($key);
                $request->merge(['current_password' => '']);
                throw ValidationException::withMessages([
                    'current_password' => "Too many incorrect password attempts. Please try again in {$seconds} seconds.",
                ]);
            }

            // Password is wrong - throw validation error
            $request->merge(['current_password' => '']);
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        // Current password is correct - clear rate limiter and proceed with new password validation
        RateLimiter::clear($key);

        // Validate new password (no rate limiting for validation errors)
        $request->validate([
            'password' => [
                'required',
                'confirmed',
                Password::min(8) // NIST: Minimum 8 characters
                    ->uncompromised(), // Check against breached passwords
            ],
        ], [
            'password.confirmed' => 'The password confirmation does not match.',
            'password.uncompromised' => 'This password has been found in a data breach. Please choose a different password.',
        ]);

        // Update password
        $user->password = $request->password;
        $user->save();

        return redirect()->route('password.change')->with('password_changed', 'Password changed successfully.');
    }
}

