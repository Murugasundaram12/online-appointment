<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentRecordController extends Controller
{
    public function index()
    {
        $paymentRecords = PaymentRecord::with('invoice.client')->latest()->paginate(10);
        $invoices = Invoice::with('client')->where('status', '!=', 'void')->orderByDesc('issued_date')->get();
        $summary = [
            'total' => PaymentRecord::sum('amount'),
            'cash' => PaymentRecord::where('payment_method', 'cash')->sum('amount'),
            'card' => PaymentRecord::where('payment_method', 'card')->sum('amount'),
            'transfer' => PaymentRecord::where('payment_method', 'transfer')->sum('amount'),
        ];
        return view('payment_records.index', compact('paymentRecords', 'invoices', 'summary'));
    }

    public function create()
    {
        return view('payment_records.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,card,transfer',
            'payment_date' => 'required|date',
            'transaction_id' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $invoice = Invoice::lockForUpdate()->findOrFail($validated['invoice_id']);
                if ($invoice->status === 'void') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'invoice_id' => 'Payments cannot be added to a void invoice.',
                    ]);
                }

                $existingPaid = (float) $invoice->payments()->sum('amount');
                if (($existingPaid + (float) $validated['amount']) > (float) $invoice->total_amount) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'amount' => 'Payment exceeds the invoice balance.',
                    ]);
                }

                PaymentRecord::create($validated);
                $this->syncInvoicePaymentStatus($invoice);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Payment could not be saved: ' . $e->getMessage());
        }

        return redirect()->route('payment-records.index')->with('success', 'Payment Record created successfully.');
    }

    public function show(string $id)
    {
        //
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
        try {
            DB::transaction(function () use ($id) {
                $payment = PaymentRecord::with('invoice')->findOrFail($id);
                $invoice = Invoice::lockForUpdate()->findOrFail($payment->invoice_id);
                $payment->delete();
                $this->syncInvoicePaymentStatus($invoice);
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Payment could not be deleted: ' . $e->getMessage());
        }

        return redirect()->route('payment-records.index')->with('success', 'Payment Record deleted successfully.');
    }

    private function syncInvoicePaymentStatus(Invoice $invoice): void
    {
        if ($invoice->status === 'void') {
            return;
        }

        $paid = (float) $invoice->payments()->sum('amount');
        $total = (float) $invoice->total_amount;

        $invoice->paid_amount = min($paid, $total);
        $invoice->status = $paid >= $total ? 'paid' : ($paid > 0 ? 'partially_paid' : 'outstanding');
        $invoice->save();
    }
}
