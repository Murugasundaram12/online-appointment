<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        if (SubscriptionPlan::count() === 0) {
            SubscriptionPlan::insert([
                ['name' => 'Starter', 'description' => 'Small teams getting started.', 'price' => 0, 'billing_cycle' => 'monthly', 'staff_limit' => 3, 'location_limit' => 1, 'appointment_limit' => 100, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Business', 'description' => 'Growing service businesses.', 'price' => 29, 'billing_cycle' => 'monthly', 'staff_limit' => 15, 'location_limit' => 5, 'appointment_limit' => 1000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
        $plans = SubscriptionPlan::where('is_active', true)->get();
        $currentSubscription = Subscription::with('plan')->latest()->first();
        $history = Subscription::with('plan')->latest()->paginate($this->perPage($request));
        return view('subscription.index', compact('plans', 'currentSubscription', 'history'));
    }

    public function activate(Request $request)
    {
        $validated = $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'payment_status' => 'required|in:paid,unpaid',
        ]);

        $plan = SubscriptionPlan::findOrFail($validated['subscription_plan_id']);
        Subscription::where('status', 'active')->update(['status' => 'cancelled']);
        Subscription::create([
            'staff_id' => optional(Auth::guard('staff')->user())->id,
            'subscription_plan_id' => $plan->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? now()->addMonth()->toDateString(),
            'status' => 'active',
            'amount' => $plan->price,
            'payment_status' => $validated['payment_status'],
        ]);

        return redirect()->route('subscription.index')->with('success', 'Subscription activated successfully.');
    }
}
