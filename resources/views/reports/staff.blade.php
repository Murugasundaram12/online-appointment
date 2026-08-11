@extends('layouts.app')

@section('title', 'Staff Performance Report')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">Staff Performance Report</h1>
                <p class="text-muted mb-0">Bookings and revenue generated per staff member.</p>
            </div>
            <a href="{{ route('reports.index') }}" class="btn btn-light border">All Reports</a>
        </div>

        @include('reports._date_filter', ['exportType' => 'staff', 'paginator' => $rows])

        <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Appointments</div><div class="fs-4 fw-bold">{{ $summary['total'] }}</div></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Staff With Bookings</div><div class="fs-4 fw-bold">{{ $summary['with_staff'] }}</div></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Revenue Generated</div><div class="fs-4 fw-bold text-success">${{ number_format($summary['revenue'], 2) }}</div></div></div></div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light text-muted small">
                        <tr>
                            <th class="ps-4">Staff</th>
                            <th>Role</th>
                            <th class="text-end">Appointments</th>
                            <th class="text-end">Completed</th>
                            <th class="text-end">Cancelled</th>
                            <th class="text-end pe-4">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td class="ps-4 fw-semibold">{{ optional($row->staff)->name ?? 'Unassigned' }}</td>
                            <td>{{ optional($row->staff)->access_level ? ucfirst(str_replace('_', ' ', $row->staff->access_level)) : '-' }}</td>
                            <td class="text-end">{{ $row->total }}</td>
                            <td class="text-end text-success">{{ $row->completed }}</td>
                            <td class="text-end text-danger">{{ $row->cancelled }}</td>
                            <td class="text-end pe-4 fw-semibold">${{ number_format((float) ($revenueByStaff[$row->staff_id] ?? 0), 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No staff bookings found for the selected period.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.pagination', ['paginator' => $rows])
        </div>
    </div>
@endsection
