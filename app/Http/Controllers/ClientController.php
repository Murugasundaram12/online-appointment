<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $clients = Client::withCount('appointments')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($this->perPage($request));
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
        $validated['client_since'] = $this->normalizeClientSince($validated['client_since'] ?? null);
        unset($validated['tags']);

        Client::create($validated);

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    public function show(string $id)
    {
        $client = Client::withCount([
                'appointments',
                'appointments as upcoming_appointments_count' => fn ($query) => $query
                    ->where('start_time', '>=', now())
                    ->where('status', '!=', 'cancelled'),
                'appointments as completed_appointments_count' => fn ($query) => $query->where('status', 'completed'),
                'appointments as cancelled_appointments_count' => fn ($query) => $query->where('status', 'cancelled'),
            ])
            ->withSum(['invoices as total_invoiced' => fn ($query) => $query->where('status', '!=', 'void')], 'total_amount')
            ->withSum(['invoices as total_paid' => fn ($query) => $query->where('status', '!=', 'void')], 'paid_amount')
            ->findOrFail($id);

        $upcoming = $client->appointments()
            ->with(['staff', 'service', 'location'])
            ->where('start_time', '>=', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->limit(5)
            ->get();

        $history = $client->appointments()
            ->with(['staff', 'service', 'location'])
            ->whereIn('status', ['completed', 'cancelled'])
            ->orderByDesc('start_time')
            ->paginate(10, ['*'], 'history_page');

        $invoices = $client->invoices()
            ->with('staff')
            ->orderByDesc('issued_date')
            ->paginate(10, ['*'], 'invoices_page');

        $payments = \App\Models\PaymentRecord::with('invoice')
            ->whereHas('invoice', fn ($query) => $query->where('client_id', $client->id))
            ->orderByDesc('payment_date')
            ->paginate(10, ['*'], 'payments_page');

        $formRecords = $client->formRecords()->with('form')->latest('submitted_at')->limit(10)->get();

        $totalInvoiced = (float) ($client->total_invoiced ?? 0);
        $totalPaid = (float) ($client->total_paid ?? 0);
        $outstanding = max(0, $totalInvoiced - $totalPaid);

        return view('clients.show', compact(
            'client',
            'upcoming',
            'history',
            'invoices',
            'payments',
            'formRecords',
            'totalInvoiced',
            'totalPaid',
            'outstanding'
        ));
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
        $validated['client_since'] = $this->normalizeClientSince($validated['client_since'] ?? null);
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

    private function normalizeClientSince(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d', $date)->toDateString();
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
        $emailRule = Rule::unique('clients', 'email');

        if ($client && $request->input('email') !== $client->email) {
            $emailRule->ignore($client->id);
        }

        $emailRules = [
            'nullable',
            'email',
            'max:255',
        ];

        if (!$client || $request->input('email') !== $client->email) {
            $emailRules[] = $emailRule;
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => $emailRules,
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'client_since' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today', 'after:1900-01-01'],
            'is_vip' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'string', 'max:255'],
        ], [
            'email.unique' => 'This email is already used by another client.',
        ]);
    }
}
