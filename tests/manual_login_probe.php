<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\AuthController;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

function loginCleanup(): void
{
    Staff::where('email', 'like', 'loginprobe_%@example.com')->forceDelete();
}

function loginResult(string $name, bool $pass, string $detail = ''): void
{
    echo ($pass ? 'PASS' : 'FAIL') . ' | ' . $name . ($detail ? ' | ' . $detail : '') . PHP_EOL;
}

function loginAttempt(array $data): array
{
    $session = app('session')->driver();
    $session->start();
    $request = Request::create('/login', 'POST', $data);
    $request->setLaravelSession($session);
    $controller = app(AuthController::class);
    try {
        $response = $controller->login($request);
        return ['status' => 'redirect', 'response' => $response, 'session' => $session];
    } catch (ValidationException $e) {
        return ['status' => 'validation', 'errors' => $e->errors(), 'session' => $session];
    }
}

loginCleanup();

$activeStaff = Staff::create([
    'name' => 'LOGIN_PROBE_Active',
    'email' => 'loginprobe_active@example.com',
    'password' => Hash::make('CorrectPass123'),
    'access_level' => 'admin',
    'is_active' => true,
]);
$inactiveStaff = Staff::create([
    'name' => 'LOGIN_PROBE_Inactive',
    'email' => 'loginprobe_inactive@example.com',
    'password' => Hash::make('CorrectPass123'),
    'access_level' => 'staff',
    'is_active' => false,
]);

$r = loginAttempt([]);
loginResult('Login requires email', $r['status'] === 'validation' && isset($r['errors']['email']));
loginResult('Login requires password', $r['status'] === 'validation' && isset($r['errors']['password']));

$r = loginAttempt(['email' => 'loginprobe_active@example.com', 'password' => 'WrongPass999']);
loginResult('Wrong password rejected', $r['status'] === 'redirect' && $r['session']->get('errors')->has('email'));

$r = loginAttempt(['email' => 'loginprobe_inactive@example.com', 'password' => 'CorrectPass123']);
loginResult('Inactive staff rejected', $r['status'] === 'redirect' && $r['session']->get('errors')->has('email'));

$r = loginAttempt(['email' => 'nobody@example.com', 'password' => 'CorrectPass123']);
loginResult('Unknown email rejected', $r['status'] === 'redirect' && $r['session']->get('errors')->has('email'));

$session = app('session')->driver();
$session->start();
$request = Request::create('/login', 'POST', ['email' => 'loginprobe_active@example.com', 'password' => 'CorrectPass123']);
$request->setLaravelSession($session);
$response = app(AuthController::class)->login($request);
loginResult('Valid login authenticates', Auth::guard('staff')->check() && Auth::guard('staff')->id() === $activeStaff->id);
loginResult('Valid login redirects to dashboard', $response->getStatusCode() === 302 && str_contains($response->headers->get('Location'), '/'));
$activeStaff->refresh();
loginResult('Valid login records last_login_at', $activeStaff->last_login_at !== null);

$logoutRequest = Request::create('/logout', 'POST');
$logoutRequest->setLaravelSession($session);
$logoutResponse = app(AuthController::class)->logout($logoutRequest);
loginResult('Logout clears auth', !Auth::guard('staff')->check());
loginResult('Logout redirects to login', $logoutResponse->getStatusCode() === 302 && str_contains($logoutResponse->headers->get('Location'), 'login'));

loginCleanup();
