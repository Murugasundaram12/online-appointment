<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FormRecordController extends Controller
{
    public function index(Request $request)
    {
        $formRecords = \App\Models\FormRecord::with(['form', 'client'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->whereHas('client', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    })->orWhereHas('form', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    });
                });
            })
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();
        return view('form_records.index', compact('formRecords'));
    }

    public function create()
    {
        $forms = \App\Models\Form::where('is_active', true)->orderBy('name')->get();
        $clients = \App\Models\Client::orderBy('name')->get();
        return view('form_records.create', compact('forms', 'clients'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'form_id' => 'required|exists:forms,id',
            'client_id' => 'required|exists:clients,id',
            'submitted_data' => 'nullable|array',
        ]);

        $form = \App\Models\Form::findOrFail($validated['form_id']);
        $fields = $form->fields;
        if (is_string($fields)) {
            $decoded = json_decode($fields, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            $fields = is_array($decoded) ? $decoded : [];
        }
        if (isset($fields['questions']) && is_array($fields['questions'])) {
            $normalized = [];
            foreach ($fields['questions'] as $q) {
                $normalized[] = is_string($q) ? ['name' => $q] : $q;
            }
            $fields = $normalized;
        }

        $rules = [];
        if (is_array($fields)) {
            foreach ($fields as $field) {
                if (is_string($field)) {
                    $name = $field;
                    $fieldRules = ['nullable'];
                } elseif (is_array($field)) {
                    $name = $field['name'] ?? null;
                    if (!$name) {
                        continue;
                    }
                    $fieldRules = !empty($field['required']) ? ['required'] : ['nullable'];
                    $type = $field['type'] ?? 'text';
                    if ($type === 'email') $fieldRules[] = 'email';
                    if ($type === 'number') $fieldRules[] = 'numeric';
                    if ($type === 'date') $fieldRules[] = 'date';
                } else {
                    continue;
                }
                $rules['submitted_data.' . $name] = $fieldRules;
            }
        }

        if (!empty($rules)) {
            $request->validate($rules);
        }

        \App\Models\FormRecord::create([
            'form_id' => $validated['form_id'],
            'client_id' => $validated['client_id'],
            'submitted_data' => $validated['submitted_data'] ?? [],
            'submitted_at' => now(),
        ]);

        return redirect()->route('form-records.index')->with('success', 'Form record created successfully.');
    }

    public function show(string $id)
    {
        $formRecord = \App\Models\FormRecord::with(['form', 'client'])->findOrFail($id);
        return view('form_records.show', compact('formRecord'));
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        \App\Models\FormRecord::findOrFail($id)->delete();
        return redirect()->route('form-records.index')->with('success', 'Form record deleted successfully.');
    }
}
