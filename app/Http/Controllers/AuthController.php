<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $staff = Staff::where('email', $validated['email'])->first();
        if (!$staff || !$staff->is_active || !Hash::check($validated['password'], $staff->password)) {
            return back()->withErrors(['email' => 'Invalid credentials or inactive account.'])->onlyInput('email');
        }

        Auth::guard('staff')->login($staff, $request->boolean('remember'));
        $staff->forceFill(['last_login_at' => now()])->save();
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('staff')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
