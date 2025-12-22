<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\Location;
use App\Models\Setting;
use App\Models\User;

class SessionController extends Controller
{
    public function create(Request $request, $location = null)
    {
        // If user is already logged in, redirect to home with flash message
        if (Auth::check()) {
            $user = Auth::user();
            $locationName = $request->session()->get('location_name');
            
            if ($locationName) {
                $message = "You are already logged in at {$locationName}.";
            } else {
                $message = "You are already logged in.";
            }
            
            return redirect('/')->with('status', $message);
        }
        
        $locationModel = null;
        if ($location) {
            $locationModel = Location::findOrFail($location);
            
            // Check if location is disabled - redirect to home with message
            if ($locationModel->disabled) {
                return redirect('/')->with('status', 'This location has been disabled. Please contact an administrator.');
            }
        }
        
        // Get default logo path from settings
        $setting = Setting::where('setting_name', 'default_location_logo')->first();
        $defaultLogoPath = $setting && !empty($setting->value) ? $setting->value : 'images/logo/BGC.png';
        
        return view('auth.login', [
            'location' => $locationModel,
            'defaultLogoPath' => $defaultLogoPath,
        ]);
    }

    public function store(Request $request, $location = null){
            $attributes = request()->validate([
                "username"=> ['required'],
                "password"=> ['required'],
            ]);

            // Trim @ symbol from the beginning of username if present
            $username = ltrim($attributes['username'], '@');

            // Find user by username (case-insensitive) - SoftDeletes automatically excludes deleted users
            $user = User::whereRaw('LOWER(username) = ?', [strtolower($username)])->first();

            // Verify user exists and password is correct
            if (!$user || !Hash::check($attributes['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'username'=> 'Sorry, those credentials are incorrect',
                    'password'=> '',
                ]);
            }

            // Check if user is deleted (explicit check, though SoftDeletes should handle this)
            if ($user->trashed()) {
                throw ValidationException::withMessages([
                    'username' => 'Your account has been deleted. Please contact an administrator.',
                    'password' => '',
                ]);
            }

            // Check if user is disabled BEFORE logging in
            if ($user->disabled) {
                throw ValidationException::withMessages([
                    'username' => 'Your account has been disabled. Please contact an administrator.',
                    'password' => '',
                ]);
            }

            // If location login is being used, validate location and user type
            if ($location) {
                $locationModel = Location::find($location);

                // Check if location exists
                if (!$locationModel) {
                    throw ValidationException::withMessages([
                        'username' => 'This location does not exist.',
                        'password' => '',
                    ]);
                }

                // Check if location is deleted
                if ($locationModel->trashed()) {
                    throw ValidationException::withMessages([
                        'username' => 'This location has been removed. Please contact an administrator.',
                        'password' => '',
                    ]);
                }

                // Check if location is disabled
                if ($locationModel->disabled) {
                    throw ValidationException::withMessages([
                        'username' => 'This location has been disabled. Please contact an administrator.',
                        'password' => '',
                    ]);
                }

                // Only allow regular users (type 0) or superadmins (type 2) for location login
                if ($user->user_type !== 0 && $user->user_type !== 2) {
                    throw ValidationException::withMessages([
                        'username' => 'Only guards and superadmins can login through locations. Admins must use the admin login.',
                        'password' => '',
                    ]);
                }
            }

            // If no location (admin login), only allow admin (1) or superadmin (2)
            if (!$location && $user->user_type === 0) {
                throw ValidationException::withMessages([
                    'username' => 'Guards must login through a location',
                    'password' => '',
                ]);
            }

            // All validations passed - now log the user in
            Auth::login($user);

            // Regenerate session to prevent session fixation
            request()->session()->regenerate();

            // Store location in session if provided (for regular users and superadmins)
            // Location is already validated above, so we can safely use it
            if ($location && isset($locationModel)) {
                $request->session()->put('location_id', $locationModel->id);
                $request->session()->put('location_name', $locationModel->location_name);
            }

            // Ensure session is saved before redirecting
            $request->session()->save();

            // If superadmin logged in through a location, redirect to guard dashboard
            // Otherwise, use their normal dashboard route
            if ($user->user_type === 2 && $location && isset($locationModel)) {
                return redirect()->route('user.dashboard');
            }

            return redirect()->route($user->dashboardRoute());
    }

    public function destroy(Request $request)
    {
        // Redirect to landing page first (before ending session)
        $redirect = redirect('/');
        
        // Then clear the session
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    
        return $redirect;
    }
}