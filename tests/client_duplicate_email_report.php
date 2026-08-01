<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$duplicates = DB::table('clients')
    ->select('email', DB::raw('COUNT(*) as total'), DB::raw('GROUP_CONCAT(id ORDER BY id) as ids'))
    ->whereNotNull('email')
    ->where('email', '!=', '')
    ->groupBy('email')
    ->having('total', '>', 1)
    ->orderBy('email')
    ->get();

echo json_encode([
    'duplicate_email_groups' => $duplicates,
], JSON_PRETTY_PRINT) . PHP_EOL;
