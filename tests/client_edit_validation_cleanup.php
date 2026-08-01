<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Client;

$deleted = Client::where('email', 'like', 'uat_client_edit_%@example.com')->delete();

echo json_encode([
    'deleted_test_clients' => $deleted,
], JSON_PRETTY_PRINT) . PHP_EOL;
