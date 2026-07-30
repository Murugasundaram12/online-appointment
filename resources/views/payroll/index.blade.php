@extends('layouts.app')

@section('title', 'Payroll')

@push('styles')
    <style>
        .payroll-kpi { border: 1px solid var(--border); border-radius: 14px; background: #fff; box-shadow: var(--shadow-sm); }
        .payroll-kpi .label { color: var(--text-secondary); font-size: .82rem; font-weight: 700; text-transform: uppercase; }
        .payroll-kpi .value { color: var(--text-primary); font-weight: 850; font-size: 1.35rem; }
        .payroll-table th { color: var(--text-secondary); font-size: .78rem; text-transform: uppercase; white-space: nowrap; }
        .payroll-table td { vertical-align: middle; white-space: nowrap; }
        .payroll-badge { border-radius: 999px; padding: .35rem .7rem; font-weight: 800; font-size: .75rem; }
        .payroll-badge.pending { background: var(--warning-soft); color: var(--warning); }
        .payroll-badge.completed, .payroll-badge.paid { background: var(--success-soft); color: var(--success); }
        .payroll-badge.processing { background: var(--info-soft); color: var(--info); }
        .payroll-badge.cancelled { background: var(--danger-soft); color: var(--danger); }
        @media print { .no-print, .sidebar, .topbar, nav { display: none !important; } body { background: #fff !important; } }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">Payroll</h1>
                <p class="text-muted mb-0">Manage salary, commission, bonus, deductions, and staff payouts.</p>
            </div>
            <div class="d-flex flex-wrap gap-2 no-print">
                <a href="{{ route('payroll.report') }}" class="btn btn-light border"><i class='bx bx-bar-chart'></i> Reports</a>
                <a href="{{ route('payroll.export.csv') }}" class="btn btn-light border"><i class='bx bx-download'></i> CSV</a>
                <a href="{{ route('payroll.create') }}" class="btn btn-primary"><i class='bx bx-plus'></i> Create Payroll</a>
            </div>
        </div>

        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
        @if(session('info')) <div class="alert alert-info">{{ session('info') }}</div> @endif

        <div class="row g-3 mb-4">
            @foreach([
                'Total Payroll' => $summary['total_payroll'],
                'Pending Payroll' => $summary['pending_payroll'],
                'Paid Payroll' => $summary['paid_payroll'],
                'This Month' => $summary['this_month_payroll'],
                'Total Salary' => '$' . number_format($summary['total_salary'], 2),
                'Commission' => '$' . number_format($summary['total_commission'], 2),
                'Bonus' => '$' . number_format($summary['total_bonus'], 2),
                'Deductions' => '$' . number_format($summary['total_deductions'], 2),
                'Net Payroll' => '$' . number_format($summary['net_payroll'], 2),
            ] as $label => $value)
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <div class="payroll-kpi p-3">
                        <div class="label">{{ $label }}</div>
                        <div class="value mt-1">{{ $value }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card border-0 shadow-sm mb-4 no-print">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3 align-items-end">
                    <div>
                        <h2 class="fs-6 fw-bold mb-1">Auto-generate Payroll</h2>
                        <p class="text-muted mb-0">Create pending payroll records for active staff with configured salary. Existing staff-period records are skipped.</p>
                    </div>
                    <form id="generatePayrollForm" class="row g-2 align-items-end" data-confirm="This will generate payroll for eligible active staff and skip duplicates." data-confirm-title="Generate payroll?" data-confirm-text="Generate" data-confirm-class="btn-primary">
                        @csrf
                        <div class="col-auto">
                            <label class="form-label">Start <span class="required-mark">*</span></label>
                            <input type="date" name="period_start" class="form-control" required>
                        </div>
                        <div class="col-auto">
                            <label class="form-label">End <span class="required-mark">*</span></label>
                            <input type="date" name="period_end" class="form-control" required>
                        </div>
                        <div class="col-auto">
                            <label class="form-label">Payment date <span class="required-mark">*</span></label>
                            <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-primary">Generate</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4 no-print">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Staff, payroll ID, status">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Staff</label>
                        <select class="form-select" name="staff_id">
                            <option value="">All staff</option>
                            @foreach($staff as $member)
                                <option value="{{ $member->id }}" {{ request('staff_id') == $member->id ? 'selected' : '' }}>{{ $member->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Paid</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From</label>
                        <input type="date" class="form-control" name="period_start" value="{{ request('period_start') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To</label>
                        <input type="date" class="form-control" name="period_end" value="{{ request('period_end') }}">
                    </div>
                    <div class="col-md-1 d-grid">
                        <button class="btn btn-primary">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table payroll-table align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Payroll ID</th>
                        <th>Staff</th>
                        <th>Role</th>
                        <th>Period</th>
                        <th class="text-end">Salary</th>
                        <th class="text-end">Commission</th>
                        <th class="text-end">Bonus</th>
                        <th class="text-end">Deduction</th>
                        <th class="text-end">Total</th>
                        <th>Payment Date</th>
                        <th>Status</th>
                        <th class="text-end no-print">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($payrolls as $payroll)
                        <tr>
                            <td class="fw-bold">{{ $payroll->payroll_number }}</td>
                            <td>{{ optional($payroll->staff)->name ?? 'Not available' }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', optional($payroll->staff)->access_level ?? 'staff')) }}</td>
                            <td>{{ $payroll->period_start->format('M d, Y') }} - {{ $payroll->period_end->format('M d, Y') }}</td>
                            <td class="text-end">${{ number_format($payroll->salary_amount, 2) }}</td>
                            <td class="text-end">${{ number_format($payroll->commission_amount ?? 0, 2) }}</td>
                            <td class="text-end">${{ number_format($payroll->bonus ?? 0, 2) }}</td>
                            <td class="text-end">${{ number_format($payroll->deductions ?? 0, 2) }}</td>
                            <td class="text-end fw-bold">${{ number_format($payroll->total_payout, 2) }}</td>
                            <td>{{ $payroll->payment_date ? $payroll->payment_date->format('M d, Y') : '-' }}</td>
                            <td><span class="payroll-badge {{ $payroll->status }}">{{ ucfirst($payroll->display_status) }}</span></td>
                            <td class="text-end no-print">
                                <div class="btn-group">
                                    <a href="{{ route('payroll.show', $payroll->id) }}" class="btn btn-sm btn-light border" title="View"><i class='bx bx-show'></i></a>
                                    <a href="{{ route('payroll.edit', $payroll->id) }}" class="btn btn-sm btn-light border" title="Edit"><i class='bx bx-edit'></i></a>
                                    @unless($payroll->isPaid())
                                        <form method="POST" action="{{ route('payroll.mark-paid', $payroll->id) }}" data-confirm="This will mark the payroll as paid and set the payment date if missing." data-confirm-title="Mark payroll paid?" data-confirm-text="Mark Paid" data-confirm-class="btn-success">
                                            @csrf
                                            <button class="btn btn-sm btn-light border" title="Mark Paid"><i class='bx bx-check-circle'></i></button>
                                        </form>
                                    @endunless
                                    <button type="button" onclick="window.print()" class="btn btn-sm btn-light border" title="Print"><i class='bx bx-printer'></i></button>
                                    <a href="{{ route('payroll.download', $payroll->id) }}" class="btn btn-sm btn-light border" title="PDF"><i class='bx bx-file'></i></a>
                                    <form method="POST" action="{{ route('payroll.destroy', $payroll->id) }}" data-confirm="Pending payroll can be deleted. Paid payroll is protected by the server." data-confirm-title="Delete payroll?" data-confirm-text="Delete" data-confirm-class="btn-danger">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-light border text-danger" title="Delete"><i class='bx bx-trash'></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center py-5 text-muted">
                                <i class='bx bx-wallet fs-1 d-block mb-2'></i>
                                No payroll records found.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white">{{ $payrolls->links() }}</div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const generateForm = document.getElementById('generatePayrollForm');
        if (generateForm) {
            generateForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const button = generateForm.querySelector('button');
                button.disabled = true;
                button.textContent = 'Generating...';
                try {
                    const response = await fetch('{{ route('payroll.generate') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': generateForm.querySelector('[name="_token"]').value,
                            'Accept': 'application/json',
                        },
                        body: new FormData(generateForm),
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Payroll generation failed.');
                    }
                    window.AppToast?.show({ type: 'success', title: 'Payroll generated', message: data.message });
                    window.location.reload();
                } catch (error) {
                    window.AppToast?.show({ type: 'danger', title: 'Payroll generation failed', message: error.message });
                } finally {
                    button.disabled = false;
                    button.textContent = 'Generate';
                }
            });
        }
    </script>
@endpush
