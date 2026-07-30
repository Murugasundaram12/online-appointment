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
            'name' => 'required',
            'price' => 'required',
            // Add other validation rules
        ]);

        \App\Models\Service::create($request->all());

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
        $request->validate([
            'name' => 'required',
            'price' => 'required',
            'duration_minutes' => 'required'
        ]);

        $service->update($request->all());

        return redirect()->route('services.index')->with('success', 'Service updated successfully.');
    }

    public function destroy(string $id)
    {
        \App\Models\Service::findOrFail($id)->delete();
        return redirect()->route('services.index')->with('success', 'Service deleted successfully.');
    }
}

