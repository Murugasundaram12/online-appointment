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
        'cash_amount',
        'card_amount',
        'e_transfer_amount',
        'insurance_amount',
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
        'cash_amount' => 'decimal:2',
        'card_amount' => 'decimal:2',
        'e_transfer_amount' => 'decimal:2',
        'insurance_amount' => 'decimal:2',
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
        if (array_key_exists('cash_amount', $this->attributes) && $this->attributes['cash_amount'] !== null) {
            return (float) $this->attributes['cash_amount'];
        }
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
        if (array_key_exists('card_amount', $this->attributes) && $this->attributes['card_amount'] !== null) {
            return (float) $this->attributes['card_amount'];
        }
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
        if (array_key_exists('e_transfer_amount', $this->attributes) && $this->attributes['e_transfer_amount'] !== null) {
            return (float) $this->attributes['e_transfer_amount'];
        }
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

    public function getInsuranceAmountAttribute(): float
    {
        if (array_key_exists('insurance_amount', $this->attributes) && $this->attributes['insurance_amount'] !== null) {
            return (float) $this->attributes['insurance_amount'];
        }
        if ($this->payment_method === 'insurance') {
            return (float) $this->amount;
        }
        if ($this->primary_method === 'insurance') {
            return (float) $this->primary_amount;
        }
        if ($this->secondary_method === 'insurance') {
            return (float) $this->secondary_amount;
        }
        return 0.0;
    }

    public function getIsSplitPaymentAttribute(): bool
    {
        $splitCount = 0;
        if ($this->cash_amount > 0) $splitCount++;
        if ($this->card_amount > 0) $splitCount++;
        if ($this->e_transfer_amount > 0) $splitCount++;
        if ($this->insurance_amount > 0) $splitCount++;

        return $splitCount >= 2
            || in_array($this->payment_method, [
                'cash_card',
                'card_e_transfer',
                'cash_e_transfer',
                'cash_insurance',
                'card_insurance',
                'e_transfer_insurance',
                'both',
                'split',
            ], true)
            || (!empty($this->primary_method) && !empty($this->secondary_method));
    }

    public function getFormattedMethodLabel(?string $currency = '$'): string
    {
        $curr = $currency ?: '$';
        $refText = $this->transaction_reference ? ' • Ref: ' . $this->transaction_reference : '';

        // If not split, render single payment method
        if (!$this->is_split_payment) {
            return match ($this->payment_method) {
                'cash' => 'Cash',
                'card' => 'Card' . ($this->card_brand ? ' • ' . $this->card_brand : '') . ($this->card_last_four ? ' • ****' . $this->card_last_four : '') . $refText,
                'e_transfer' => 'E-Transfer' . ($this->e_transfer_reference ? ' • ' . $this->e_transfer_reference : ''),
                'insurance' => 'Insurance' . ($this->insuranceCompany ? ' • ' . $this->insuranceCompany->name : ''),
                default => ucfirst(str_replace('_', ' ', $this->payment_method)),
            };
        }

        // Split payment: collect methods with amounts > 0
        $methodNames = [];
        $breakdowns = [];

        if ($this->cash_amount > 0) {
            $methodNames[] = 'Cash';
            $breakdowns[] = 'Cash: ' . $curr . number_format($this->cash_amount, 2);
        }
        if ($this->card_amount > 0) {
            $methodNames[] = 'Card';
            $cardDetail = ($this->card_brand ?: 'Card') . ($this->card_last_four ? ' ****' . $this->card_last_four : '');
            $breakdowns[] = 'Card: ' . $cardDetail . ' — ' . $curr . number_format($this->card_amount, 2);
        }
        if ($this->e_transfer_amount > 0) {
            $methodNames[] = 'E-Transfer';
            $breakdowns[] = 'E-Transfer: ' . ($this->e_transfer_reference ?: 'ETR') . ' — ' . $curr . number_format($this->e_transfer_amount, 2);
        }
        if ($this->insurance_amount > 0) {
            $methodNames[] = 'Insurance';
            $breakdowns[] = 'Insurance: ' . ($this->insuranceCompany ? $this->insuranceCompany->name : 'Insurance') . ' — ' . $curr . number_format($this->insurance_amount, 2);
        }

        if (empty($methodNames)) {
            return ucfirst(str_replace('_', ' ', $this->payment_method));
        }

        $title = implode(' + ', $methodNames);
        return $title . ' (' . implode(' | ', $breakdowns) . ')' . $refText;
    }

    public function getFormattedMethodLabelAttribute(): string
    {
        return $this->getFormattedMethodLabel('$');
    }
}
