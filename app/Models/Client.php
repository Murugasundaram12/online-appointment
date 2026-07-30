<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'city', 'notes', 'client_since', 'is_vip'];

    protected $casts = [
        'client_since' => 'date',
        'is_vip' => 'boolean'
    ];

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function formRecords()
    {
        return $this->hasMany(FormRecord::class);
    }
}
