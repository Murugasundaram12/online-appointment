<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FormController extends Controller
{
    public function index(Request $request)
    {
        $forms = \App\Models\Form::when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'active');
            })
            ->paginate($this->perPage($request));
        return view('forms.index', compact('forms'));
    }

    public function create()
    {
        return view('forms.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fields_json' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $fields = $this->parseFields($validated['fields_json'] ?? null);
        \App\Models\Form::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'fields' => $fields,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('forms.index')->with('success', 'Form created successfully.');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        $form = \App\Models\Form::findOrFail($id);
        return view('forms.edit', compact('form'));
    }

    public function update(Request $request, string $id)
    {
        $form = \App\Models\Form::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fields_json' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
        $form->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'fields' => $this->parseFields($validated['fields_json'] ?? null),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('forms.index')->with('success', 'Form updated successfully.');
    }

    public function destroy(string $id)
    {
        \App\Models\Form::findOrFail($id)->delete();
        return redirect()->route('forms.index')->with('success', 'Form deleted successfully.');
    }

    private function parseFields(?string $json): array
    {
        if (!$json) {
            return [['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea', 'required' => false]];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? array_values($decoded) : [['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea', 'required' => false]];
    }
}
