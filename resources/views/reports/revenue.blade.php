@extends('layouts.app')

@section('title', 'Revenue Report')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">Revenue Report</h1>
                <p class="text-muted mb-0">Billed vs collected revenue grouped by day.</p>
            </div>
            <a href="{{ route('reports.index') }}" class="btn btn-light border">All Reports</a>
        </div>

        @include('reports._date_filter', ['exportType' => 'revenue'])

        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Billed</div><div class="fs-4 fw-bold text-primary">${{ number_format($summary['billed'], 2) }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Collected</div><div class="fs-4 fw-bold text-success">${{ number_format($summary['collected'], 2) }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Outstanding</div><div class="fs-4 fw-bold {{ $summary['outstanding'] > 0 ? 'text-danger' : '' }}">${{ number_format($summary['outstanding'], 2) }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Active Days</div><div class="fs-4 fw-bold">{{ $summary['days'] }}</div></div></div></div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light text-muted small">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th class="text-end">Invoices</th>
                            <th class="text-end">Billed</th>
                            <th class="text-end">Collected</th>
                            <th class="text-end pe-4">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td class="ps-4">{{ \Carbon\Carbon::parse($row->issued_date)->format('M d, Y') }}</td>
                            <td class="text-end">{{ $row->invoice_count }}</td>
                            <td class="text-end">${{ number_format((float) $row->billed, 2) }}</td>
                            <td class="text-end text-success">${{ number_format((float) $row->collected, 2) }}</td>
                            <td class="text-end pe-4 {{ (float) $row->balance > 0 ? 'text-danger fw-semibold' : '' }}">${{ number_format((float) $row->balance, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">No revenue recorded for the selected period.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.pagination', ['paginator' => $rows])
        </div>
    </div>
@endsection
