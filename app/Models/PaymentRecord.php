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

    public function getCashAmountAttribute(): float
    {
        if ($this->payment_method === 'cash') {
            return (float) $this->amount;
        }
        if ($this->primary_method === 'cash') {
            return (float) $this->primary_amount;
        }
        if ($this->secondary_method === 'cash') {
            return (float) $this->secondary_amount;
        }
        return 0.0;
    }

    public function getCardAmountAttribute(): float
    {
        if ($this->payment_method === 'card') {
            return (float) $this->amount;
        }
        if ($this->primary_method === 'card') {
            return (float) $this->primary_amount;
        }
        if ($this->secondary_method === 'card') {
            return (float) $this->secondary_amount;
        }
        return 0.0;
    }

    public function getETransferAmountAttribute(): float
    {
        if (in_array($this->payment_method, ['e_transfer', 'transfer'], true)) {
            return (float) $this->amount;
        }
        if ($this->primary_method === 'e_transfer') {
            return (float) $this->primary_amount;
        }
        if ($this->secondary_method === 'e_transfer') {
            return (float) $this->secondary_amount;
        }
        return 0.0;
    }

    public function getIsSplitPaymentAttribute(): bool
    {
        return in_array($this->payment_method, ['cash_card', 'card_e_transfer', 'cash_e_transfer', 'both'], true)
            || (!empty($this->primary_method) && !empty($this->secondary_method));
    }
}
