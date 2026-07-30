<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRecord extends Model
{
    protected $fillable = ['invoice_id', 'amount', 'payment_method', 'payment_date', 'transaction_id'];

    protected $casts = [
        'payment_date' => 'date'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
