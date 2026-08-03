<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $locations = \App\Models\Location::when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->paginate($this->perPage($request));
        return view('locations.index', compact('locations'));
    }

    public function create()
    {
        return view('locations.create');
    }

    public function store(Request $request)
    {
        if (!\App\Models\Subscription::checkLimit('location')) {
            return back()->withInput()->with('error', 'Your current subscription plan location limit has been reached. Please upgrade.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'timezone' => 'nullable|string|timezone',
            'color' => 'nullable|string|max:7',
            'is_active' => 'nullable|boolean',
        ]);

        if (!isset($validated['is_active'])) {
            $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        }

        \App\Models\Location::create($validated);

        return redirect()->route('locations.index')->with('success', 'Location created successfully.');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        $location = \App\Models\Location::findOrFail($id);
        return view('locations.create', compact('location'));
    }

    public function update(Request $request, string $id)
    {
        $location = \App\Models\Location::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'timezone' => 'nullable|string|timezone',
            'color' => 'nullable|string|max:7',
            'is_active' => 'nullable|boolean',
        ]);

        if (!isset($validated['is_active'])) {
            $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : false;
        }

        $location->update($validated);

        return redirect()->route('locations.index')->with('success', 'Location updated successfully.');
    }

    public function destroy(string $id)
    {
        \App\Models\Location::findOrFail($id)->delete();
        return redirect()->route('locations.index')->with('success', 'Location deleted successfully.');
    }
}
