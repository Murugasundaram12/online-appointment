<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'amount',
        'payment_method',
        'primary_method',
        'secondary_method',
        'primary_amount',
        'secondary_amount',
        'payment_date',
        'transaction_id',
        'card_brand',
        'cardholder_name',
        'card_last_four',
        'transaction_reference',
        'e_transfer_reference',
        'sender_name',
        'transfer_date',
        'insurance_company_id',
        'insurance_information_id',
        'policy_id',
        'member_id_or_contract_number',
        'claim_reference',
        'amount_submitted',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'transfer_date' => 'date',
        'amount' => 'decimal:2',
        'primary_amount' => 'decimal:2',
        'secondary_amount' => 'decimal:2',
        'amount_submitted' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function insuranceCompany()
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function insuranceInformation()
    {
        return $this->belongsTo(InsuranceInformation::class);
    }
}
