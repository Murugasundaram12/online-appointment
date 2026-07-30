<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index()
    {
        $locations = \App\Models\Location::paginate(10);
        return view('locations.index', compact('locations'));
    }

    public function create()
    {
        return view('locations.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            // Add other validation rules
        ]);

        \App\Models\Location::create($request->all());

        return redirect()->route('locations.index')->with('success', 'Location created successfully.');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        $location = \App\Models\Location::findOrFail($id);
        return view('locations.create', compact('location')); // Reusing create view for simplicity if applicable, or locations.edit
    }

    public function update(Request $request, string $id)
    {
        $location = \App\Models\Location::findOrFail($id);
        $location->update($request->all());

        return redirect()->route('locations.index')->with('success', 'Location updated successfully.');
    }

    public function destroy(string $id)
    {
        \App\Models\Location::findOrFail($id)->delete();
        return redirect()->route('locations.index')->with('success', 'Location deleted successfully.');
    }
}
