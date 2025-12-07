<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
    
    public function settings()
    {
        return view('superadmin.settings');
    }
}

