<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        $services = \App\Models\Service::paginate(10);
        $categories = \App\Models\ServiceCategory::all();
        return view('services.index', compact('services', 'categories'));
    }

    public function create()
    {
        return view('services.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_category_id' => 'nullable|exists:service_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string|in:in_person,online',
            'price' => 'required|numeric|min:0',
            'duration_minutes' => 'required|integer|min:1',
            'buffer_minutes' => 'nullable|integer|min:0',
            'color' => 'nullable|string|max:7',
            'is_active' => 'nullable|boolean',
        ]);

        if (!isset($validated['is_active'])) {
            $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        }

        \App\Models\Service::create($validated);

        return redirect()->route('services.index')->with('success', 'Service created successfully.');
    }

    public function show(string $id)
    {
        return view('services.show', ['service' => \App\Models\Service::findOrFail($id)]);
    }

    public function edit(string $id)
    {
        $service = \App\Models\Service::findOrFail($id);
        return view('services.edit', compact('service'));
    }

    public function update(Request $request, string $id)
    {
        $service = \App\Models\Service::findOrFail($id);
        $validated = $request->validate([
            'service_category_id' => 'nullable|exists:service_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string|in:in_person,online',
            'price' => 'required|numeric|min:0',
            'duration_minutes' => 'required|integer|min:1',
            'buffer_minutes' => 'nullable|integer|min:0',
            'color' => 'nullable|string|max:7',
            'is_active' => 'nullable|boolean',
        ]);

        if (!isset($validated['is_active'])) {
            $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : false;
        }

        $service->update($validated);

        return redirect()->route('services.index')->with('success', 'Service updated successfully.');
    }

    public function destroy(string $id)
    {
        $service = \App\Models\Service::withCount('appointments')->findOrFail($id);

        if ($service->appointments_count > 0) {
            return redirect()
                ->route('services.index')
                ->with('error', 'This service has scheduled appointments and cannot be deleted. Deactivate it instead.');
        }

        $service->delete();
        return redirect()->route('services.index')->with('success', 'Service deleted successfully.');
    }
}

