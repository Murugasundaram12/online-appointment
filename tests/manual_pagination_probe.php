<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ServiceController;
use App\Models\Client;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;

function pgResult(string $name, bool $pass, string $detail = ''): void
{
    echo ($pass ? 'PASS' : 'FAIL') . ' | ' . $name . ($detail ? ' | ' . $detail : '') . PHP_EOL;
}

function pgIndex($controller, string $method, Request $request)
{
    Paginator::currentPageResolver(fn ($pageName = 'page', $default = 1) => $request->input($pageName, $default));
    return $controller->{$method}($request)->render();
}

function pgCleanup(): void
{
    Client::where('email', 'like', 'pgprobe_%@example.com')->forceDelete();
    Service::where('name', 'like', 'PGPROBE_%')->forceDelete();
}

pgCleanup();
View::share('errors', new ViewErrorBag());

$preExisting = Client::count();
$created = 15;
for ($i = 1; $i <= $created; $i++) {
    Client::create([
        'name' => 'PGPROBE_Client_' . $i,
        'email' => 'pgprobe_' . $i . '@example.com',
        'phone' => '700' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
    ]);
}
$total = $preExisting + $created;

$controller = app(ClientController::class);
$page1Req = Request::create('/clients?per_page=10', 'GET', ['per_page' => 10]);
$page1 = pgIndex($controller, 'index', $page1Req);
pgResult('Page 1 shows 10 of ' . $total . ' clients', str_contains($page1, 'Showing 1 to 10 of ' . $total));
pgResult('Page 1 renders next page link', preg_match('/page=2/', $page1) === 1);
pgResult('Rows per page selector rendered', preg_match('/per_page=25/', $page1) === 1);
pgResult('Pagination uses Bootstrap 5 classes', str_contains($page1, 'class="page-link"') && !str_contains($page1, 'inline-flex items-center'));

$page2Req = Request::create('/clients?per_page=10&page=2', 'GET', ['per_page' => 10, 'page' => 2]);
$page2 = pgIndex($controller, 'index', $page2Req);
pgResult('Page 2 shows remaining clients', str_contains($page2, 'Showing 11 to ' . min(20, $total) . ' of ' . $total));

$page3Req = Request::create('/clients?per_page=10&page=3', 'GET', ['per_page' => 10, 'page' => 3]);
$page3 = pgIndex($controller, 'index', $page3Req);
pgResult('Empty page renders safely', str_contains($page3, 'Showing ' . (min(20, $total) + 1) . ' to ' . $total . ' of ' . $total));

$per25Req = Request::create('/clients?per_page=25', 'GET', ['per_page' => 25]);
$per25 = pgIndex($controller, 'index', $per25Req);
pgResult('Custom per_page respected', str_contains($per25, 'Showing 1 to 25 of ' . $total));

$searchReq = Request::create('/clients?per_page=10&search=PGPROBE_Client_1', 'GET', ['per_page' => 10, 'search' => 'PGPROBE_Client_1']);
$searchMatches = Client::where('name', 'like', '%PGPROBE_Client_1%')->count();
$searchPage = pgIndex($controller, 'index', $searchReq);
pgResult('Search result paginates', str_contains($searchPage, 'of ' . $searchMatches . ' results'));

for ($i = 1; $i <= 12; $i++) {
    Service::create(['name' => 'PGPROBE_Service_' . $i, 'type' => 'in_person', 'price' => 10, 'duration_minutes' => 30, 'is_active' => true]);
}
$servicesReq = Request::create('/services?per_page=10', 'GET', ['per_page' => 10]);
$servicesPage = pgIndex(app(ServiceController::class), 'index', $servicesReq);
pgResult('Services index paginates', str_contains($servicesPage, 'Showing 1 to 10 of'));

pgCleanup();
