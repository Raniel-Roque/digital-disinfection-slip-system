<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Redirect authenticated users to their dashboard.
     * Superadmins with a location in session are redirected to user dashboard.
     */
    public function redirect(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // If superadmin has a location in session, redirect to guard dashboard
        if ($user->user_type === 2 && $request->session()->has('location_id')) {
            return redirect()->route('user.dashboard');
        }

        return redirect()->route($user->dashboardRoute());
    }

    public function user(): View
    {
        return view('user.dashboard');
    }

    public function admin(): View
    {
        return view('admin.dashboard');
    }

    public function superadmin(): View
    {
        return view('super-admin.dashboard');
    }
}


