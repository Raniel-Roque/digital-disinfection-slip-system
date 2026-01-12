<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use Illuminate\Support\Facades\Session;

class UserController extends Controller
{
    public function dashboard()
    {
        // Check if current location allows creating slips
        $canCreateSlip = false;
        $currentLocationId = Session::get('location_id');
        
        if ($currentLocationId) {
            $location = Location::find($currentLocationId);
            $canCreateSlip = $location && ($location->create_slip ?? false);
        }
        
        return view('user.dashboard', compact('canCreateSlip'));
    }

    public function incomingTrucks()
    {
        return view('user.incoming-trucks');
    }

    public function outgoingTrucks()
    {
        return view('user.outgoing-trucks');
    }

    public function completedTrucks()
    {
        return view('user.completed-trucks');
    }

    public function reports()
    {
        return view('user.reports');
    }

    public function report()
    {
        return view('user.report');
    }

    // Super Guard Data Management Methods (accessible to super guards and super admins)
    public function dataGuards()
    {
        $user = auth()->user();
        // Allow super guards OR super admins
        if (!($user->super_guard || $user->user_type === 2)) {
            // Regular guards trying to access super guard routes - redirect to landing
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }
        return view('user.data.guards');
    }

    public function dataDrivers()
    {
        $user = auth()->user();
        // Allow super guards OR super admins
        if (!($user->super_guard || $user->user_type === 2)) {
            // Regular guards trying to access super guard routes - redirect to landing
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }
        return view('user.data.drivers');
    }

    public function dataLocations()
    {
        $user = auth()->user();
        // Allow super guards OR super admins
        if (!($user->super_guard || $user->user_type === 2)) {
            // Regular guards trying to access super guard routes - redirect to landing
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }
        return view('user.data.locations');
    }

    public function dataPlateNumbers()
    {
        $user = auth()->user();
        // Allow super guards OR super admins
        if (!($user->super_guard || $user->user_type === 2)) {
            // Regular guards trying to access super guard routes - redirect to landing
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }
        return view('user.data.plate-numbers');
    }

    // Print methods for super guards
    public function printGuards(Request $request)
    {
        $user = auth()->user();
        // Allow super guards OR super admins
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
        
        return view('livewire.admin.print-guards', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }

    public function printDrivers(Request $request)
    {
        $user = auth()->user();
        // Allow super guards OR super admins
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
        
        return view('livewire.admin.print-drivers', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }

    public function printLocations(Request $request)
    {
        $user = auth()->user();
        // Allow super guards OR super admins
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
        
        return view('livewire.admin.print-locations', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }

    public function printPlateNumbers(Request $request)
    {
        $user = auth()->user();
        // Allow super guards OR super admins
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
        
        return view('livewire.admin.print-plate-numbers', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }
}
