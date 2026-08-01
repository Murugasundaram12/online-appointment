<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BusinessSettingController extends Controller
{
    public function index()
    {
        $settings = \App\Models\BusinessSetting::pluck('value', 'key')->toArray();
        return view('business_settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'business_email' => 'nullable|email|max:255',
            'business_phone' => 'nullable|string|max:50',
            'business_address' => 'nullable|string|max:1000',
            'currency' => 'required|string|max:10',
            'timezone' => 'required|timezone',
            'date_format' => 'required|string|max:30',
            'time_format' => 'required|string|max:30',
            'appointment_interval' => 'required|integer|min:5|max:240',
            'default_appointment_status' => 'required|in:pending,booked,completed,cancelled',
            'invoice_prefix' => 'required|string|max:20',
            'business_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('business_logo')) {
            $path = $request->file('business_logo')->store('business', 'public');
            \App\Models\BusinessSetting::updateOrCreate(
                ['key' => 'business_logo'],
                ['value' => $path, 'group' => 'general']
            );
        }

        unset($validated['business_logo']);

        foreach ($validated as $key => $value) {
            \App\Models\BusinessSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => in_array($key, ['currency', 'timezone', 'date_format', 'time_format']) ? 'defaults' : 'general']
            );
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}
