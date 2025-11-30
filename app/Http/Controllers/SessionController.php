<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SessionController extends Controller
{
    public function create()
    {
        // If user is logged in, redirect to /home
        if (Auth::check()) {
            return redirect('/');
        }
        
        return view('auth.login');
    }

    public function store(Request $request){
        $attributes = request()->validate([
            "username"=> ['required'],
            "password"=> ['required'],
        ]);

        if(! Auth::attempt($attributes)){
            throw ValidationException::withMessages([
                'username'=> 'Sorry, those credentials are incorrect',
                'password'=> '',
            ]);
        }
        
        request()->session()->regenerate();

        /** @var \App\Models\User $user */
        $user = Auth::user();

        return redirect()->route($user->dashboardRoute());
    }

    public function destroy()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    }
}
