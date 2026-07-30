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
}
