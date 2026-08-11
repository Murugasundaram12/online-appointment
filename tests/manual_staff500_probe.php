<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\StaffController;
use App\Models\Location;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;

function stResult(string $name, bool $pass, string $detail = ''): void
{
    echo ($pass ? 'PASS' : 'FAIL') . ' | ' . $name . ($detail ? ' | ' . $detail : '') . PHP_EOL;
}

function stCleanup(): void
{
    Staff::where('email', 'like', 'st500_%@example.com')->forceDelete();
    Location::where('name', 'like', 'ST500_%')->forceDelete();
}

function stRender(Request $request): string
{
    Paginator::currentPageResolver(fn ($pageName = 'page', $default = 1) => $request->input($pageName, $default));
    Paginator::queryStringResolver(fn () => $request->query());
    View::share('errors', new ViewErrorBag());
    return app(StaffController::class)->index($request)->render();
}

stCleanup();
View::share('errors', new ViewErrorBag());

$loc = Location::create(['name' => 'ST500_Main', 'timezone' => config('app.timezone'), 'is_active' => true]);
for ($i = 1; $i <= 15; $i++) {
    Staff::create([
        'name' => 'ST500_Staff_' . $i,
        'email' => 'st500_' . $i . '@example.com',
        'password' => Hash::make('Password123'),
        'access_level' => $i % 2 ? 'admin' : 'staff',
        'location_id' => $loc->id,
        'category' => 'Massage Therapy',
        'is_active' => true,
    ]);
}
$total = Staff::count();

$base = stRender(Request::create('/staff', 'GET'));
stResult('Staff list renders without 500', str_contains($base, 'Staff'));
stResult('Pagination summary shown', str_contains($base, 'Showing 1 to 10 of ' . $total));

$email = 'st500_1@example.com';
$one = stRender(Request::create('/staff?search=' . urlencode($email), 'GET', ['search' => $email]));
$staffA = Staff::where('email', $email)->first();
stResult('Single search result page renders', str_contains($one, 'Showing 1 to 1 of 1'));
stResult('Edit link uses item id', str_contains($one, 'data-update-url="' . route('staff.update', $staffA->id) . '"'));
stResult('Delete link uses item id', str_contains($one, 'action="' . route('staff.destroy', $staffA->id) . '"'));

$search = 'ST500_';
$p1 = stRender(Request::create('/staff?search=' . urlencode($search) . '&per_page=10', 'GET', ['search' => $search, 'per_page' => 10]));
stResult('Search scoped page 1 summary', str_contains($p1, 'Showing 1 to 10 of 15'));
stResult('Page 2 link preserves search', preg_match('/search=ST500_/', $p1) === 1 && preg_match('/page=2/', $p1) === 1);

$p2 = stRender(Request::create('/staff?search=' . urlencode($search) . '&per_page=10&page=2', 'GET', ['search' => $search, 'per_page' => 10, 'page' => 2]));
stResult('Page 2 renders without 500', str_contains($p2, 'ST500_Staff'));
stResult('Page 2 summary correct', str_contains($p2, 'Showing 11 to 15 of 15'));

$filters = stRender(Request::create('/staff?search=' . urlencode($search) . '&access_level=admin&category=Massage+Therapy&per_page=25', 'GET', ['search' => $search, 'access_level' => 'admin', 'category' => 'Massage Therapy', 'per_page' => 25]));
stResult('Filters render without 500', str_contains($filters, 'Showing 1 to 8 of 8'));
stResult('per_page selector preserved', preg_match('/per_page=25/', $filters) === 1);

stResult('Edit modal action is placeholder (JS-set)', str_contains($filters, 'id="edit-staff-form" action="#"'));

stCleanup();
