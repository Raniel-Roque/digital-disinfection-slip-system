<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Location;
use Illuminate\Support\Facades\Session;

class UserController extends Controller
{
    public function dashboard()
    {
        // Authorize: user type must be 0, or superadmin (2) with location in session
        $user = Auth::user();
        $isAuthorized = $user->user_type == 0 ||
            ($user->user_type == 2 && Session::has('location_id'));

        if (!$isAuthorized) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }
        // Check if current location allows creating slips
        $canCreateSlip = false;
        $currentLocationId = Session::get('location_id');
        
        if ($currentLocationId) {
            $location = Location::find($currentLocationId);
            $canCreateSlip = $location && ($location->create_slip ?? false);
        }
        
        return view('user.dashboard', compact('canCreateSlip'));
    }

    public function incomingSlips()
    {
        // Authorize: user type must be 0, or superadmin (2) with location in session
        $user = Auth::user();
        $isAuthorized = $user->user_type == 0 ||
            ($user->user_type == 2 && Session::has('location_id'));

        if (!$isAuthorized) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('user.incoming-slips');
    }

    public function outgoingSlips()
    {
        // Authorize: user type must be 0, or superadmin (2) with location in session
        $user = Auth::user();
        $isAuthorized = $user->user_type == 0 ||
            ($user->user_type == 2 && Session::has('location_id'));

        if (!$isAuthorized) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('user.outgoing-slips');
    }

    public function completedSlips()
    {
        // Authorize: user type must be 0, or superadmin (2) with location in session
        $user = Auth::user();
        $isAuthorized = $user->user_type == 0 ||
            ($user->user_type == 2 && Session::has('location_id'));

        if (!$isAuthorized) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('user.completed-slips');
    }

    public function issues()
    {
        // Authorize: user type must be 0, or superadmin (2) with location in session
        $user = Auth::user();
        $isAuthorized = $user->user_type == 0 ||
            ($user->user_type == 2 && Session::has('location_id'));

        if (!$isAuthorized) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('user.issues');
    }

    public function issue()
    {
        // Authorize: user type must be 0, or superadmin (2) with location in session
        $user = Auth::user();
        $isAuthorized = $user->user_type == 0 ||
            ($user->user_type == 2 && Session::has('location_id'));

        if (!$isAuthorized) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('user.issue');
    }

    // Super Guard Data Management Methods (accessible to super guards and super admins)
    public function dataGuards()
    {
        $user = Auth::user();

        // First check basic user type authorization (user type 0, or superadmin with location)
        $isAuthorized = $user->user_type == 0 ||
            ($user->user_type == 2 && Session::has('location_id'));

        if (!$isAuthorized) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        // Then check super guard OR super admin permissions for data access
        if (!($user->super_guard || $user->user_type === 2)) {
            // Regular guards trying to access super guard routes - redirect to landing
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('user.data.guards');
    }

    public function dataDrivers()
    {
        $user = Auth::user();

        // First check basic user type authorization (user type 0, or superadmin with location)
        $isAuthorized = $user->user_type == 0 ||
            ($user->user_type == 2 && Session::has('location_id'));

        if (!$isAuthorized) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        // Then check super guard OR super admin permissions for data access
        if (!($user->super_guard || $user->user_type === 2)) {
            // Regular guards trying to access super guard routes - redirect to landing
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('user.data.drivers');
    }

    public function dataLocations()
    {
        $user = Auth::user();

        // First check basic user type authorization (user type 0, or superadmin with location)
        $isAuthorized = $user->user_type == 0 ||
            ($user->user_type == 2 && Session::has('location_id'));

        if (!$isAuthorized) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        // Then check super guard OR super admin permissions for data access
        if (!($user->super_guard || $user->user_type === 2)) {
            // Regular guards trying to access super guard routes - redirect to landing
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('user.data.locations');
    }

    public function dataVehicles()
    {
        $user = Auth::user();

        // First check basic user type authorization (user type 0, or superadmin with location)
        $isAuthorized = $user->user_type == 0 ||
            ($user->user_type == 2 && Session::has('location_id'));

        if (!$isAuthorized) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        // Then check super guard OR super admin permissions for data access
        if (!($user->super_guard || $user->user_type === 2)) {
            // Regular guards trying to access super guard routes - redirect to landing
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('user.data.vehicles');
    }

    // Print methods for super guards
    public function printGuards(Request $request)
    {
        $user = Auth::user();

        // First check basic user type authorization (user type 0, or superadmin with location)
        $isAuthorized = $user->user_type == 0 ||
            ($user->user_type == 2 && Session::has('location_id'));

        if (!$isAuthorized) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        // Then check super guard OR super admin permissions for data access
        if (!($user->super_guard || $user->user_type === 2)) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        $data = collect();
        $filters = [];
        $sorting = [];
        
        if ($request->has('token')) {
            $token = $request->token;
            $sessionKey = "export_data_{$token}";
            $filtersKey = "export_filters_{$token}";
            $sortingKey = "export_sorting_{$token}";
            $expiresKey = "export_data_{$token}_expires";
            
            if (Session::has($sessionKey) && Session::has($expiresKey)) {
                if (now()->lt(Session::get($expiresKey))) {
                    // Note: The data from getExportData() in Guards.php already filters out super guards
                    // using ->where('super_guard', false), so only regular guards are included here
                    $data = collect(Session::get($sessionKey));
                    $filters = Session::get($filtersKey, []);
                    $sorting = Session::get($sortingKey, []);
                    Session::forget([$sessionKey, $filtersKey, $sortingKey, $expiresKey]);
                }
            }
        }
        
        return view('components.prints.guards', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }

    public function printDrivers(Request $request)
    {
        $user = Auth::user();

        // First check basic user type authorization (user type 0, or superadmin with location)
        $isAuthorized = $user->user_type == 0 ||
            ($user->user_type == 2 && Session::has('location_id'));

        if (!$isAuthorized) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        // Then check super guard OR super admin permissions for data access
        if (!($user->super_guard || $user->user_type === 2)) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        $data = collect();
        $filters = [];
        $sorting = [];
        
        if ($request->has('token')) {
            $token = $request->token;
            $sessionKey = "export_data_{$token}";
            $filtersKey = "export_filters_{$token}";
            $sortingKey = "export_sorting_{$token}";
            $expiresKey = "export_data_{$token}_expires";
            
            if (Session::has($sessionKey) && Session::has($expiresKey)) {
                if (now()->lt(Session::get($expiresKey))) {
                    $data = collect(Session::get($sessionKey));
                    $filters = Session::get($filtersKey, []);
                    $sorting = Session::get($sortingKey, []);
                    Session::forget([$sessionKey, $filtersKey, $sortingKey, $expiresKey]);
                }
            }
        }
        
        return view('components.prints.drivers', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }

    public function printLocations(Request $request)
    {
        $user = Auth::user();

        // First check basic user type authorization (user type 0, or superadmin with location)
        $isAuthorized = $user->user_type == 0 ||
            ($user->user_type == 2 && Session::has('location_id'));

        if (!$isAuthorized) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        // Then check super guard OR super admin permissions for data access
        if (!($user->super_guard || $user->user_type === 2)) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        $data = collect();
        $filters = [];
        $sorting = [];
        
        if ($request->has('token')) {
            $token = $request->token;
            $sessionKey = "export_data_{$token}";
            $filtersKey = "export_filters_{$token}";
            $sortingKey = "export_sorting_{$token}";
            $expiresKey = "export_data_{$token}_expires";
            
            if (Session::has($sessionKey) && Session::has($expiresKey)) {
                if (now()->lt(Session::get($expiresKey))) {
                    $data = collect(Session::get($sessionKey));
                    $filters = Session::get($filtersKey, []);
                    $sorting = Session::get($sortingKey, []);
                    Session::forget([$sessionKey, $filtersKey, $sortingKey, $expiresKey]);
                }
            }
        }
        
        return view('components.prints.locations', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }

    public function printVehicles(Request $request)
    {
        $user = Auth::user();

        // First check basic user type authorization (user type 0, or superadmin with location)
        $isAuthorized = $user->user_type == 0 ||
            ($user->user_type == 2 && Session::has('location_id'));

        if (!$isAuthorized) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        // Then check super guard OR super admin permissions for data access
        if (!($user->super_guard || $user->user_type === 2)) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        $data = collect();
        $filters = [];
        $sorting = [];
        
        if ($request->has('token')) {
            $token = $request->token;
            $sessionKey = "export_data_{$token}";
            $filtersKey = "export_filters_{$token}";
            $sortingKey = "export_sorting_{$token}";
            $expiresKey = "export_data_{$token}_expires";
            
            if (Session::has($sessionKey) && Session::has($expiresKey)) {
                if (now()->lt(Session::get($expiresKey))) {
                    $data = collect(Session::get($sessionKey));
                    $filters = Session::get($filtersKey, []);
                    $sorting = Session::get($sortingKey, []);
                    Session::forget([$sessionKey, $filtersKey, $sortingKey, $expiresKey]);
                }
            }
        }
        
        return view('components.prints.vehicles', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }
}
