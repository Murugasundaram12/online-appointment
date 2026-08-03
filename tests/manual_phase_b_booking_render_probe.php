<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\OnlineBookingController;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;

$failures = 0;
function ok(string $label, bool $cond): void
{
    global $failures;
    if (!$cond) $failures++;
    echo ($cond ? 'PASS' : 'FAIL') . ' | ' . $label . PHP_EOL;
}

View::share('errors', new ViewErrorBag());

$html = app(OnlineBookingController::class)->index()->render();

ok('page renders without exceptions', is_string($html) && strlen($html) > 1000);
ok('review button present', str_contains($html, 'id="reviewBtn"'));
ok('review panel present', str_contains($html, 'id="reviewPanel"'));
ok('confirm button inside review panel', str_contains($html, 'id="confirmBookingBtn"'));
ok('back to edit button present', str_contains($html, 'id="backToEditBtn"'));
ok('fields wrapper id present', str_contains($html, 'id="bookingFields"'));
ok('booking error box present', str_contains($html, 'id="bookingError"'));
ok('service options carry duration data attr', str_contains($html, 'data-duration='));
ok('service options carry price data attr', str_contains($html, 'data-price='));
ok('step badges have ids', str_contains($html, 'step-badge-0') && str_contains($html, 'step-badge-4'));
ok('no double native submit button without review guard', str_contains($html, 'Please review your booking details before confirming.'));
ok('no admin sidebar leak', !str_contains($html, 'partials.sidebar') && !str_contains($html, 'id="sidebar"'));
ok('no undefined-variable errors', !str_contains($html, 'Undefined variable') && !str_contains($html, 'whoops'));

echo PHP_EOL . ($failures === 0 ? 'ALL PASS' : $failures . ' FAILURES') . PHP_EOL;
exit($failures === 0 ? 0 : 1);
