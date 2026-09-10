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
                $method = $request->input('payment_method');
                if ($method === 'both') {
                    $query->whereIn('payment_method', ['both', 'cash_card', 'card_e_transfer', 'cash_e_transfer', 'cash_insurance', 'card_insurance', 'e_transfer_insurance']);
                } else {
                    $query->where('payment_method', $method);
                }
            })
            ->latest()
            ->paginate($this->perPage($request));

        $invoices = Invoice::with(['client.insuranceInformations.insuranceCompany', 'payments'])
            ->whereNotIn('status', ['void', 'paid'])
            ->orderByDesc('issued_date')
            ->get()
            ->filter(function ($inv) {
                if ($inv->status === 'void' || $inv->status === 'paid') {
                    return false;
                }
                $existingPaid = (float) $inv->payments->sum('amount');
                $totalAmount = (float) $inv->total_amount;
                return ($totalAmount - $existingPaid) > 0.0001;
            })
            ->values();
        $selectedInvoiceId = $request->integer('invoice_id');
        $selectedInvoice = $invoices->firstWhere('id', $selectedInvoiceId);

        $insuranceCompanies = \App\Models\InsuranceCompany::orderBy('name')->get();

        $summary = [
            'total' => PaymentRecord::sum('amount'),
            'cash' => PaymentRecord::where('payment_method', 'cash')->sum('amount')
                + PaymentRecord::where('payment_method', '!=', 'cash')->whereNotNull('cash_amount')->sum('cash_amount')
                + PaymentRecord::whereNull('cash_amount')->where('primary_method', 'cash')->sum('primary_amount')
                + PaymentRecord::whereNull('cash_amount')->where('secondary_method', 'cash')->sum('secondary_amount'),
            'card' => PaymentRecord::where('payment_method', 'card')->sum('amount')
                + PaymentRecord::where('payment_method', '!=', 'card')->whereNotNull('card_amount')->sum('card_amount')
                + PaymentRecord::whereNull('card_amount')->where('primary_method', 'card')->sum('primary_amount')
                + PaymentRecord::whereNull('card_amount')->where('secondary_method', 'card')->sum('secondary_amount'),
            'e_transfer' => PaymentRecord::whereIn('payment_method', ['e_transfer', 'transfer'])->sum('amount')
                + PaymentRecord::whereNotIn('payment_method', ['e_transfer', 'transfer'])->whereNotNull('e_transfer_amount')->sum('e_transfer_amount')
                + PaymentRecord::whereNull('e_transfer_amount')->where('primary_method', 'e_transfer')->sum('primary_amount')
                + PaymentRecord::whereNull('e_transfer_amount')->where('secondary_method', 'e_transfer')->sum('secondary_amount'),
        ];

        return view('payment_records.index', compact('paymentRecords', 'invoices', 'summary', 'selectedInvoice', 'insuranceCompanies'));
    }

    public function create()
    {
        return view('payment_records.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|gt:0',
            'payment_method' => 'required|in:cash,card,e_transfer,insurance,cash_card,card_e_transfer,cash_e_transfer,cash_insurance,card_insurance,e_transfer_insurance,both',
            'split_methods' => 'nullable|array',
            'split_methods.*' => 'in:cash,card,e_transfer,insurance',
            'cash_amount' => 'nullable|numeric|min:0',
            'card_amount' => 'nullable|numeric|min:0',
            'e_transfer_amount' => 'nullable|numeric|min:0',
            'insurance_amount' => 'nullable|numeric|min:0',
            'payment_date' => 'required|date',
            'transaction_id' => 'nullable|string|max:255',
            'card_brand' => 'nullable|in:Visa,Mastercard,American Express,Discover,Other',
            'cardholder_name' => 'nullable|string|max:255',
            'card_last_four' => 'nullable|regex:/^\d{4}$/',
            'transaction_reference' => 'nullable|string|max:255',
            'e_transfer_reference' => 'nullable|string|max:255',
            'sender_name' => 'nullable|string|max:255',
            'transfer_date' => 'nullable|date',
            'insurance_company_id' => 'nullable|exists:insurance_companies,id',
            'insurance_information_id' => 'nullable|exists:insurance_information,id',
            'policy_id' => 'nullable|string|max:255',
            'member_id_or_contract_number' => 'nullable|string|max:255',
            'claim_reference' => 'nullable|string|max:255',
            'amount_submitted' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ], [
            'card_last_four.regex' => 'Card last 4 digits must be exactly 4 numeric digits.',
            'amount.gt' => 'Paid amount must be greater than 0.',
        ]);

        try {
            DB::transaction(function () use ($validated, $request) {
                $invoice = Invoice::with(['staff', 'appointment'])->lockForUpdate()->findOrFail($validated['invoice_id']);
                $this->authorizeInvoiceAccess($invoice);

                if ($invoice->status === 'void') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'invoice_id' => 'Payments cannot be added to a void invoice.',
                    ]);
                }

                $existingPaid = (float) $invoice->payments()->sum('amount');
                $totalAmount = (float) $invoice->total_amount;
                $remainingBalance = max(0, $totalAmount - $existingPaid);

                if ($invoice->status === 'paid' || $remainingBalance <= 0.0001) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'invoice_id' => 'This invoice is already fully paid.',
                    ]);
                }

                if ((float) $validated['amount'] > ($remainingBalance + 0.0001)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'amount' => 'Paid amount cannot exceed the remaining balance.',
                    ]);
                }

                $method = $validated['payment_method'];

                $isSplit = in_array($method, [
                    'both',
                    'cash_card',
                    'card_e_transfer',
                    'cash_e_transfer',
                    'cash_insurance',
                    'card_insurance',
                    'e_transfer_insurance',
                ], true);

                if ($isSplit) {
                    // Collect split methods
                    $selectedMethods = $request->input('split_methods');
                    if (!is_array($selectedMethods)) {
                        $selectedMethods = [];
                        if ($request->filled('cash_amount') && (float) $request->input('cash_amount') > 0) $selectedMethods[] = 'cash';
                        if ($request->filled('card_amount') && (float) $request->input('card_amount') > 0) $selectedMethods[] = 'card';
                        if ($request->filled('e_transfer_amount') && (float) $request->input('e_transfer_amount') > 0) $selectedMethods[] = 'e_transfer';
                        if ($request->filled('insurance_amount') && (float) $request->input('insurance_amount') > 0) $selectedMethods[] = 'insurance';

                        if (empty($selectedMethods) && in_array($method, ['cash_card', 'card_e_transfer', 'cash_e_transfer', 'cash_insurance', 'card_insurance', 'e_transfer_insurance'], true)) {
                            if (str_contains($method, 'cash')) $selectedMethods[] = 'cash';
                            if (str_contains($method, 'card')) $selectedMethods[] = 'card';
                            if (str_contains($method, 'e_transfer')) $selectedMethods[] = 'e_transfer';
                            if (str_contains($method, 'insurance')) $selectedMethods[] = 'insurance';
                        }
                    }

                    if (count($selectedMethods) < 2) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'payment_method' => 'Split payment requires selecting at least two payment methods.',
                        ]);
                    }

                    if (count($selectedMethods) > 4) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'payment_method' => 'Split payment allows a maximum of 4 payment methods.',
                        ]);
                    }

                    $methodAmounts = [
                        'cash' => in_array('cash', $selectedMethods, true) ? (float) $request->input('cash_amount', 0) : null,
                        'card' => in_array('card', $selectedMethods, true) ? (float) $request->input('card_amount', 0) : null,
                        'e_transfer' => in_array('e_transfer', $selectedMethods, true) ? (float) $request->input('e_transfer_amount', 0) : null,
                        'insurance' => in_array('insurance', $selectedMethods, true) ? (float) $request->input('insurance_amount', 0) : null,
                    ];

                    // Check for negative amounts
                    foreach ($selectedMethods as $m) {
                        if (($methodAmounts[$m] ?? 0) < 0) {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'amount' => 'Payment method amounts cannot be negative.',
                            ]);
                        }
                    }

                    $splitSum = 0.0;
                    foreach ($selectedMethods as $m) {
                        $splitSum += ($methodAmounts[$m] ?? 0);
                    }

                    if ($splitSum <= 0) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'amount' => 'At least one selected payment method amount must be greater than 0.00.',
                        ]);
                    }

                    if (abs($splitSum - (float) $validated['amount']) > 0.0001) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'amount' => 'Split payment amounts must equal the paid amount.',
                        ]);
                    }

                    $validated['cash_amount'] = $methodAmounts['cash'];
                    $validated['card_amount'] = $methodAmounts['card'];
                    $validated['e_transfer_amount'] = $methodAmounts['e_transfer'];
                    $validated['insurance_amount'] = $methodAmounts['insurance'];

                    // Populate primary/secondary for backward compatibility
                    $validated['primary_method'] = $selectedMethods[0] ?? null;
                    $validated['secondary_method'] = $selectedMethods[1] ?? null;
                    $validated['primary_amount'] = isset($selectedMethods[0]) ? $methodAmounts[$selectedMethods[0]] : null;
                    $validated['secondary_amount'] = isset($selectedMethods[1]) ? $methodAmounts[$selectedMethods[1]] : null;

                    if (count($selectedMethods) === 2) {
                        $p1 = $selectedMethods[0];
                        $p2 = $selectedMethods[1];
                        if (($p1 === 'cash' && $p2 === 'card') || ($p1 === 'card' && $p2 === 'cash')) {
                            $validated['payment_method'] = 'cash_card';
                        } elseif (($p1 === 'card' && $p2 === 'e_transfer') || ($p1 === 'e_transfer' && $p2 === 'card')) {
                            $validated['payment_method'] = 'card_e_transfer';
                        } elseif (($p1 === 'cash' && $p2 === 'e_transfer') || ($p1 === 'e_transfer' && $p2 === 'cash')) {
                            $validated['payment_method'] = 'cash_e_transfer';
                        } elseif (($p1 === 'cash' && $p2 === 'insurance') || ($p1 === 'insurance' && $p2 === 'cash')) {
                            $validated['payment_method'] = 'cash_insurance';
                        } elseif (($p1 === 'card' && $p2 === 'insurance') || ($p1 === 'insurance' && $p2 === 'card')) {
                            $validated['payment_method'] = 'card_insurance';
                        } elseif (($p1 === 'e_transfer' && $p2 === 'insurance') || ($p1 === 'insurance' && $p2 === 'e_transfer')) {
                            $validated['payment_method'] = 'e_transfer_insurance';
                        } else {
                            $validated['payment_method'] = 'both';
                        }
                    } else {
                        $validated['payment_method'] = 'both';
                    }
                } else {
                    $validated['primary_method'] = null;
                    $validated['secondary_method'] = null;
                    $validated['primary_amount'] = null;
                    $validated['secondary_amount'] = null;
                    $validated['cash_amount'] = ($method === 'cash') ? (float) $validated['amount'] : null;
                    $validated['card_amount'] = ($method === 'card') ? (float) $validated['amount'] : null;
                    $validated['e_transfer_amount'] = in_array($method, ['e_transfer', 'transfer'], true) ? (float) $validated['amount'] : null;
                    $validated['insurance_amount'] = ($method === 'insurance') ? (float) $validated['amount'] : null;
                }

                $hasCard = $isSplit ? in_array('card', $selectedMethods, true) : ($method === 'card');
                $cardAmount = (float) ($validated['card_amount'] ?? 0);

                // Card Validation and Cleanup
                if ($hasCard && $cardAmount > 0) {
                    if (empty($validated['card_brand'])) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'card_brand' => 'The card brand field is required when paying with card.',
                        ]);
                    }
                    if (!empty($validated['card_last_four']) && !preg_match('/^\d{4}$/', $validated['card_last_four'])) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'card_last_four' => 'Card last 4 digits must be exactly 4 numeric digits.',
                        ]);
                    }
                } else {
                    $validated['card_brand'] = null;
                    $validated['cardholder_name'] = null;
                    $validated['card_last_four'] = null;
                    $validated['transaction_reference'] = null;
                    if (!$hasCard) {
                        $validated['card_amount'] = null;
                    }
                }

                // Insurance Validation and Cleanup
                $hasInsurance = $isSplit ? in_array('insurance', $selectedMethods, true) : ($method === 'insurance');
                if ($hasInsurance) {
                    $insErrors = [];
                    if (empty($validated['insurance_company_id'])) {
                        $insErrors['insurance_company_id'] = 'Please select an insurance company when paying with insurance.';
                    }
                    if (empty($validated['policy_id'])) {
                        $insErrors['policy_id'] = 'The policy ID is required when paying with insurance.';
                    }
                    if (empty($validated['member_id_or_contract_number'])) {
                        $insErrors['member_id_or_contract_number'] = 'The member ID or contract number is required when paying with insurance.';
                    }
                    if (!empty($insErrors)) {
                        throw \Illuminate\Validation\ValidationException::withMessages($insErrors);
                    }
                } else {
                    $validated['insurance_company_id'] = null;
                    $validated['insurance_information_id'] = null;
                    $validated['policy_id'] = null;
                    $validated['member_id_or_contract_number'] = null;
                    $validated['claim_reference'] = null;
                    $validated['amount_submitted'] = null;
                    $validated['insurance_amount'] = null;
                }

                // E-Transfer Cleanup
                $hasEtransfer = $isSplit ? in_array('e_transfer', $selectedMethods, true) : in_array($method, ['e_transfer', 'transfer'], true);
                if (!$hasEtransfer) {
                    $validated['e_transfer_reference'] = null;
                    $validated['sender_name'] = null;
                    $validated['transfer_date'] = null;
                    $validated['e_transfer_amount'] = null;
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

        return redirect()
            ->route('invoices.show', $validated['invoice_id'])
            ->with('success', 'Payment recorded successfully. Invoice updated.');
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
