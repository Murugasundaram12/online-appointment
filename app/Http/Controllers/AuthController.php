<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
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

        try {
            $normalizedEmail = strtolower(trim((string) $validated['email']));

            $staff = Staff::whereRaw(
                'LOWER(TRIM(email)) = ?',
                [$normalizedEmail]
            )->first();
        } catch (QueryException $e) {
            report($e);

            return back()
                ->withErrors(['email' => 'Database connection is not configured. Please contact the administrator.'])
                ->onlyInput('email');
        }

        if (!$staff || !$staff->is_active || !Hash::check($validated['password'], $staff->password)) {
            return back()->withErrors(['email' => 'Invalid credentials or inactive account.'])->onlyInput('email');
        }

        Auth::guard('staff')->login($staff, $request->boolean('remember'));
        $staff->forceFill(['last_login_at' => now()])->save();
        $request->session()->regenerate();

        $intended = $request->session()->pull('url.intended');
        if ($intended) {
            $dashboardUrls = array_filter([
                route('home'),
                route('dashboard'),
                route('login'),
                url('/'),
                url('/dashboard'),
            ]);

            $path = parse_url($intended, PHP_URL_PATH);
            $normalizedPath = '/' . trim((string) $path, '/');

            if (
                in_array($intended, $dashboardUrls, true) ||
                in_array($normalizedPath, ['/', '/dashboard', '/login'], true)
            ) {
                $intended = null;
            }
        }

        return redirect()->to($intended ?: route('calendar.index'));
    }

    public function logout(Request $request)
    {
        Auth::guard('staff')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
