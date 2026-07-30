<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::withCount('appointments')
            ->latest()
            ->paginate(10);
        $newClientsCount = Client::where('created_at', '>=', now()->subDays(30))->count();

        return view('clients.index', compact('clients', 'newClientsCount'));
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateClient($request);

        $validated['is_vip'] = $request->has('is_vip')
            ? $request->boolean('is_vip')
            : $this->tagsToVip($request->input('tags'));
        unset($validated['tags']);

        Client::create($validated);

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    public function show(string $id)
    {
        $client = Client::with(['appointments.staff', 'appointments.service', 'invoices'])
            ->withCount('appointments')
            ->findOrFail($id);

        return view('clients.show', compact('client'));
    }

    public function edit(string $id)
    {
        $client = Client::findOrFail($id);
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, string $id)
    {
        $client = Client::findOrFail($id);
        $validated = $this->validateClient($request, $client);

        $validated['is_vip'] = $request->has('is_vip')
            ? $request->boolean('is_vip')
            : $this->tagsToVip($request->input('tags'));
        unset($validated['tags']);
        $client->update($validated);

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    private function tagsToVip(?string $tags): bool
    {
        if ($tags === null) {
            return false;
        }

        return stripos($tags, 'vip') !== false;
    }

    public function destroy(string $id)
    {
        $client = Client::withCount(['appointments', 'invoices', 'formRecords'])->findOrFail($id);

        if ($client->appointments_count > 0 || $client->invoices_count > 0 || $client->form_records_count > 0) {
            return redirect()
                ->route('clients.index')
                ->with('error', 'This client has appointments, invoices, or form records and cannot be deleted. Keep the record for history.');
        }

        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client deleted successfully.');
    }

    private function validateClient(Request $request, ?Client $client = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('clients', 'email')->ignore($client?->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'client_since' => ['nullable', 'date'],
            'is_vip' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
