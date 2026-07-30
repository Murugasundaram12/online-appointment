<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = ['name', 'description', 'price', 'billing_cycle', 'staff_limit', 'location_limit', 'appointment_limit', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
