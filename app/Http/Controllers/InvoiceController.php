<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = \App\Models\Invoice::with(['client', 'staff'])->latest()->paginate(10);
        return view('invoices.index', compact('invoices'));
    }

    public function create()
    {
        return view('invoices.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'appointment_id' => 'nullable|exists:appointments,id',
            'client_id' => 'required|exists:clients,id',
            'staff_id' => 'required|exists:staff,id',
            'invoice_number' => 'nullable|string|max:255|unique:invoices,invoice_number',
            'total_amount' => 'required|numeric|min:0.01',
            'paid_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:outstanding,paid,partially_paid,void',
            'issued_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issued_date',
        ]);

        $validated['invoice_number'] = $validated['invoice_number'] ?? $this->nextInvoiceNumber();
        $validated['paid_amount'] = min((float) ($validated['paid_amount'] ?? 0), (float) $validated['total_amount']);
        $validated['status'] = $validated['status'] ?? $this->statusForAmounts($validated['paid_amount'], $validated['total_amount']);

        \App\Models\Invoice::create($validated);

        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully.');
    }

    public function show(string $id)
    {
        return view('invoices.show', ['invoice' => \App\Models\Invoice::with(['appointment.service', 'client', 'staff', 'payments'])->findOrFail($id)]);
    }

    public function download(string $id)
    {
        $invoice = \App\Models\Invoice::with(['appointment.service', 'client', 'staff', 'payments'])->findOrFail($id);
        return response()
            ->view('invoices.pdf', compact('invoice'))
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'attachment; filename="invoice-' . $invoice->invoice_number . '.html"');
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
            'invoice_number' => 'required|string|max:255|unique:invoices,invoice_number,' . $invoice->id,
            'total_amount' => 'required|numeric|min:0.01',
            'paid_amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:outstanding,paid,partially_paid,void',
            'issued_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issued_date',
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
}
