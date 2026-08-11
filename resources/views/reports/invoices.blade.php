@extends('layouts.app')

@section('title', 'Invoices Report')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">Invoices Report</h1>
                <p class="text-muted mb-0">Invoice status, balances and overdue aging.</p>
            </div>
            <a href="{{ route('reports.index') }}" class="btn btn-light border">All Reports</a>
        </div>

        @include('reports._date_filter', [
            'exportType' => 'invoices',
            'paginator' => $invoices,
            'extraFields' => [
                'status' => ['label' => 'Status', 'items' => collect($statuses)->mapWithKeys(fn ($s) => [$s => ucfirst(str_replace('_', ' ', $s))])->all()],
            ],
        ])

        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Invoices</div><div class="fs-4 fw-bold">{{ $summary['total'] }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Billed</div><div class="fs-4 fw-bold text-primary">${{ number_format($summary['billed'], 2) }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Collected</div><div class="fs-4 fw-bold text-success">${{ number_format($summary['collected'], 2) }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Outstanding</div><div class="fs-4 fw-bold {{ $summary['outstanding'] > 0 ? 'text-danger' : '' }}">${{ number_format($summary['outstanding'], 2) }}</div></div></div></div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Paid</div><div class="fs-4 fw-bold text-success">{{ $summary['paid'] }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Pending / Partial</div><div class="fs-4 fw-bold text-warning">{{ $summary['unpaid'] }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Overdue</div><div class="fs-4 fw-bold {{ $summary['overdue'] > 0 ? 'text-danger' : '' }}">{{ $summary['overdue'] }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Void</div><div class="fs-4 fw-bold text-secondary">{{ $summary['void'] }}</div></div></div></div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light text-muted small">
                        <tr>
                            <th class="ps-4">Invoice</th>
                            <th>Client</th>
                            <th>Issued</th>
                            <th>Due</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Balance</th>
                            <th class="pe-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td class="ps-4"><a href="{{ route('invoices.show', $invoice->id) }}" class="text-decoration-none fw-semibold">{{ $invoice->invoice_number }}</a></td>
                            <td>{{ optional($invoice->client)->name ?? '-' }}</td>
                            <td>{{ $invoice->issued_date ? $invoice->issued_date->format('M d, Y') : '-' }}</td>
                            <td>{{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : '-' }}</td>
                            <td class="text-end">${{ number_format((float) $invoice->total_amount, 2) }}</td>
                            <td class="text-end {{ (float) $invoice->total_amount > (float) $invoice->paid_amount ? 'text-danger fw-semibold' : '' }}">${{ number_format(max(0, (float) $invoice->total_amount - (float) $invoice->paid_amount), 2) }}</td>
                            <td class="pe-4">
                                <span class="badge {{ $invoice->status === 'paid' ? 'bg-success-subtle text-success-emphasis' : ($invoice->status === 'partially_paid' ? 'bg-info-subtle text-info-emphasis' : ($invoice->status === 'void' ? 'bg-secondary-subtle text-secondary-emphasis' : 'bg-danger-subtle text-danger-emphasis')) }}">
                                    {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">No invoices found for the selected period and filters.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.pagination', ['paginator' => $invoices])
        </div>
    </div>
@endsection
