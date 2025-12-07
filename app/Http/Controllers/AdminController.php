<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard');
    }

    public function guards()
    {
        return view('admin.guards');
    }
    public function drivers()
    {
        return view('admin.drivers');
    }
    public function locations()
    {
        return view('admin.locations');
    }
    public function plateNumbers()
    {
        return view('admin.plate-numbers');
    }
    public function trucks()
    {
        return view('admin.trucks');
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
        
        return view('livewire.admin.print-guards', [
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
        
        return view('livewire.admin.print-drivers', [
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
        
        return view('livewire.admin.print-locations', [
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
        
        return view('livewire.admin.print-plate-numbers', [
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
        
        return view('livewire.admin.print-trucks', [
            'data' => $data,
            'filters' => $filters,
            'sorting' => $sorting
        ]);
    }
}
