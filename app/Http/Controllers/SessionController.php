<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\Location;
use App\Models\Setting;

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

        if(! Auth::attempt($attributes)){
            throw ValidationException::withMessages([
                'username'=> 'Sorry, those credentials are incorrect',
                'password'=> '',
            ]);
        }
        
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check if user is disabled
        if ($user->disabled) {
            Auth::logout();
            throw ValidationException::withMessages([
                'username' => 'Your account has been disabled. Please contact an administrator.',
                'password' => '',
            ]);
        }

        // If location login is being used, only allow regular users (type 0)
        if ($location && $user->user_type !== 0) {
            Auth::logout();
            throw ValidationException::withMessages([
                'username' => 'Only guards can login through locations. Admins must use the admin login.',
                'password' => '',
            ]);
        }

        // If no location (admin login), only allow admin (1) or superadmin (2)
        if (!$location && $user->user_type === 0) {
            Auth::logout();
            throw ValidationException::withMessages([
                'username' => 'Guards must login through a location',
                'password' => '',
            ]);
        }

        request()->session()->regenerate();

        // Store location in session if provided (for regular users)
        if ($location) {
            $locationModel = Location::findOrFail($location);
            $request->session()->put('location_id', $locationModel->id);
            $request->session()->put('location_name', $locationModel->location_name);
        }

        return redirect()->route($user->dashboardRoute());
    }

    public function destroy()
    {
        // Create redirect response first to avoid 404 errors
        $redirect = redirect('/');
        
        // Then clear the session
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    
        return $redirect;
    }
}