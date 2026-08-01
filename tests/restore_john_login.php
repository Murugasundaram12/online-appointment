<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use Illuminate\Support\Facades\Hash;

$staff = Staff::where('email', 'john@example.com')->first();

if (!$staff) {
    echo "missing\n";
    exit(1);
}

$staff->forceFill([
    'password' => Hash::make('12345678'),
    'is_active' => true,
])->save();

echo json_encode([
    'id' => $staff->id,
    'email' => $staff->email,
    'name' => $staff->name,
    'access_level' => $staff->access_level,
    'is_active' => (bool) $staff->is_active,
    'password_verified' => Hash::check('12345678', $staff->password),
], JSON_PRETTY_PRINT) . PHP_EOL;
