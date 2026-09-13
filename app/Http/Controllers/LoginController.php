<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Jenssegers\Agent\Agent; // Keep import, but use directly below

class LoginController extends Controller
{
    public function getLogin()
    {
        return view('login');
    }

    public function postLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:8',
        ]);

        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
        ];

        if (auth()->attempt($credentials, $request->boolean('remember'))) {
            $user = auth()->user();
            $successMessage = 'Login Successful';

            if ($user->role === 'Technician') {
                $agent = new Agent(); // Direct use—no facade
                if ($agent->isMobile()) {
                    return redirect()->route('qr-scan')->with('success', $successMessage);
                }
                return redirect()->route('dashboard')->with('success', $successMessage);
            }

            return redirect()->route('dashboard')->with('success', $successMessage);
        }

        return redirect()->back()
            ->withInput($request->only('username'))
            ->withErrors(['username' => 'Invalid Credentials']);
    }
}