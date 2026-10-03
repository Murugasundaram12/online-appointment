<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_number',
        'client_id',
        'staff_id',
        'created_by',
        'appointment_id',
        'status',
        'issued_date',
        'valid_until',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'notes',
        'terms',
    ];

    protected $casts = [
        'issued_date'     => 'date',
        'valid_until'     => 'date',
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'total_amount'    => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
