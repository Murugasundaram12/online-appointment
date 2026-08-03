<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\PaymentRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $stats = [
            'appointments_today' => Appointment::whereDate('start_time', $today)->count(),
            'appointments_month' => Appointment::whereBetween('start_time', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'billed_month' => (float) Invoice::where('status', '!=', 'void')
                ->whereBetween('issued_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->sum('total_amount'),
            'collected_month' => (float) PaymentRecord::whereBetween('payment_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->sum('amount'),
            'clients_total' => Client::count(),
            'invoices_unpaid' => Invoice::whereIn('status', ['pending', 'partially_paid'])->count(),
        ];

        $reports = [
            'appointments' => ['title' => 'Appointments', 'description' => 'Booking volume, status breakdown and detailed appointment records.', 'icon' => 'bx-calendar-check'],
            'revenue' => ['title' => 'Revenue', 'description' => 'Billed vs collected revenue grouped by day.', 'icon' => 'bx-dollar-circle'],
            'invoices' => ['title' => 'Invoices', 'description' => 'Invoice status, balances and overdue aging.', 'icon' => 'bx-receipt'],
            'payments' => ['title' => 'Payments', 'description' => 'Payments collected, methods and transaction details.', 'icon' => 'bx-credit-card'],
            'clients' => ['title' => 'Clients', 'description' => 'New client growth, top spenders and VIP clients.', 'icon' => 'bx-user-circle'],
            'staff' => ['title' => 'Staff', 'description' => 'Per-staff performance, bookings and revenue generated.', 'icon' => 'bx-group'],
        ];

        return view('reports.index', compact('stats', 'reports'));
    }

    public function appointments(Request $request)
    {
        [$start, $end] = $this->resolveDateRange($request);

        $query = Appointment::with(['client', 'staff', 'service', 'location'])
            ->whereBetween('start_time', [$start, $end])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('staff_id'), fn ($q) => $q->where('staff_id', $request->input('staff_id')))
            ->when($request->filled('service_id'), fn ($q) => $q->where('service_id', $request->input('service_id')))
            ->when($request->filled('location_id'), fn ($q) => $q->where('location_id', $request->input('location_id')));

        $appointments = (clone $query)->orderByDesc('start_time')->paginate(15)->withQueryString();

        $summary = [
            'total' => (clone $query)->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            'booked' => (clone $query)->whereIn('status', ['pending', 'booked'])->count(),
        ];

        $filters = [
            'statuses' => Appointment::select('status')->distinct()->orderBy('status')->pluck('status'),
            'staff' => \App\Models\Staff::orderBy('name')->get(),
            'services' => \App\Models\Service::orderBy('name')->get(),
            'locations' => \App\Models\Location::orderBy('name')->get(),
        ];

        return view('reports.appointments', compact('appointments', 'summary', 'filters', 'start', 'end'));
    }

    public function revenue(Request $request)
    {
        [$start, $end] = $this->resolveDateRange($request);

        $rows = Invoice::where('status', '!=', 'void')
            ->whereBetween('issued_date', [$start, $end])
            ->selectRaw('issued_date, COUNT(*) as invoice_count, SUM(total_amount) as billed, SUM(paid_amount) as collected, SUM(total_amount - paid_amount) as balance')
            ->groupBy('issued_date')
            ->orderByDesc('issued_date')
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'billed' => (float) Invoice::where('status', '!=', 'void')->whereBetween('issued_date', [$start, $end])->sum('total_amount'),
            'collected' => (float) PaymentRecord::whereBetween('payment_date', [$start, $end])->sum('amount'),
            'outstanding' => (float) Invoice::where('status', '!=', 'void')
                ->selectRaw('SUM(total_amount - paid_amount) as balance')
                ->whereBetween('issued_date', [$start, $end])
                ->value('balance'),
            'days' => $rows->total(),
        ];

        return view('reports.revenue', compact('rows', 'summary', 'start', 'end'));
    }

    public function invoices(Request $request)
    {
        [$start, $end] = $this->resolveDateRange($request);

        $query = Invoice::with(['client', 'staff'])
            ->whereBetween('issued_date', [$start, $end])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')));

        $invoices = (clone $query)->orderByDesc('issued_date')->paginate(15)->withQueryString();

        $summary = [
            'total' => (clone $query)->count(),
            'billed' => (float) (clone $query)->sum('total_amount'),
            'collected' => (float) (clone $query)->sum('paid_amount'),
            'outstanding' => (float) (clone $query)->selectRaw('SUM(total_amount - paid_amount) as balance')->value('balance'),
            'paid' => (clone $query)->where('status', 'paid')->count(),
            'unpaid' => (clone $query)->whereIn('status', ['pending', 'partially_paid'])->count(),
            'overdue' => (clone $query)->whereIn('status', ['pending', 'partially_paid'])->where('due_date', '<', now()->toDateString())->count(),
            'void' => (clone $query)->where('status', 'void')->count(),
        ];

        $statuses = ['pending', 'partially_paid', 'paid', 'void'];

        return view('reports.invoices', compact('invoices', 'summary', 'statuses', 'start', 'end'));
    }

    public function payments(Request $request)
    {
        [$start, $end] = $this->resolveDateRange($request);

        $query = PaymentRecord::with('invoice.client')
            ->whereBetween('payment_date', [$start, $end])
            ->when($request->filled('method'), fn ($q) => $q->where('payment_method', $request->input('method')));

        $payments = (clone $query)->orderByDesc('payment_date')->paginate(15)->withQueryString();

        $summary = [
            'total' => (float) (clone $query)->sum('amount'),
            'count' => (clone $query)->count(),
            'methods' => (clone $query)->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as amount')
                ->groupBy('payment_method')->orderByDesc('amount')->get(),
        ];

        $methods = PaymentRecord::select('payment_method')->distinct()->orderBy('payment_method')->pluck('payment_method');

        return view('reports.payments', compact('payments', 'summary', 'methods', 'start', 'end'));
    }

    public function clients(Request $request)
    {
        [$start, $end] = $this->resolveDateRange($request);

        $query = Client::withCount(['appointments', 'invoices'])
            ->withSum(['invoices as total_spent' => fn ($q) => $q->where('status', '!=', 'void')], 'total_amount')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%' . trim($request->input('search')) . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('phone', 'like', $search));
            })
            ->when($request->filled('vip'), fn ($q) => $q->where('is_vip', (bool) $request->input('vip')));

        $clients = (clone $query)->orderByDesc('total_spent')->paginate(15)->withQueryString();

        $summary = [
            'new' => (clone $query)->whereNotNull('client_since')->whereBetween('client_since', [$start, $end])->count(),
            'total' => (clone $query)->count(),
            'vip' => (clone $query)->where('is_vip', true)->count(),
            'top_spend' => (float) $clients->items() && $clients->first() ? (float) $clients->first()->total_spent : 0,
        ];

        return view('reports.clients', compact('clients', 'summary', 'start', 'end'));
    }

    public function staff(Request $request)
    {
        [$start, $end] = $this->resolveDateRange($request);

        $rows = Appointment::selectRaw('staff_id, COUNT(*) as total,
                SUM(status = "completed") as completed,
                SUM(status = "cancelled") as cancelled,
                SUM(status IN ("pending", "booked")) as upcoming')
            ->whereNotNull('staff_id')
            ->whereBetween('start_time', [$start, $end])
            ->groupBy('staff_id')
            ->with('staff')
            ->orderByDesc('total')
            ->paginate(15)
            ->withQueryString();

        $revenueByStaff = Invoice::where('status', '!=', 'void')
            ->whereBetween('issued_date', [$start, $end])
            ->whereNotNull('staff_id')
            ->selectRaw('staff_id, SUM(total_amount) as total')
            ->groupBy('staff_id')
            ->pluck('total', 'staff_id');

        $summary = [
            'total' => Appointment::whereBetween('start_time', [$start, $end])->count(),
            'with_staff' => $rows->total(),
            'revenue' => (float) $revenueByStaff->sum(),
        ];

        return view('reports.staff', compact('rows', 'revenueByStaff', 'summary', 'start', 'end'));
    }

    public function export(string $type, Request $request)
    {
        [$start, $end] = $this->resolveDateRange($request);
        $stamp = $start->format('Y-m-d') . '_to_' . $end->format('Y-m-d');

        $payload = match ($type) {
            'appointments' => $this->exportAppointments($request, $start, $end),
            'revenue' => $this->exportRevenue($start, $end),
            'invoices' => $this->exportInvoices($request, $start, $end),
            'payments' => $this->exportPayments($request, $start, $end),
            'clients' => $this->exportClients($request, $start, $end),
            'staff' => $this->exportStaff($start, $end),
            default => abort(404),
        };

        return response()->streamDownload(function () use ($payload) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $payload['headers']);
            foreach ($payload['rows'] as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $type . '-report-' . $stamp . '.csv', ['Content-Type' => 'text/csv']);
    }

    private function exportAppointments(Request $request, Carbon $start, Carbon $end): array
    {
        $appointments = Appointment::with(['client', 'staff', 'service', 'location'])
            ->whereBetween('start_time', [$start, $end])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('staff_id'), fn ($q) => $q->where('staff_id', $request->input('staff_id')))
            ->when($request->filled('service_id'), fn ($q) => $q->where('service_id', $request->input('service_id')))
            ->when($request->filled('location_id'), fn ($q) => $q->where('location_id', $request->input('location_id')))
            ->orderByDesc('start_time')
            ->get();

        return [
            'headers' => ['Start Time', 'End Time', 'Client', 'Staff', 'Service', 'Location', 'Status', 'Reference'],
            'rows' => $appointments->map(fn ($a) => [
                optional($a->start_time)->format('Y-m-d H:i'),
                optional($a->end_time)->format('Y-m-d H:i'),
                optional($a->client)->name,
                optional($a->staff)->name,
                optional($a->service)->name,
                optional($a->location)->name,
                $a->status,
                $a->reference ?? $a->id,
            ])->all(),
        ];
    }

    private function exportRevenue(Carbon $start, Carbon $end): array
    {
        $rows = Invoice::where('status', '!=', 'void')
            ->whereBetween('issued_date', [$start, $end])
            ->selectRaw('issued_date, COUNT(*) as invoice_count, SUM(total_amount) as billed, SUM(paid_amount) as collected, SUM(total_amount - paid_amount) as balance')
            ->groupBy('issued_date')
            ->orderByDesc('issued_date')
            ->get();

        return [
            'headers' => ['Date', 'Invoices', 'Billed', 'Collected', 'Outstanding Balance'],
            'rows' => $rows->map(fn ($r) => [$r->issued_date, $r->invoice_count, $r->billed, $r->collected, $r->balance])->all(),
        ];
    }

    private function exportInvoices(Request $request, Carbon $start, Carbon $end): array
    {
        $invoices = Invoice::with(['client', 'staff'])
            ->whereBetween('issued_date', [$start, $end])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('issued_date')
            ->get();

        return [
            'headers' => ['Invoice Number', 'Client', 'Staff', 'Issued Date', 'Due Date', 'Total', 'Paid', 'Balance', 'Status'],
            'rows' => $invoices->map(fn ($i) => [
                $i->invoice_number,
                optional($i->client)->name,
                optional($i->staff)->name,
                optional($i->issued_date)->toDateString(),
                optional($i->due_date)->toDateString(),
                $i->total_amount,
                $i->paid_amount,
                max(0, (float) $i->total_amount - (float) $i->paid_amount),
                $i->status,
            ])->all(),
        ];
    }

    private function exportPayments(Request $request, Carbon $start, Carbon $end): array
    {
        $payments = PaymentRecord::with('invoice.client')
            ->whereBetween('payment_date', [$start, $end])
            ->when($request->filled('method'), fn ($q) => $q->where('payment_method', $request->input('method')))
            ->orderByDesc('payment_date')
            ->get();

        return [
            'headers' => ['Payment Date', 'Amount', 'Method', 'Transaction ID', 'Invoice', 'Client'],
            'rows' => $payments->map(fn ($p) => [
                optional($p->payment_date)->toDateString(),
                $p->amount,
                $p->payment_method,
                $p->transaction_id,
                optional($p->invoice)->invoice_number,
                optional($p->invoice->client)->name,
            ])->all(),
        ];
    }

    private function exportClients(Request $request, Carbon $start, Carbon $end): array
    {
        $clients = Client::withCount('appointments')
            ->withSum(['invoices as total_spent' => fn ($q) => $q->where('status', '!=', 'void')], 'total_amount')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%' . trim($request->input('search')) . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('phone', 'like', $search));
            })
            ->when($request->filled('vip'), fn ($q) => $q->where('is_vip', (bool) $request->input('vip')))
            ->orderByDesc('total_spent')
            ->get();

        return [
            'headers' => ['Name', 'Email', 'Phone', 'City', 'Client Since', 'VIP', 'Appointments', 'Total Spent'],
            'rows' => $clients->map(fn ($c) => [
                $c->name,
                $c->email,
                $c->phone,
                $c->city,
                optional($c->client_since)->toDateString(),
                $c->is_vip ? 'Yes' : 'No',
                $c->appointments_count,
                $c->total_spent,
            ])->all(),
        ];
    }

    private function exportStaff(Carbon $start, Carbon $end): array
    {
        $rows = Appointment::selectRaw('staff_id, COUNT(*) as total,
                SUM(status = "completed") as completed,
                SUM(status = "cancelled") as cancelled')
            ->whereNotNull('staff_id')
            ->whereBetween('start_time', [$start, $end])
            ->groupBy('staff_id')
            ->with('staff')
            ->orderByDesc('total')
            ->get();

        $revenueByStaff = Invoice::where('status', '!=', 'void')
            ->whereBetween('issued_date', [$start, $end])
            ->whereNotNull('staff_id')
            ->selectRaw('staff_id, SUM(total_amount) as total')
            ->groupBy('staff_id')
            ->pluck('total', 'staff_id');

        return [
            'headers' => ['Staff', 'Appointments', 'Completed', 'Cancelled', 'Revenue Generated'],
            'rows' => $rows->map(fn ($r) => [
                optional($r->staff)->name,
                $r->total,
                $r->completed,
                $r->cancelled,
                $revenueByStaff[$r->staff_id] ?? 0,
            ])->all(),
        ];
    }

    private function resolveDateRange(Request $request): array
    {
        $start = Carbon::parse($request->input('start_date', now()->startOfMonth()->toDateString()))->startOfDay();
        $end = Carbon::parse($request->input('end_date', now()->endOfMonth()->toDateString()))->endOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }
}
