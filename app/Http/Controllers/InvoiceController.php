<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\BusinessSetting;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Staff;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = \App\Models\Invoice::with(['client', 'staff'])
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

                if ($appointment->invoice()->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'appointment_id' => 'An invoice already exists for the selected appointment.',
                    ]);
                }
            }

            $validated['invoice_number'] = $validated['invoice_number'] ?? $this->nextInvoiceNumber();
            $validated['paid_amount'] = min((float) ($validated['paid_amount'] ?? 0), (float) $validated['total_amount']);
            $validated['status'] = $validated['status'] ?? $this->statusForAmounts($validated['paid_amount'], $validated['total_amount']);

            return Invoice::create($validated);
        });

        return redirect()->route('invoices.show', $invoice->id)->with('success', 'Invoice created successfully.');
    }

    public function show(string $id)
    {
        $invoice = $this->invoiceQuery()->findOrFail($id);

        return view('invoices.show', $this->invoiceViewData($invoice));
    }

    public function download(string $id)
    {
        $invoice = $this->invoiceQuery()->findOrFail($id);
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
        $invoice = \App\Models\Invoice::findOrFail($id);
        return view('invoices.edit', compact('invoice'));
    }

    public function update(Request $request, string $id)
    {
        $invoice = \App\Models\Invoice::findOrFail($id);
        $validated = $request->validate([
            'appointment_id' => 'nullable|exists:appointments,id',
            'client_id' => 'required|exists:clients,id',
            'staff_id' => 'required|exists:staff,id',
            'invoice_number' => ['required', 'string', 'max:255', Rule::unique('invoices', 'invoice_number')->ignore($invoice->id)],
            'total_amount' => 'required|numeric|min:0.01',
            'paid_amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:outstanding,paid,partially_paid,void',
            'issued_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issued_date',
        ], [
            'invoice_number.unique' => 'This invoice number is already used by another invoice.',
        ]);
        $validated['paid_amount'] = min((float) ($validated['paid_amount'] ?? 0), (float) $validated['total_amount']);
        if ($validated['status'] !== 'void') {
            $validated['status'] = $this->statusForAmounts($validated['paid_amount'], $validated['total_amount']);
        }
        $invoice->update($validated);

        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
    }

    public function destroy(string $id)
    {
        \App\Models\Invoice::findOrFail($id)->delete();
        return redirect()->route('invoices.index')->with('success', 'Invoice deleted successfully.');
    }

    private function nextInvoiceNumber(): string
    {
        $prefix = \App\Models\BusinessSetting::where('key', 'invoice_prefix')->value('value') ?: 'INV';
        return $prefix . '-' . now()->format('Ymd') . '-' . str_pad((string) ((\App\Models\Invoice::max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT);
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
            'staff',
            'appointment.service',
            'appointment.location',
            'payments' => fn ($query) => $query->orderBy('payment_date')->orderBy('created_at'),
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
