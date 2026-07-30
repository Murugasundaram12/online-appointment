<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = ['appointment_id', 'client_id', 'staff_id', 'invoice_number', 'total_amount', 'paid_amount', 'status', 'issued_date', 'due_date'];

    protected $casts = [
        'issued_date' => 'date',
        'due_date' => 'date'
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function payments()
    {
        return $this->hasMany(PaymentRecord::class);
    }
}
