<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $table = 'payroll';

    protected static function booted()
    {
        static::saving(function (Payroll $payroll) {
            $payroll->total_payout = $payroll->calculateTotalPayout();
            if ($payroll->total_hours === null) {
                $payroll->total_hours = 0;
            }
        });
    }

    protected $fillable = [
        'staff_id',
        'period_start',
        'period_end',
        'salary_amount',
        'commission_amount',
        'bonus',
        'deductions',
        'total_hours',
        'total_payout',
        'payment_date',
        'payment_type',
        'status',
        'notes'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'payment_date' => 'date',
        'salary_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'bonus' => 'decimal:2',
        'deductions' => 'decimal:2',
        'total_hours' => 'decimal:2',
        'total_payout' => 'decimal:2'
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Calculate total payout from salary, commission, and deductions
     */
    public function calculateTotalPayout()
    {
        return max(0, ($this->salary_amount ?? 0) + ($this->commission_amount ?? 0) + ($this->bonus ?? 0) - ($this->deductions ?? 0));
    }

    public function getDisplayStatusAttribute()
    {
        return $this->status === 'completed' ? 'paid' : $this->status;
    }

    public function getPayrollNumberAttribute()
    {
        return 'PAY-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function isPaid()
    {
        return in_array($this->status, ['completed', 'paid'], true);
    }

    /**
     * Scope to filter by staff
     */
    public function scopeByStaff($query, $staffId)
    {
        return $query->where('staff_id', $staffId);
    }

    /**
     * Scope to filter by period
     */
    public function scopeByPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('period_start', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
