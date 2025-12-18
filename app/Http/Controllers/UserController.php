<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function dashboard()
    {
        return view('user.dashboard');
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
}
