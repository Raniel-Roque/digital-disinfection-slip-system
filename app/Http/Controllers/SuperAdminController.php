<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        return view('superadmin.dashboard');
    }

    public function guards()
    {
        return view('superadmin.guards');
    }
    
    public function drivers()
    {
        return view('superadmin.drivers');
    }
    
    public function locations()
    {
        return view('superadmin.locations');
    }
    
    public function plateNumbers()
    {
        return view('superadmin.plate-numbers');
    }
    
    public function trucks()
    {
        return view('superadmin.trucks');
    }
    
    public function admins()
    {
        return view('superadmin.admins');
    }
    
    public function reports()
    {
        return view('superadmin.reports');
    }

    public function auditTrail()
    {
        return view('superadmin.audit-trail');
    }

    public function settings()
    {
        return view('superadmin.settings');
    }

    public function printGuards(Request $request)
    {
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
        
        return view('livewire.superadmin.print-guards', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }

    public function printAdmins(Request $request)
    {
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
        
        return view('livewire.superadmin.print-admins', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }

    public function printDrivers(Request $request)
    {
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
        
        return view('livewire.superadmin.print-drivers', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }

    public function printLocations(Request $request)
    {
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
        
        return view('livewire.superadmin.print-locations', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }

    public function printPlateNumbers(Request $request)
    {
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
        
        return view('livewire.superadmin.print-plate-numbers', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }

    public function printTrucks(Request $request)
    {
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
        
        return view('livewire.superadmin.print-trucks', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }

    public function printSlip(Request $request)
    {
        $slip = null;
        
        if ($request->has('token')) {
            $token = $request->token;
            $sessionKey = "print_slip_{$token}";
            $expiresKey = "print_slip_{$token}_expires";
            
            if (Session::has($sessionKey) && Session::has($expiresKey)) {
                if (now()->lt(Session::get($expiresKey))) {
                    $slipId = Session::get($sessionKey);
                    $slip = \App\Models\DisinfectionSlip::with(['truck' => function($q) { $q->withTrashed(); }, 'location', 'destination', 'driver', 'hatcheryGuard', 'receivedGuard'])
                        ->find($slipId);
                    Session::forget([$sessionKey, $expiresKey]);
                }
            }
        }
        
        if (!$slip) {
            abort(404, 'Slip not found or expired');
        }
        
        return view('livewire.admin.print-slip', [
            'slip' => $slip
        ]);
    }

    public function printAuditTrail(Request $request)
    {
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
        
        return view('livewire.superadmin.print-audit-trail', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }
}

