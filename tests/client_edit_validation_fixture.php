<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Client;

$prefix = 'uat_client_edit_';

Client::where('email', 'like', $prefix . '%@example.com')->delete();

$primary = Client::create([
    'name' => 'UAT Client Edit Primary',
    'email' => $prefix . 'primary@example.com',
    'phone' => '9000001010',
    'city' => 'Chennai',
    'client_since' => now()->toDateString(),
    'is_vip' => false,
]);

$other = Client::create([
    'name' => 'UAT Client Edit Other',
    'email' => $prefix . 'other@example.com',
    'phone' => '9000001011',
    'city' => 'Coimbatore',
    'client_since' => now()->toDateString(),
    'is_vip' => false,
]);

$duplicateA = Client::create([
    'name' => 'UAT Client Edit Duplicate A',
    'email' => $prefix . 'duplicate@example.com',
    'phone' => '9000001012',
    'city' => 'Madurai',
    'client_since' => now()->toDateString(),
    'is_vip' => false,
]);

$duplicateB = Client::create([
    'name' => 'UAT Client Edit Duplicate B',
    'email' => $prefix . 'duplicate@example.com',
    'phone' => '9000001013',
    'city' => 'Trichy',
    'client_since' => now()->toDateString(),
    'is_vip' => false,
]);

echo json_encode([
    'primary_id' => $primary->id,
    'primary_email' => $primary->email,
    'other_id' => $other->id,
    'other_email' => $other->email,
    'unused_email' => $prefix . 'unused@example.com',
    'duplicate_a_id' => $duplicateA->id,
    'duplicate_b_id' => $duplicateB->id,
    'duplicate_email' => $duplicateA->email,
], JSON_PRETTY_PRINT) . PHP_EOL;
