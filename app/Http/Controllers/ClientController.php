<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\PaymentRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $clients = Client::withCount('appointments')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('client_code', 'like', "%{$search}%")
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

    public function store(StoreClientRequest $request)
    {
        $validated = $request->validated();
        $validated['name'] = trim(($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? ''));

        $client = Client::create($validated);

        return redirect()->route('clients.show', $client->id)
            ->with('success', 'Client profile created successfully.');
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

        $lastVisit = $client->appointments()
            ->where('status', 'completed')
            ->where('start_time', '<=', now())
            ->latest('start_time')
            ->value('start_time');

        $nextAppointment = $client->appointments()
            ->whereIn('status', ['pending', 'booked'])
            ->where('start_time', '>=', now())
            ->oldest('start_time')
            ->value('start_time');

        $allAppointments = $client->appointments()
            ->with(['staff', 'service', 'location'])
            ->orderByDesc('start_time')
            ->paginate(10, ['*'], 'appts_page');

        $invoices = $client->invoices()
            ->with('staff')
            ->orderByDesc('issued_date')
            ->paginate(10, ['*'], 'invoices_page');

        $payments = PaymentRecord::with('invoice')
            ->whereHas('invoice', fn ($query) => $query->where('client_id', $client->id))
            ->orderByDesc('payment_date')
            ->paginate(10, ['*'], 'payments_page');

        $packages = Package::where('is_active', true)->limit(5)->get();

        $formRecords = $client->formRecords()
            ->with('form')
            ->latest('submitted_at')
            ->limit(10)
            ->get();

        $totalInvoiced = (float) ($client->total_invoiced ?? 0);
        $totalPaid = (float) ($client->total_paid ?? 0);
        $outstanding = max(0, $totalInvoiced - $totalPaid);

        // Build Timeline events
        $timeline = [];
        $timeline[] = [
            'title' => 'Client Account Created',
            'date' => $client->created_at,
            'icon' => 'bx-user-plus',
            'color' => 'bg-primary',
            'description' => "Client profile registered with ID {$client->client_code}.",
        ];

        foreach ($allAppointments->take(5) as $app) {
            $statusColor = $app->status === 'completed' ? 'bg-success' : ($app->status === 'cancelled' ? 'bg-danger' : 'bg-info');
            $timeline[] = [
                'title' => "Appointment " . ucfirst($app->status),
                'date' => $app->created_at ?? $app->start_time,
                'icon' => 'bx-calendar',
                'color' => $statusColor,
                'description' => "Service: {$app->service?->name} with {$app->staff?->name} on " . ($app->start_time ? $app->start_time->format('M d, Y g:i A') : 'N/A'),
            ];
        }

        foreach ($invoices->take(5) as $inv) {
            $timeline[] = [
                'title' => "Invoice #{$inv->invoice_number} Generated",
                'date' => $inv->created_at ?? $inv->issued_date,
                'icon' => 'bx-receipt',
                'color' => 'bg-warning',
                'description' => "Total Amount: $" . number_format($inv->total_amount, 2) . " | Status: " . ucfirst($inv->status),
            ];
        }

        usort($timeline, fn($a, $b) => strtotime((string)$b['date']) <=> strtotime((string)$a['date']));

        return view('clients.show', compact(
            'client',
            'lastVisit',
            'nextAppointment',
            'allAppointments',
            'invoices',
            'payments',
            'packages',
            'formRecords',
            'timeline',
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

    public function update(StoreClientRequest $request, string $id)
    {
        $client = Client::findOrFail($id);
        $validated = $request->validated();
        $validated['name'] = trim(($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? ''));

        $client->update($validated);

        return redirect()->route('clients.show', $client->id)
            ->with('success', 'Client profile updated successfully.');
    }

    public function destroy(string $id)
    {
        $client = Client::withCount(['appointments', 'invoices', 'formRecords'])->findOrFail($id);

        if ($client->appointments_count > 0 || $client->invoices_count > 0 || $client->form_records_count > 0) {
            return redirect()
                ->route('clients.index')
                ->with('error', 'This client has appointments, invoices, or form records and cannot be deleted.');
        }

        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client deleted successfully.');
    }
}
