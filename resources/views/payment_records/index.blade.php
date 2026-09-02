@extends('layouts.app')

@section('title', 'Payment Records')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">Payments</h1>
                <p class="text-muted mb-0">Record and track payments against invoices.</p>
            </div>
        </div>
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row g-3 mb-4">
            @foreach([
                ['Total received', $summary['total'], 'bx-dollar'],
                ['Cash total', $summary['cash'], 'bx-money'],
                ['Card total', $summary['card'], 'bx-credit-card'],
                ['E-Transfer total', $summary['e_transfer'], 'bx-transfer'],
            ] as [$label, $amount, $icon])
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm kpi-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="text-muted small">{{ $label }}</div>
                                    <div class="fs-4 fw-bold financial">${{ number_format($amount, 2) }}</div>
                                </div>
                                <div class="kpi-icon"><i class='bx {{ $icon }}'></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card shadow-sm border-0 rounded mb-4">
            <div class="card-body">
                <div id="invoice-summary-banner" class="alert alert-light border mb-3 {{ $selectedInvoice ? '' : 'd-none' }}">
                    <div class="row g-2 align-items-center text-dark">
                        <div class="col-sm-6 col-md-3">
                            <span class="text-muted small d-block">Invoice Number:</span>
                            <strong id="summary-inv-number">{{ $selectedInvoice?->invoice_number ?: '-' }}</strong>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <span class="text-muted small d-block">Client:</span>
                            <strong id="summary-inv-client">{{ optional($selectedInvoice?->client)->name ?: '-' }}</strong>
                        </div>
                        <div class="col-sm-4 col-md-2">
                            <span class="text-muted small d-block">Invoice Total:</span>
                            <strong id="summary-inv-total">${{ number_format((float) ($selectedInvoice?->total_amount ?? 0), 2) }}</strong>
                        </div>
                        <div class="col-sm-4 col-md-2">
                            <span class="text-muted small d-block">Already Paid:</span>
                            <strong id="summary-inv-paid" class="text-success">${{ number_format((float) ($selectedInvoice?->paid_amount ?? 0), 2) }}</strong>
                        </div>
                        <div class="col-sm-4 col-md-2">
                            <span class="text-muted small d-block">Balance Due:</span>
                            <strong id="summary-inv-balance" class="text-danger">${{ number_format(max((float) ($selectedInvoice?->total_amount ?? 0) - (float) ($selectedInvoice?->paid_amount ?? 0), 0), 2) }}</strong>
                        </div>
                    </div>
                </div>

                <form action="{{ route('payment-records.store') }}" method="POST" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Invoice <span class="required-mark">*</span></label>
                        <select name="invoice_id" id="payment-invoice" class="form-select" required>
                            <option value="">Select invoice</option>
                            @foreach($invoices as $invoice)
                                @php
                                    $invBal = max((float) $invoice->total_amount - (float) $invoice->paid_amount, 0);
                                @endphp
                                <option value="{{ $invoice->id }}"
                                    data-number="{{ $invoice->invoice_number }}"
                                    data-client="{{ optional($invoice->client)->name }}"
                                    data-total="${{ number_format((float) $invoice->total_amount, 2) }}"
                                    data-paid="${{ number_format((float) $invoice->paid_amount, 2) }}"
                                    data-balance="{{ number_format($invBal, 2, '.', '') }}"
                                    data-balance-formatted="${{ number_format($invBal, 2) }}"
                                    @selected(($selectedInvoice?->id ?? null) === $invoice->id)>
                                    {{ $invoice->invoice_number }} - {{ optional($invoice->client)->name }} - Balance ${{ number_format($invBal, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Amount <span class="required-mark">*</span></label>
                        <input type="number" step="0.01" min="0.01" max="{{ $selectedInvoice ? number_format(max((float) $selectedInvoice->total_amount - (float) $selectedInvoice->paid_amount, 0), 2, '.', '') : '' }}" name="amount" id="payment-amount" class="form-control" value="{{ $selectedInvoice ? number_format(max((float) $selectedInvoice->total_amount - (float) $selectedInvoice->paid_amount, 0), 2, '.', '') : old('amount') }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Method <span class="required-mark">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="e_transfer">E-Transfer</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date <span class="required-mark">*</span></label>
                        <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100">Add payment</button>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Transaction ID</label>
                        <input name="transaction_id" class="form-control" placeholder="Optional reference / transaction ID">
                    </div>
                </form>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const invoice = document.getElementById('payment-invoice');
                const amount = document.getElementById('payment-amount');
                const banner = document.getElementById('invoice-summary-banner');
                const numEl = document.getElementById('summary-inv-number');
                const clientEl = document.getElementById('summary-inv-client');
                const totalEl = document.getElementById('summary-inv-total');
                const paidEl = document.getElementById('summary-inv-paid');
                const balanceEl = document.getElementById('summary-inv-balance');

                invoice?.addEventListener('change', () => {
                    const opt = invoice.selectedOptions[0];
                    if (!opt || !opt.value) {
                        if (banner) banner.classList.add('d-none');
                        if (amount) { amount.value = ''; amount.removeAttribute('max'); }
                        return;
                    }

                    const balance = opt.dataset.balance;
                    if (balance && amount) {
                        amount.value = balance;
                        amount.setAttribute('max', balance);
                    }

                    if (numEl) numEl.textContent = opt.dataset.number || '-';
                    if (clientEl) clientEl.textContent = opt.dataset.client || '-';
                    if (totalEl) totalEl.textContent = opt.dataset.total || '$0.00';
                    if (paidEl) paidEl.textContent = opt.dataset.paid || '$0.00';
                    if (balanceEl) balanceEl.textContent = opt.dataset.balanceFormatted || '$0.00';
                    if (banner) banner.classList.remove('d-none');
                });
            });
        </script>

        <!-- Toolbar -->
        <x-list-toolbar :paginator="$paymentRecords" searchAction="{{ route('payment-records.index') }}" searchPlaceholder="Search payments">
            <x-slot name="filters">
                <x-list-toolbar-filters
                    :showClear="(request()->has('search') && request('search') !== '') || request()->filled('payment_method')"
                    :clearUrl="route('payment-records.index', ['per_page' => request('per_page', $paymentRecords->perPage())])" />
                <div class="dropdown">
                    <button class="btn btn-light border dropdown-toggle btn-sm text-muted" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        {{ request()->filled('payment_method') ? (request('payment_method') === 'e_transfer' ? 'E-Transfer' : ucfirst(request('payment_method'))) : 'Payment method' }}
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('payment-records.index', request()->except(['payment_method', 'page'])) }}">All</a></li>
                        <li><a class="dropdown-item" href="{{ route('payment-records.index', array_merge(request()->except(['payment_method', 'page']), ['payment_method' => 'cash'])) }}">Cash</a></li>
                        <li><a class="dropdown-item" href="{{ route('payment-records.index', array_merge(request()->except(['payment_method', 'page']), ['payment_method' => 'card'])) }}">Card</a></li>
                        <li><a class="dropdown-item" href="{{ route('payment-records.index', array_merge(request()->except(['payment_method', 'page']), ['payment_method' => 'e_transfer'])) }}">E-Transfer</a></li>
                    </ul>
                </div>
            </x-slot>
        </x-list-toolbar>

        <!-- Table -->
        <div class="bg-white rounded shadow-sm overflow-hidden mb-5">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 250px;">Date <i class='bx bx-down-arrow-alt'></i></th>
                            <th>Client name</th>
                            <th>Status</th>
                            <th>Method</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paymentRecords as $record)
                            <tr>
                                <td class="text-muted">{{ $record->created_at->format('M j, Y - h:i A') }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="client-avatar">
                                            <i class='bx bx-user'></i>
                                        </div>
                                            <span>{{ optional(optional($record->invoice)->client)->name ?: 'N/A' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge badge-paid">
                                        <i class='bx bx-check-circle'></i> Paid
                                    </span>
                                </td>
                                <td class="small">{{ $record->payment_method }}</td>
                                <td class="small fw-500 text-end">${{ number_format($record->amount, 2) }}</td>
                                <td class="text-end">
                                    @if($record->invoice)
                                        <a href="{{ route('invoices.show', $record->invoice_id) }}" class="btn btn-link text-muted p-0 me-2"><i class='bx bx-show'></i></a>
                                    @endif
                                    <form action="{{ route('payment-records.destroy', $record->id) }}" method="POST" class="d-inline"
                                        data-confirm="This payment record will be permanently removed if the server allows it."
                                        data-confirm-title="Delete payment?"
                                        data-confirm-record="{{ number_format((float) $record->amount, 2) }}"
                                        data-confirm-text="Delete"
                                        data-confirm-loading="Deleting...">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-link text-muted p-0"><i class='bx bx-trash'></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No payment records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            @include('partials.pagination', ['paginator' => $paymentRecords])
        </div>
    </div>
@endsection
