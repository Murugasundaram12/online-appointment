<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$r = App\Models\FormRecord::with(['form', 'client'])->find(2);
echo "Form Record 2:\n";
echo "Form exists: " . ($r->form ? $r->form->name : 'NULL') . "\n";
echo "Client exists: " . ($r->client ? $r->client->name : 'NULL') . "\n";
echo "Submitted at: " . ($r->submitted_at ? $r->submitted_at->toDateTimeString() : 'NULL') . "\n";
