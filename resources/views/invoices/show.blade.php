@extends('layouts.app')

@section('title', 'Invoice ' . $invoice->invoice_number)

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <h2 class="fs-4 m-0 fw-bold">Invoice Details</h2>
            <div class="d-flex gap-2">
                <a href="{{ route('invoices.index') }}" class="btn btn-white border btn-sm text-muted">Back to List</a>
                <button type="button" onclick="window.print()" class="btn btn-white border btn-sm">Print</button>
                @if(!in_array($invoice->status, ['paid', 'void']))
                    <a href="{{ route('payment-records.index') }}" class="btn btn-white border btn-sm">Add payment</a>
                @endif
                <a href="{{ route('invoices.download', $invoice->id) }}" class="btn btn-primary btn-sm px-4">Download invoice</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 pt-4">
        <div class="card shadow-sm border-0 rounded">
            <div class="card-body p-5">
                <div class="d-flex justify-content-between mb-5">
                    <div>
                        <h3 class="fw-bold mb-1">{{ config('app.name') }}</h3>
                        <p class="text-muted mb-0">123 Business Street</p>
                        <p class="text-muted mb-0">City, Country</p>
                    </div>
                    <div class="text-end">
                        <h4 class="fw-bold text-primary mb-1">INVOICE</h4>
                        <p class="text-muted mb-0">#{{ $invoice->invoice_number }}</p>
                        <p class="text-muted mb-0">Date: {{ $invoice->issued_date->format('M j, Y') }}</p>
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-6">
                        <h6 class="fw-bold text-uppercase text-muted small mb-2">Bill To</h6>
                        <h5 class="fw-bold mb-1">{{ $invoice->client->name }}</h5>
                        <p class="text-muted mb-0">{{ $invoice->client->email }}</p>
                        <p class="text-muted mb-0">{{ $invoice->client->phone }}</p>
                    </div>
                    <div class="col-6 text-end">
                        <!-- Status Badge -->
                        @if($invoice->status == 'outstanding')
                            <span class="badge bg-danger fs-6 px-3 py-2">Outstanding</span>
                        @elseif($invoice->status == 'paid')
                            <span class="badge bg-success fs-6 px-3 py-2">Paid</span>
                        @else
                            <span class="badge bg-secondary fs-6 px-3 py-2">{{ ucfirst($invoice->status) }}</span>
                        @endif
                    </div>
                </div>

                <div class="table-responsive mb-5">
                    <table class="table table-borderless">
                        <thead class="border-bottom">
                            <tr class="fw-bold text-uppercase text-muted small">
                                <th class="py-3">Description</th>
                                <th class="text-end py-3">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Assuming invoice items relation or singular appointment link -->
                            <tr>
                                <td class="py-3">
                                    <p class="fw-bold mb-1">
                                        {{ optional(optional($invoice->appointment)->service)->name ?: 'Service' }}</p>
                                    <p class="text-muted small mb-0">
                                        {{ $invoice->appointment ? $invoice->appointment->start_time->format('M j, Y g:i A') : '' }}
                                    </p>
                                </td>
                                <td class="text-end py-3 fw-bold">${{ number_format($invoice->total_amount, 2) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="border-top">
                            <tr>
                                <td class="text-end py-2 pt-3">Subtotal</td>
                                <td class="text-end py-2 pt-3">${{ number_format($invoice->total_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-end py-2">Tax (0%)</td> <!-- Placeholder tax -->
                                <td class="text-end py-2">$0.00</td>
                            </tr>
                            <tr>
                                <td class="text-end py-3 fw-bold fs-5">Total</td>
                                <td class="text-end py-3 fw-bold fs-5 text-primary">
                                    ${{ number_format($invoice->total_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-end py-2 text-muted">Amount Paid</td>
                                <td class="text-end py-2 text-muted">${{ number_format($invoice->paid_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-end py-2 fw-bold text-danger">Balance Due</td>
                                <td class="text-end py-2 fw-bold text-danger">
                                    ${{ number_format($invoice->total_amount - $invoice->paid_amount, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="text-center text-muted small">
                    <h6 class="fw-bold text-start text-uppercase text-muted small mb-2">Payment History</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm">
                            <thead><tr><th>Date</th><th>Method</th><th>Transaction</th><th class="text-end">Amount</th></tr></thead>
                            <tbody>
                                @forelse($invoice->payments as $payment)
                                    <tr>
                                        <td>{{ optional($payment->payment_date)->format('M j, Y') }}</td>
                                        <td>{{ ucfirst($payment->payment_method) }}</td>
                                        <td>{{ $payment->transaction_id ?: '-' }}</td>
                                        <td class="text-end">${{ number_format($payment->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">No payments recorded.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <p class="mb-0">Thank you for your business!</p>
                </div>
            </div>
        </div>
    </div>
@endsection
