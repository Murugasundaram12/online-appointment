@extends('layouts.app')

@section('title', 'Appointments Report')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">Appointments Report</h1>
                <p class="text-muted mb-0">Booking volume and status breakdown.</p>
            </div>
            <a href="{{ route('reports.index') }}" class="btn btn-light border">All Reports</a>
        </div>

        @include('reports._date_filter', [
            'exportType' => 'appointments',
            'paginator' => $appointments,
            'extraFields' => [
                'status' => ['label' => 'Status', 'items' => $filters['statuses']->mapWithKeys(fn ($s) => [$s => ucfirst($s)])->all()],
                'staff_id' => ['label' => 'Staff', 'items' => $filters['staff']->pluck('name', 'id')->all()],
                'service_id' => ['label' => 'Service', 'items' => $filters['services']->pluck('name', 'id')->all()],
                'location_id' => ['label' => 'Location', 'items' => $filters['locations']->pluck('name', 'id')->all()],
            ],
        ])

        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Total Appointments</div><div class="fs-4 fw-bold">{{ $summary['total'] }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Completed</div><div class="fs-4 fw-bold text-success">{{ $summary['completed'] }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Cancelled</div><div class="fs-4 fw-bold text-danger">{{ $summary['cancelled'] }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Pending / Booked</div><div class="fs-4 fw-bold text-primary">{{ $summary['booked'] }}</div></div></div></div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light text-muted small">
                        <tr>
                            <th class="ps-4">Start</th>
                            <th>Client</th>
                            <th>Staff</th>
                            <th>Service</th>
                            <th>Location</th>
                            <th class="pe-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($appointments as $appointment)
                        <tr>
                            <td class="ps-4">{{ $appointment->start_time ? $appointment->start_time->format('M d, Y g:i A') : '-' }}</td>
                            <td>{{ optional($appointment->client)->name ?? 'Walk-in' }}</td>
                            <td>{{ optional($appointment->staff)->name ?? 'N/A' }}</td>
                            <td>{{ optional($appointment->service)->name ?? 'N/A' }}</td>
                            <td>{{ optional($appointment->location)->name ?? 'N/A' }}</td>
                            <td class="pe-4">
                                <span class="badge {{ $appointment->status === 'completed' ? 'bg-success-subtle text-success-emphasis' : ($appointment->status === 'cancelled' ? 'bg-danger-subtle text-danger-emphasis' : ($appointment->status === 'pending' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-primary-subtle text-primary-emphasis')) }}">
                                    {{ ucfirst($appointment->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No appointments found for the selected period and filters.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.pagination', ['paginator' => $appointments])
        </div>
    </div>
@endsection
