<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function perPage(Request $request, int $default = 10)
    {
        $perPage = (int) $request->input('per_page', $default);
        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : $default;
    }
}
