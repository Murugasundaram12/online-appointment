<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index(Request $request)
    {
        $packages = \App\Models\Package::with('services')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->input('search') . '%');
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'active');
            })
            ->paginate($this->perPage($request));
        return view('packages.index', compact('packages'));
    }

    public function create()
    {
        $services = \App\Models\Service::where('is_active', true)->orderBy('name')->get();
        return view('packages.create', compact('services'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0.01',
            'validity_days' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'services' => 'nullable|array',
            'services.*.selected' => 'nullable|boolean',
            'services.*.quantity' => 'nullable|integer|min:1|max:100',
        ]);

        $package = \App\Models\Package::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'validity_days' => $validated['validity_days'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);
        $package->services()->sync($this->selectedServices($request));

        return redirect()->route('packages.index')->with('success', 'Package created successfully.');
    }

    public function show(string $id)
    {
        return view('packages.show', ['package' => \App\Models\Package::findOrFail($id)]);
    }

    public function edit(string $id)
    {
        $package = \App\Models\Package::findOrFail($id);
        $package->load('services');
        $services = \App\Models\Service::where('is_active', true)->orderBy('name')->get();
        return view('packages.edit', compact('package', 'services'));
    }

    public function update(Request $request, string $id)
    {
        $package = \App\Models\Package::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0.01',
            'validity_days' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'services' => 'nullable|array',
            'services.*.selected' => 'nullable|boolean',
            'services.*.quantity' => 'nullable|integer|min:1|max:100',
        ]);
        $package->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'validity_days' => $validated['validity_days'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);
        $package->services()->sync($this->selectedServices($request));

        return redirect()->route('packages.index')->with('success', 'Package updated successfully.');
    }

    public function destroy(string $id)
    {
        $package = \App\Models\Package::findOrFail($id);
        $package->services()->detach();
        $package->delete();
        return redirect()->route('packages.index')->with('success', 'Package deleted successfully.');
    }

    private function selectedServices(Request $request): array
    {
        $selected = [];
        foreach ($request->input('services', []) as $serviceId => $data) {
            if (!empty($data['selected'])) {
                $selected[(int) $serviceId] = ['quantity' => max(1, (int) ($data['quantity'] ?? 1))];
            }
        }
        return $selected;
    }
}
