<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $staff = Auth::guard('staff')->user();
        if (!$staff || !$staff->is_active) {
            Auth::guard('staff')->logout();
            return redirect()->route('login')->withErrors(['email' => 'Your account is inactive.']);
        }

        return $next($request);
    }
}
