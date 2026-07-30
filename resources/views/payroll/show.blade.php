@extends('layouts.app')

@section('title', 'Payroll Record')

@push('styles')
    <style>
        .payroll-doc { background: #fff; border: 1px solid var(--border); border-radius: 16px; box-shadow: var(--shadow-sm); }
        .payroll-badge { border-radius: 999px; padding: .35rem .75rem; font-weight: 800; font-size: .78rem; }
        .payroll-badge.pending { background: var(--warning-soft); color: var(--warning); }
        .payroll-badge.completed, .payroll-badge.paid { background: var(--success-soft); color: var(--success); }
        .payroll-badge.cancelled { background: var(--danger-soft); color: var(--danger); }
        @media print {
            .no-print, .sidebar, .topbar, nav { display: none !important; }
            body { background: #fff !important; }
            .payroll-doc { border: 0; box-shadow: none; }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 no-print">
            <div>
                <h1 class="fs-3 fw-bold mb-1">Payroll Details</h1>
                <p class="text-muted mb-0">{{ $payroll->payroll_number }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" onclick="window.print()" class="btn btn-light border"><i class='bx bx-printer'></i> Print</button>
                <a href="{{ route('payroll.download', $payroll->id) }}" class="btn btn-light border"><i class='bx bx-file'></i> PDF</a>
                <a href="{{ route('payroll.edit', $payroll->id) }}" class="btn btn-primary"><i class='bx bx-edit'></i> Edit</a>
                <a href="{{ route('payroll.index') }}" class="btn btn-light border">Back</a>
            </div>
        </div>

        <div class="payroll-doc p-4 p-lg-5">
            <div class="d-flex justify-content-between gap-4 border-bottom pb-4 mb-4">
                <div>
                    <h2 class="fw-bold mb-1">{{ $settings['business_name'] ?? config('app.name') }}</h2>
                    <div class="text-muted">{{ $settings['business_email'] ?? '' }}</div>
                    <div class="text-muted">{{ $settings['business_phone'] ?? '' }}</div>
                </div>
                <div class="text-end">
                    <div class="text-muted small text-uppercase fw-bold">Payroll</div>
                    <div class="fs-4 fw-bold">{{ $payroll->payroll_number }}</div>
                    <span class="payroll-badge {{ $payroll->status }}">{{ ucfirst($payroll->display_status) }}</span>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <h3 class="fs-6 fw-bold">Employee</h3>
                    <div>{{ optional($payroll->staff)->name ?? 'Not available' }}</div>
                    <div class="text-muted">{{ optional($payroll->staff)->email }}</div>
                    <div class="text-muted">{{ ucfirst(str_replace('_', ' ', optional($payroll->staff)->access_level ?? 'staff')) }}</div>
                </div>
                <div class="col-md-6">
                    <h3 class="fs-6 fw-bold">Payment Details</h3>
                    <div>Period: {{ $payroll->period_start->format('M d, Y') }} - {{ $payroll->period_end->format('M d, Y') }}</div>
                    <div>Payment date: {{ $payroll->payment_date ? $payroll->payment_date->format('M d, Y') : 'Not paid yet' }}</div>
                    <div>Method: {{ ucfirst(str_replace('_', ' ', $payroll->payment_type ?? 'transfer')) }}</div>
                </div>
            </div>

            <div class="table-responsive mb-4">
                <table class="table">
                    <thead>
                    <tr><th>Description</th><th class="text-end">Amount</th></tr>
                    </thead>
                    <tbody>
                    <tr><td>Basic Salary</td><td class="text-end">${{ number_format($payroll->salary_amount, 2) }}</td></tr>
                    <tr><td>Commission</td><td class="text-end">${{ number_format($payroll->commission_amount ?? 0, 2) }}</td></tr>
                    <tr><td>Bonus</td><td class="text-end">${{ number_format($payroll->bonus ?? 0, 2) }}</td></tr>
                    <tr><td>Deductions</td><td class="text-end">-${{ number_format($payroll->deductions ?? 0, 2) }}</td></tr>
                    <tr><td>Worked Hours</td><td class="text-end">{{ number_format($payroll->total_hours ?? 0, 2) }}</td></tr>
                    <tr class="fw-bold fs-5"><td>Total Payout</td><td class="text-end text-primary">${{ number_format($payroll->total_payout, 2) }}</td></tr>
                    </tbody>
                </table>
            </div>

            @if($payroll->notes)
                <div class="alert alert-light border"><strong>Notes:</strong> {{ $payroll->notes }}</div>
            @endif

            <div class="row g-4 mt-5">
                <div class="col-md-6">
                    <div class="border-top pt-3 text-muted">Prepared by payroll administrator</div>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="border-top pt-3 text-muted">Authorized signature</div>
                </div>
            </div>
        </div>
    </div>
@endsection
