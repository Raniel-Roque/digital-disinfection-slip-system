<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Redirect authenticated users to their dashboard.
     */
    public function redirect(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

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
        return view('superadmin.dashboard');
    }
}


