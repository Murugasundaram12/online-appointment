<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$forms = App\Models\Form::all();
echo "Total forms: " . $forms->count() . "\n";
foreach ($forms as $f) {
    echo "Form ID: {$f->id}, Name: {$f->name}\n";
    echo "Type of fields property: " . gettype($f->fields) . "\n";
    echo "Raw original fields: " . var_export($f->getRawOriginal('fields'), true) . "\n";
    echo "Fields value: " . var_export($f->fields, true) . "\n\n";
}
