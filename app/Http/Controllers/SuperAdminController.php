<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        // Authorize: user type must be 2 (superadmin)
        if (Auth::user()->user_type != 2) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('superadmin.dashboard');
    }

    public function guards()
    {
        // Authorize: user type must be 2 (superadmin)
        if (Auth::user()->user_type != 2) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('superadmin.guards');
    }

    public function drivers()
    {
        // Authorize: user type must be 2 (superadmin)
        if (Auth::user()->user_type != 2) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('superadmin.drivers');
    }

    public function locations()
    {
        // Authorize: user type must be 2 (superadmin)
        if (Auth::user()->user_type != 2) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('superadmin.locations');
    }

    public function vehicles()
    {
        // Authorize: user type must be 2 (superadmin)
        if (Auth::user()->user_type != 2) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('superadmin.vehicles');
    }

    public function slips()
    {
        // Authorize: user type must be 2 (superadmin)
        if (Auth::user()->user_type != 2) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('superadmin.slips');
    }

    public function admins()
    {
        // Authorize: user type must be 2 (superadmin)
        if (Auth::user()->user_type != 2) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('superadmin.admins');
    }

    public function issues()
    {
        // Authorize: user type must be 2 (superadmin)
        if (Auth::user()->user_type != 2) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('superadmin.issues');
    }

    public function auditTrail()
    {
        return view('superadmin.audit-trail');
    }

    public function settings()
    {
        // Authorize: user type must be 2 (superadmin)
        if (Auth::user()->user_type != 2) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('superadmin.settings');
    }

    public function printGuards(Request $request)
    {
        // Authorize: user type must be 2 (superadmin)
        if (Auth::user()->user_type != 2) {
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
        
        return view('components.prints.guards', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }

    public function printAdmins(Request $request)
    {
        // Authorize: user type must be 2 (superadmin)
        if (Auth::user()->user_type != 2) {
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
        
        return view('components.prints.admins', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }

    public function printDrivers(Request $request)
    {
        // Authorize: user type must be 2 (superadmin)
        if (Auth::user()->user_type != 2) {
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
        // Authorize: user type must be 2 (superadmin)
        if (Auth::user()->user_type != 2) {
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
        // Authorize: user type must be 2 (superadmin)
        if (Auth::user()->user_type != 2) {
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

    public function printSlips(Request $request)
    {
        // Authorize: user type must be 2 (superadmin)
        if (Auth::user()->user_type != 2) {
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
        
        return view('components.prints.slips', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }

    public function printSlip(Request $request)
    {
        // Authorize: user type must be 2 (superadmin)
        if (Auth::user()->user_type != 2) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        $slip = null;
        
        if ($request->has('token')) {
            $token = $request->token;
            $sessionKey = "print_slip_{$token}";
            $expiresKey = "print_slip_{$token}_expires";
            
            if (Session::has($sessionKey) && Session::has($expiresKey)) {
                if (now()->lt(Session::get($expiresKey))) {
                    $slipId = Session::get($sessionKey);
                    $slip = \App\Models\DisinfectionSlip::with([
                        'vehicle' => function($q) { $q->withTrashed(); },
                        'location' => function($q) { $q->withTrashed(); },
                        'destination' => function($q) { $q->withTrashed(); },
                        'driver' => function($q) { $q->withTrashed(); },
                        'reason',
                        'hatcheryGuard' => function($q) { $q->withTrashed(); },
                        'receivedGuard' => function($q) { $q->withTrashed(); }
                    ])
                        ->find($slipId);
                    Session::forget([$sessionKey, $expiresKey]);
                }
            }
        }
        
        if (!$slip) {
            abort(404, 'Slip not found or expired');
        }
        
        // Get display reason text
        $displayReason = 'N/A';
        if ($slip->reason_id && $slip->reason) {
            $displayReason = ($slip->reason && !$slip->reason->is_disabled) ? $slip->reason->reason_text : 'N/A';
        }
        
        return view('components.prints.slip-single', [
            'slip' => $slip,
            'displayReason' => $displayReason
        ]);
    }

    public function printAuditTrail(Request $request)
    {
        // Authorize: user type must be 2 (superadmin)
        if (Auth::user()->user_type != 2) {
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
        
        return view('components.prints.audit-trail', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }
}

