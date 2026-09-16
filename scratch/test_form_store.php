<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$f = App\Models\Form::find(1);
echo "Form 1 Name: " . $f->name . "\n";
echo "Type of fields: " . gettype($f->fields) . "\n";
echo "Raw: " . $f->getRawOriginal('fields') . "\n";

// Let's test FormRecord creation as FormRecordController does:
$client = App\Models\Client::first();
echo "Client ID: " . $client->id . "\n";
