<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

class DummyRecord extends \App\Models\FormRecord {
    protected $table = 'form_records';
    public function getSubmittedDataAttribute($value)
    {
        echo "Accessor called with type: " . gettype($value) . "\n";
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}

$r = DummyRecord::find(2);
echo "Result of toArray():\n";
var_dump($r->toArray()['submitted_data']);
