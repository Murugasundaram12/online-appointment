@extends('layouts.app')

@section('title', 'Payroll Reports')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">Payroll Reports</h1>
                <p class="text-muted mb-0">Monthly and yearly payroll summary from real payroll records.</p>
            </div>
            <a href="{{ route('payroll.index') }}" class="btn btn-light border">Back to Payroll</a>
        </div>

        <form method="GET" class="card border-0 shadow-sm mb-4">
            <div class="card-body row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Start date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ \Carbon\Carbon::parse($startDate)->toDateString() }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ \Carbon\Carbon::parse($endDate)->toDateString() }}">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary">Run Report</button>
                </div>
            </div>
        </form>

        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Net Payroll</div><div class="fs-4 fw-bold">${{ number_format($summary['totalPayout'], 2) }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Salary</div><div class="fs-4 fw-bold">${{ number_format($summary['totalSalary'], 2) }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Commission</div><div class="fs-4 fw-bold">${{ number_format($summary['totalCommission'], 2) }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Deductions</div><div class="fs-4 fw-bold">${{ number_format($summary['totalDeductions'], 2) }}</div></div></div></div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="fs-6 fw-bold">Highest Payroll</h2>
                        <p class="mb-0">{{ optional(optional($summary['highest'])->staff)->name ?? 'Not available' }}</p>
                        <div class="fs-4 fw-bold text-primary">${{ number_format(optional($summary['highest'])->total_payout ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="fs-6 fw-bold">Lowest Payroll</h2>
                        <p class="mb-0">{{ optional(optional($summary['lowest'])->staff)->name ?? 'Not available' }}</p>
                        <div class="fs-4 fw-bold text-primary">${{ number_format(optional($summary['lowest'])->total_payout ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>ID</th><th>Staff</th><th>Period</th><th>Status</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    @forelse($payrolls as $payroll)
                        <tr>
                            <td>{{ $payroll->payroll_number }}</td>
                            <td>{{ optional($payroll->staff)->name ?? 'Not available' }}</td>
                            <td>{{ $payroll->period_start->format('M d, Y') }} - {{ $payroll->period_end->format('M d, Y') }}</td>
                            <td>{{ ucfirst($payroll->display_status) }}</td>
                            <td class="text-end fw-bold">${{ number_format($payroll->total_payout, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">No payroll records found for this period.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
