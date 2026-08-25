<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentRecordController extends Controller
{
    public function index(Request $request)
    {
        $staff = Auth::guard('staff')->user();

        $paymentRecords = PaymentRecord::with('invoice.client', 'invoice.staff', 'invoice.appointment')
            ->when($staff && !in_array($staff->access_level, ['admin', 'business_owner'], true) && !is_null($staff->location_id), function ($query) use ($staff) {
                $query->whereHas('invoice', function ($iq) use ($staff) {
                    $iq->where('staff_id', $staff->id)
                        ->orWhereHas('staff', fn ($sq) => $sq->where('location_id', $staff->location_id))
                        ->orWhereHas('appointment', fn ($aq) => $aq->where('location_id', $staff->location_id));
                });
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->whereHas('invoice.client', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    })->orWhere('payment_method', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('payment_method'), function ($query) use ($request) {
                $query->where('payment_method', $request->input('payment_method'));
            })
            ->latest()
            ->paginate($this->perPage($request));

        $invoices = Invoice::with('client')
            ->whereNotIn('status', ['void', 'paid'])
            ->orderByDesc('issued_date')
            ->get();
        $selectedInvoiceId = $request->integer('invoice_id');
        $selectedInvoice = $invoices->firstWhere('id', $selectedInvoiceId);

        $summary = [
            'total' => PaymentRecord::sum('amount'),
            'cash' => PaymentRecord::where('payment_method', 'cash')->sum('amount'),
            'card' => PaymentRecord::where('payment_method', 'card')->sum('amount'),
            'e_transfer' => PaymentRecord::whereIn('payment_method', ['e_transfer', 'transfer'])->sum('amount'),
        ];

        return view('payment_records.index', compact('paymentRecords', 'invoices', 'summary', 'selectedInvoice'));
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
            'payment_method' => 'required|in:cash,card,e_transfer',
            'payment_date' => 'required|date',
            'transaction_id' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $invoice = Invoice::with(['staff', 'appointment'])->lockForUpdate()->findOrFail($validated['invoice_id']);
                $this->authorizeInvoiceAccess($invoice);

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
        } catch (HttpException $e) {
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
                $payment = PaymentRecord::with('invoice.staff', 'invoice.appointment')->findOrFail($id);
                $this->authorizeInvoiceAccess($payment->invoice);

                $invoice = Invoice::lockForUpdate()->findOrFail($payment->invoice_id);
                $payment->delete();
                $this->syncInvoicePaymentStatus($invoice);
            });
        } catch (HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->with('error', 'Payment could not be deleted: ' . $e->getMessage());
        }

        return redirect()->route('payment-records.index')->with('success', 'Payment Record deleted successfully.');
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
