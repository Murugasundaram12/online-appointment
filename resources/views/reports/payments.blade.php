@extends('layouts.app')

@section('title', 'Payments Report')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">Payments Report</h1>
                <p class="text-muted mb-0">Payments collected, methods and transaction details.</p>
            </div>
            <a href="{{ route('reports.index') }}" class="btn btn-light border">All Reports</a>
        </div>

        @include('reports._date_filter', [
            'exportType' => 'payments',
            'extraFields' => [
                'method' => ['label' => 'Method', 'items' => $methods->mapWithKeys(fn ($m) => [$m => ucfirst($m)])->all()],
            ],
        ])

        <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Payments</div><div class="fs-4 fw-bold">{{ $summary['count'] }}</div></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Total Collected</div><div class="fs-4 fw-bold text-success">${{ number_format($summary['total'], 2) }}</div></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Top Method</div>
                <div class="fs-4 fw-bold text-capitalize">{{ $summary['methods']->first()?->payment_method ?? 'N/A' }}</div>
            </div></div></div>
        </div>

        @if($summary['methods']->isNotEmpty())
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="fs-6 fw-bold mb-3">Breakdown by Method</h2>
                    <div class="row g-3">
                        @foreach($summary['methods'] as $method)
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between align-items-center border rounded p-3">
                                    <div>
                                        <div class="fw-semibold text-capitalize">{{ $method->payment_method }}</div>
                                        <div class="text-muted small">{{ $method->count }} payment(s)</div>
                                    </div>
                                    <div class="fs-5 fw-bold">${{ number_format((float) $method->amount, 2) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light text-muted small">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th class="text-end">Amount</th>
                            <th>Method</th>
                            <th>Transaction ID</th>
                            <th>Invoice</th>
                            <th class="pe-4">Client</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td class="ps-4">{{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : '-' }}</td>
                            <td class="text-end fw-semibold text-success">${{ number_format((float) $payment->amount, 2) }}</td>
                            <td><span class="badge bg-light text-dark text-capitalize">{{ $payment->payment_method }}</span></td>
                            <td>{{ $payment->transaction_id ?: '-' }}</td>
                            <td>
                                @if($payment->invoice)
                                    <a href="{{ route('invoices.show', $payment->invoice->id) }}" class="text-decoration-none">{{ $payment->invoice->invoice_number }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="pe-4">{{ optional($payment->invoice?->client)->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No payments found for the selected period and filters.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.pagination', ['paginator' => $payments])
        </div>
    </div>
@endsection
