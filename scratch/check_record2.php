<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$r = App\Models\FormRecord::find(2);
if (!$r) {
    echo "Record 2 NOT FOUND\n";
    exit;
}

echo "Record 2 found:\n";
echo "ID: " . $r->id . "\n";
echo "Form ID: " . var_export($r->form_id, true) . "\n";
echo "Client ID: " . var_export($r->client_id, true) . "\n";
echo "Type of submitted_data property: " . gettype($r->submitted_data) . "\n";
echo "Raw original submitted_data: " . var_export($r->getRawOriginal('submitted_data'), true) . "\n";
echo "Casted submitted_data: " . var_export($r->submitted_data, true) . "\n";

$all = App\Models\FormRecord::all();
echo "\nTotal form records: " . $all->count() . "\n";
foreach ($all as $item) {
    echo "ID {$item->id}: type=" . gettype($item->submitted_data) . ", raw=" . substr($item->getRawOriginal('submitted_data'), 0, 50) . "\n";
}
