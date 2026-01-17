<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AdminController extends Controller
{
    public function dashboard()
    {
        // Authorize: user type must be 1 (admin)
        if (Auth::user()->user_type != 1) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('admin.dashboard');
    }

    public function guards()
    {
        // Authorize: user type must be 1 (admin)
        if (Auth::user()->user_type != 1) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('admin.guards');
    }
    public function drivers()
    {
        // Authorize: user type must be 1 (admin)
        if (Auth::user()->user_type != 1) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('admin.drivers');
    }
    public function locations()
    {
        // Authorize: user type must be 1 (admin)
        if (Auth::user()->user_type != 1) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('admin.locations');
    }
    public function vehicles()
    {
        // Authorize: user type must be 1 (admin)
        if (Auth::user()->user_type != 1) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('admin.vehicles');
    }
    public function slips()
    {
        // Authorize: user type must be 1 (admin)
        if (Auth::user()->user_type != 1) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('admin.slips');
    }

    public function issues()
    {
        // Authorize: user type must be 1 (admin)
        if (Auth::user()->user_type != 1) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('admin.issues');
    }

    public function auditTrail()
    {
        // Authorize: user type must be 1 (admin)
        if (Auth::user()->user_type != 1) {
            return redirect('/')->with('status', 'You do not have permission to access this page.');
        }

        return view('admin.audit-trail');
    }

    public function printGuards(Request $request)
    {
        // Authorize: user type must be 1 (admin)
        if (Auth::user()->user_type != 1) {
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
        
        return view('livewire.admin.print-guards', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }

    public function printDrivers(Request $request)
    {
        // Authorize: user type must be 1 (admin)
        if (Auth::user()->user_type != 1) {
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
        // Authorize: user type must be 1 (admin)
        if (Auth::user()->user_type != 1) {
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

    public function printVehicles(Request $request)
    {
        // Authorize: user type must be 1 (admin)
        if (Auth::user()->user_type != 1) {
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
        
        return view('livewire.admin.print-vehicles', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }

    public function printSlips(Request $request)
    {
        // Authorize: user type must be 1 (admin)
        if (Auth::user()->user_type != 1) {
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
        
        return view('livewire.admin.print-slips', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }

    public function printSlip(Request $request)
    {
        // Authorize: user type must be 1 (admin)
        if (Auth::user()->user_type != 1) {
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
                        'vehicle',
                        'location',
                        'destination',
                        'driver',
                        'reason',
                        'hatcheryGuard',
                        'receivedGuard'
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
        
        return view('livewire.admin.print-slip', [
            'slip' => $slip,
            'displayReason' => $displayReason
        ]);
    }

    public function printAuditTrail(Request $request)
    {
        // Authorize: user type must be 1 (admin)
        if (Auth::user()->user_type != 1) {
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
        
        return view('livewire.admin.print-audit-trail', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }
}
