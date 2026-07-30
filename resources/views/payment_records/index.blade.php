@extends('layouts.app')

@section('title', 'Payment Records')

@push('styles')
    <style>
        .badge-paid {
            background-color: #e8fff3;
            color: #50cd89;
            border: 1px solid #d1f7e4;
        }

        .badge-partial {
            background-color: #fff8dd;
            color: #ff9900;
            border: 1px solid #fff4cc;
        }

        .badge-failed {
            background-color: #fff5f8;
            color: #f1416c;
            border: 1px solid #ffdbdc;
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.6rem;
            border-radius: 4px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .search-input {
            background-color: #f5f8fa;
            border: none;
            border-radius: 8px;
            padding-left: 40px;
        }

        .search-container {
            position: relative;
        }

        .search-container i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a1a5b7;
        }

        .filter-select {
            background-color: #f5f8fa;
            border: none;
            font-size: 0.85rem;
            font-weight: 500;
            color: #3f4254;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }

        .table thead th {
            background-color: #fcfdfe;
            color: #7e8299;
            font-weight: 600;
            font-size: 0.85rem;
            border-top: none;
            padding: 1.25rem 1rem;
        }

        .table tbody td {
            padding: 1.25rem 1rem;
            vertical-align: middle;
            font-size: 0.9rem;
            color: #3f4254;
            border-bottom: 1px solid #f8f9fa;
        }

        .client-avatar {
            width: 30px;
            height: 30px;
            background-color: #f5f8fa;
            color: #7e8299;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 0.8rem;
            margin-right: 10px;
        }

        /* Standardized Header/Sidebar Styles */
        .dot-active {
            width: 8px;
            height: 8px;
            background-color: white;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
        }

        .nav-icon-box {
            width: 40px;
            height: 40px;
            background-color: #f3f6f9;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7e8299;
            font-size: 1.25rem;
            position: relative;
        }

        .plus-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #3699ff;
            color: white;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
@endpush

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-transparent py-4 px-4">
        <div class="d-flex align-items-center justify-content-between w-100">
            <div class="d-flex align-items-center">
                <div class="nav-icon-box me-3">
                    <i class='bx bx-cog'></i>
                    <span class="plus-badge">+</span>
                </div>
                <div class="nav-icon-box">
                    <i class='bx bx-camera-plus'></i>
                </div>
            </div>
            <h2 class="fs-4 m-0 fw-bold">Payment records</h2>
        </div>
    </nav>

    <div class="container-fluid px-4">
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
                ['Transfer total', $summary['transfer'], 'bx-transfer'],
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
                <form action="{{ route('payment-records.store') }}" method="POST" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Invoice</label>
                        <select name="invoice_id" class="form-select" required>
                            <option value="">Select invoice</option>
                            @foreach($invoices as $invoice)
                                <option value="{{ $invoice->id }}">{{ $invoice->invoice_number }} - {{ optional($invoice->client)->name }} - Balance ${{ number_format(max($invoice->total_amount - $invoice->paid_amount, 0), 2) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Method</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="transfer">Transfer</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date</label>
                        <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100">Add payment</button>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Transaction ID</label>
                        <input name="transaction_id" class="form-control">
                    </div>
                </form>
            </div>
        </div>

        <!-- Filters -->
        <div class="d-flex flex-wrap gap-3 align-items-center mb-4">
            <div class="search-container flex-grow-1" style="max-width: 400px;">
                <i class='bx bx-search'></i>
                <input type="text" class="form-control search-input" placeholder="Search">
            </div>
            <div class="dropdown">
                <button class="btn filter-select dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    Date
                </button>
            </div>
            <div class="dropdown">
                <button class="btn filter-select dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    Status
                </button>
            </div>
            <div class="dropdown">
                <button class="btn filter-select dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    Payment method
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded shadow-sm overflow-hidden mb-5">
            <div class="d-flex justify-content-end p-3 bg-white border-bottom">
                <i class='bx bx-hide text-muted fs-5'></i>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 250px;">Date <i class='bx bx-down-arrow-alt'></i></th>
                            <th>Client name</th>
                            <th>Status</th>
                            <th>Method</th>
                            <th>Amount</th>
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
                                <td class="small fw-500">${{ number_format($record->amount, 2) }}</td>
                                <td class="text-end">
                                    @if($record->invoice)
                                        <a href="{{ route('invoices.show', $record->invoice_id) }}" class="btn btn-link text-muted p-0 me-2"><i class='bx bx-show'></i></a>
                                    @endif
                                    <form action="{{ route('payment-records.destroy', $record->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this payment?');">
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
            <div class="card-footer bg-white border-0 py-3 px-4 d-flex justify-content-end">
                {{ $paymentRecords->links() }}
            </div>
        </div>
    </div>
@endsection
