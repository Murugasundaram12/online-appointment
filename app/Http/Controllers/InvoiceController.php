<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\BusinessSetting;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\PaymentRecord;
use App\Models\Staff;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $staff = Auth::guard('staff')->user();

        $invoices = Invoice::with(['client', 'staff'])
            ->when($staff && !in_array($staff->access_level, ['admin', 'business_owner'], true) && !is_null($staff->location_id), function ($query) use ($staff) {
                $query->where(function ($q) use ($staff) {
                    $q->where('staff_id', $staff->id)
                        ->orWhereHas('staff', fn ($sq) => $sq->where('location_id', $staff->location_id))
                        ->orWhereHas('appointment', fn ($aq) => $aq->where('location_id', $staff->location_id));
                });
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('id', is_numeric($search) ? (int) $search : 0)
                        ->orWhereHas('client', function ($cq) use ($search) {
                            $cq->where('name', 'like', "%{$search}%");
                        })
                        ->orWhere('status', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->latest()
            ->paginate($this->perPage($request));

        return view('invoices.index', compact('invoices'));
    }

    public function create()
    {
        $clients = Client::orderBy('name')->get(['id', 'name', 'email', 'phone']);
        $staff = Staff::where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']);
        $appointments = Appointment::with(['client:id,name', 'staff:id,name', 'service:id,name,price'])
            ->where('status', '!=', 'cancelled')
            ->whereDoesntHave('invoice')
            ->latest('start_time')
            ->limit(250)
            ->get();

        return view('invoices.create', [
            'clients' => $clients,
            'staff' => $staff,
            'appointments' => $appointments,
            'nextInvoiceNumber' => $this->nextInvoiceNumber(),
            'currency' => BusinessSetting::where('key', 'currency')->value('value') ?: '$',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'appointment_id' => ['nullable', 'exists:appointments,id'],
            'client_id' => 'required|exists:clients,id',
            'staff_id' => 'required|exists:staff,id',
            'invoice_number' => ['nullable', 'string', 'max:255', Rule::unique('invoices', 'invoice_number')],
            'total_amount' => 'required|numeric|min:0.01',
            'paid_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:outstanding,paid,partially_paid,void',
            'issued_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issued_date',
        ], [
            'appointment_id.unique' => 'An invoice already exists for the selected appointment.',
            'invoice_number.unique' => 'This invoice number is already used by another invoice.',
        ]);

        $invoice = DB::transaction(function () use ($validated) {
            if (!empty($validated['appointment_id'])) {
                $appointment = Appointment::with(['client', 'staff', 'service'])->findOrFail($validated['appointment_id']);

                if ($appointment->status === 'cancelled') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'appointment_id' => 'Cannot create an invoice for a cancelled appointment.',
                    ]);
                }

                if ($appointment->invoice()->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'appointment_id' => 'An invoice already exists for the selected appointment.',
                    ]);
                }
            }

            $validated['invoice_number'] = $validated['invoice_number'] ?? $this->nextInvoiceNumber();
            $initialPaid = min((float) ($validated['paid_amount'] ?? 0), (float) $validated['total_amount']);
            $totalAmount = (float) $validated['total_amount'];

            $validated['paid_amount'] = 0;
            $validated['status'] = ($validated['status'] ?? null) === 'void' ? 'void' : 'outstanding';

            $invoice = Invoice::create($validated);

            if ($initialPaid > 0 && $invoice->status !== 'void') {
                PaymentRecord::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $initialPaid,
                    'payment_method' => 'cash',
                    'payment_date' => $validated['issued_date'] ?? now()->toDateString(),
                    'transaction_id' => 'INIT-' . $invoice->id,
                ]);

                $invoice->paid_amount = $initialPaid;
                $invoice->status = $this->statusForAmounts($initialPaid, $totalAmount);
                $invoice->save();
            }

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice->id)->with('success', 'Invoice created successfully.');
    }

    public function show(string $id)
    {
        $invoice = $this->invoiceQuery()->findOrFail($id);
        $this->authorizeInvoiceAccess($invoice);

        return view('invoices.show', $this->invoiceViewData($invoice));
    }

    public function download(string $id)
    {
        $invoice = $this->invoiceQuery()->findOrFail($id);
        $this->authorizeInvoiceAccess($invoice);

        $data = $this->invoiceViewData($invoice, true);
        $filename = 'invoice-' . $invoice->invoice_number . '.pdf';

        try {
            return Pdf::loadView('invoices.pdf', $data)
                ->setPaper('a4', 'portrait')
                ->download($filename);
        } catch (\Throwable $exception) {
            Log::error('Invoice PDF generation failed', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'exception' => $exception->getMessage(),
            ]);

            return $this->htmlInvoiceFallback($invoice, $data);
        }
    }

    public function edit(string $id)
    {
        $invoice = Invoice::with(['staff', 'appointment'])->findOrFail($id);
        $this->authorizeInvoiceAccess($invoice);

        return view('invoices.edit', compact('invoice'));
    }

    public function update(Request $request, string $id)
    {
        $invoice = Invoice::with(['staff', 'appointment', 'payments'])->findOrFail($id);
        $this->authorizeInvoiceAccess($invoice);

        $validated = $request->validate([
            'appointment_id' => 'nullable|exists:appointments,id',
            'client_id' => 'required|exists:clients,id',
            'staff_id' => 'required|exists:staff,id',
            'invoice_number' => ['required', 'string', 'max:255', Rule::unique('invoices', 'invoice_number')->ignore($invoice->id)],
            'total_amount' => 'required|numeric|min:0.01',
            'status' => 'required|in:outstanding,paid,partially_paid,void',
            'issued_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issued_date',
        ], [
            'invoice_number.unique' => 'This invoice number is already used by another invoice.',
        ]);

        DB::transaction(function () use ($invoice, $validated) {
            $totalAmount = (float) $validated['total_amount'];
            $actualPaidFromRecords = (float) $invoice->payments()->sum('amount');

            if ($totalAmount < $actualPaidFromRecords) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'total_amount' => 'Total amount ($' . number_format($totalAmount, 2) . ') cannot be less than total recorded payments ($' . number_format($actualPaidFromRecords, 2) . ').',
                ]);
            }

            // Payment records are source of truth for paid_amount
            $validated['paid_amount'] = min($actualPaidFromRecords, $totalAmount);

            if ($validated['status'] !== 'void') {
                $validated['status'] = $this->statusForAmounts($validated['paid_amount'], $totalAmount);
            }

            $invoice->update($validated);
        });

        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
    }

    public function destroy(string $id)
    {
        $invoice = Invoice::with(['staff', 'appointment'])->findOrFail($id);
        $this->authorizeInvoiceAccess($invoice);

        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', 'Invoice deleted successfully.');
    }

    private function authorizeInvoiceAccess(Invoice $invoice): void
    {
        $staff = Auth::guard('staff')->user();
        if (!$staff) {
            abort(403, 'Unauthorized action.');
        }

        if (in_array($staff->access_level, ['admin', 'business_owner'], true)) {
            return;
        }

        if (is_null($staff->location_id)) {
            return;
        }

        $invoiceStaffLocation = $invoice->staff?->location_id;
        $appointmentLocation = $invoice->appointment?->location_id;

        $matchesLocation = ($invoiceStaffLocation && (int) $invoiceStaffLocation === (int) $staff->location_id)
            || ($appointmentLocation && (int) $appointmentLocation === (int) $staff->location_id)
            || ((int) $invoice->staff_id === (int) $staff->id);

        if (!$matchesLocation) {
            abort(403, 'Unauthorized access to invoice at another location.');
        }
    }

    private function nextInvoiceNumber(): string
    {
        $prefix = BusinessSetting::where('key', 'invoice_prefix')->value('value') ?: 'INV';
        return $prefix . '-' . now()->format('Ymd') . '-' . str_pad((string) ((Invoice::max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    private function statusForAmounts(float $paid, float $total): string
    {
        if ($paid >= $total) {
            return 'paid';
        }
        return $paid > 0 ? 'partially_paid' : 'outstanding';
    }

    private function invoiceQuery()
    {
        return Invoice::with([
            'client',
            'staff.location',
            'appointment.service',
            'appointment.location',
            'payments' => fn ($query) => $query->with('insuranceCompany')->orderBy('payment_date')->orderBy('created_at'),
        ]);
    }

    private function invoiceViewData(Invoice $invoice, bool $forPdf = false): array
    {
        $settings = BusinessSetting::pluck('value', 'key')->toArray();
        $currency = $settings['currency'] ?? '$';
        $balance = max(0, (float) $invoice->total_amount - (float) $invoice->paid_amount);
        $settings['business_logo'] = $this->resolveInvoiceLogo($settings['business_logo'] ?? ($settings['logo'] ?? null), $forPdf);

        return [
            'invoice' => $invoice,
            'settings' => $settings,
            'currency' => $currency,
            'balance' => $balance,
            'forPdf' => $forPdf,
            'dateFormat' => $settings['date_format'] ?? 'd M Y',
            'timeFormat' => $settings['time_format'] ?? 'g:i A',
            'timezone' => $settings['timezone'] ?? config('app.timezone'),
        ];
    }

    private function htmlInvoiceFallback(Invoice $invoice, array $data)
    {
        return response()
            ->view('invoices.pdf', $data)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'attachment; filename="invoice-' . $invoice->invoice_number . '.html"');
    }

    private function resolveInvoiceLogo(?string $logo, bool $forPdf): ?string
    {
        if (!$logo) {
            return null;
        }

        if (filter_var($logo, FILTER_VALIDATE_URL)) {
            return $forPdf ? null : $logo;
        }

        $relative = ltrim(str_replace('\\', '/', $logo), '/');

        if (is_file(public_path($relative))) {
            return $forPdf ? public_path($relative) : asset($relative);
        }

        if (is_file(public_path('storage/' . $relative))) {
            return $forPdf ? public_path('storage/' . $relative) : asset('storage/' . $relative);
        }

        if (is_file(storage_path('app/public/' . $relative))) {
            return $forPdf ? storage_path('app/public/' . $relative) : asset('storage/' . $relative);
        }

        return null;
    }
}
