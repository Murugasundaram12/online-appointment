<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = ['staff_id', 'subscription_plan_id', 'start_date', 'end_date', 'status', 'amount', 'payment_status'];

    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public static function checkLimit(string $type): bool
    {
        $activeSub = self::with('plan')
            ->where('status', 'active')
            ->where('end_date', '>=', now()->toDateString())
            ->latest()
            ->first();

        if (!$activeSub || !$activeSub->plan) {
            $staffLimit = 3;
            $locationLimit = 1;
            $appointmentLimit = 100;
        } else {
            $staffLimit = $activeSub->plan->staff_limit;
            $locationLimit = $activeSub->plan->location_limit;
            $appointmentLimit = $activeSub->plan->appointment_limit;
        }

        if ($type === 'staff' && $staffLimit !== null) {
            return \App\Models\Staff::where('is_active', true)->count() < $staffLimit;
        }

        if ($type === 'location' && $locationLimit !== null) {
            return \App\Models\Location::where('is_active', true)->count() < $locationLimit;
        }

        if ($type === 'appointment' && $appointmentLimit !== null) {
            $monthStart = now()->startOfMonth()->toDateTimeString();
            $monthEnd = now()->endOfMonth()->toDateTimeString();
            return \App\Models\Appointment::whereBetween('created_at', [$monthStart, $monthEnd])->count() < $appointmentLimit;
        }

        return true;
    }
}
