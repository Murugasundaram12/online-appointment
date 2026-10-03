<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'appointment_id',
        'quotation_id',
        'client_id',
        'staff_id',
        'invoice_number',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'status',
        'issued_date',
        'due_date',
    ];

    protected $casts = [
        'issued_date'     => 'date',
        'due_date'        => 'date',
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'paid_amount'     => 'decimal:2',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
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

    public function items()
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }
}

