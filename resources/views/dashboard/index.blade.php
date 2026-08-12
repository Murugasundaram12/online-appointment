@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="container-fluid pt-4">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <h2 class="page-title mb-1">Business overview</h2>
                <div class="text-muted">Live appointment, finance, and client performance.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('calendar.index') }}" class="btn btn-primary"><i class='bx bx-calendar-plus me-1'></i> New appointment</a>
                <a href="{{ route('clients.index') }}" class="btn btn-white"><i class='bx bx-user-plus me-1'></i> Add client</a>
                <a href="{{ route('payment-records.index') }}" class="btn btn-white"><i class='bx bx-credit-card me-1'></i> Add payment</a>
                <a href="{{ route('invoices.create') }}" class="btn btn-white"><i class='bx bx-receipt me-1'></i> Create invoice</a>
            </div>
        </div>

        <!-- Today Summary Section -->
        <div class="mb-4">
            <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.75rem; letter-spacing: 0.05em;">Today Summary</h6>
            <div class="row g-3">
                <div class="col-6 col-md">
                    <div class="bg-white rounded shadow-sm p-3 border-start border-4 border-primary">
                        <div class="text-muted small">Today Appointments</div>
                        <div class="fs-4 fw-bold text-dark mt-1">{{ $todayStats['total'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="bg-white rounded shadow-sm p-3 border-start border-4" style="border-color: #6366f1 !important;">
                        <div class="text-muted small">Confirmed</div>
                        <div class="fs-4 fw-bold mt-1" style="color: #6366f1;">{{ $todayStats['confirmed'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="bg-white rounded shadow-sm p-3 border-start border-4 border-success">
                        <div class="text-muted small">Completed</div>
                        <div class="fs-4 fw-bold text-success mt-1">{{ $todayStats['completed'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="bg-white rounded shadow-sm p-3 border-start border-4" style="border-color: #8b5cf6 !important;">
                        <div class="text-muted small">No Shows</div>
                        <div class="fs-4 fw-bold mt-1" style="color: #8b5cf6;">{{ $todayStats['no_show'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-12 col-md">
                    <div class="bg-white rounded shadow-sm p-3 border-start border-4" style="border-color: #10b981 !important;">
                        <div class="text-muted small">Today's Revenue</div>
                        <div class="fs-4 fw-bold mt-1" style="color: #059669;">${{ number_format($todayStats['revenue'] ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $cards = [
                ['Total clients', $stats['clients'], 'bx-user', 'Client records in your database'],
                ['Active staff', $stats['active_staff'], 'bx-group', 'Available providers and operators'],
                ['Active services', $stats['active_services'], 'bx-layer', 'Bookable services'],
                ['Pending appointments', $stats['pending_appointments'], 'bx-time-five', 'Need attention'],
                ['Completed appointments', $stats['completed_appointments'], 'bx-check-circle', 'All-time completed'],
                ['Outstanding amount', '$' . number_format($stats['outstanding_invoice_amount'], 2), 'bx-receipt', 'Open invoice balance'],
                ['Paid amount', '$' . number_format($stats['paid_invoice_amount'], 2), 'bx-dollar', 'Collected invoice amount'],
                ['Pending payroll', $stats['pending_payroll_count'], 'bx-wallet', 'Salary records awaiting payout'],
                ['Monthly payroll', '$' . number_format($stats['monthly_payroll_amount'], 2), 'bx-money', 'Paid payroll this month'],
                ['Upcoming salaries', $stats['upcoming_salary_payments'], 'bx-calendar-event', 'Payments due in 14 days'],
            ];
        @endphp

        <div class="row g-3">
            @foreach($cards as [$label, $value, $icon, $helper])
                <div class="col-sm-6 col-xl-3">
                    <div class="bg-white rounded shadow-sm p-4 h-100 kpi-card">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="text-muted small mb-1">{{ $label }}</div>
                                <div class="fs-3 fw-bold financial">{{ $value }}</div>
                                <div class="text-muted small mt-2">{{ $helper }}</div>
                            </div>
                            <div class="kpi-icon"><i class="bx {{ $icon }}"></i></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Today's Schedule & Staff Workload Section -->
        <div class="row g-3 mt-1">
            <div class="col-lg-7">
                <div class="bg-white rounded shadow-sm p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="fs-6 fw-bold m-0"><i class='bx bx-time me-1 text-primary'></i> Today's schedule</h3>
                        <span class="badge bg-light text-dark fw-normal">{{ count($todaySchedule ?? []) }} appointments</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Time</th>
                                    <th>Client</th>
                                    <th>Service</th>
                                    <th>Staff</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($todaySchedule ?? [] as $app)
                                    <tr>
                                        <td class="fw-semibold small">{{ $app->start_time ? $app->start_time->format('g:i A') : '-' }}</td>
                                        <td>
                                            @if($app->client)
                                                <a href="{{ route('clients.show', $app->client->id) }}" class="text-dark fw-semibold text-decoration-none small">{{ $app->client->name }}</a>
                                            @else
                                                <span class="text-muted small">Unassigned</span>
                                            @endif
                                        </td>
                                        <td class="small">{{ $app->service?->name ?? 'N/A' }}</td>
                                        <td class="small">{{ $app->staff?->name ?? 'N/A' }}</td>
                                        <td>
                                            @php
                                                $stBadge = match($app->status) {
                                                    'completed' => 'bg-success',
                                                    'cancelled' => 'bg-danger',
                                                    'confirmed' => 'bg-primary',
                                                    'no_show'   => 'bg-secondary',
                                                    'pending'   => 'bg-warning text-dark',
                                                    default     => 'bg-info',
                                                };
                                            @endphp
                                            <span class="badge {{ $stBadge }}">{{ ucfirst(str_replace('_', ' ', $app->status)) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('calendar.index', ['staff_id' => $app->staff_id]) }}" class="btn btn-sm btn-outline-secondary" style="font-size: 0.75rem;">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted small">No appointments scheduled for today.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="bg-white rounded shadow-sm p-4 h-100">
                    <h3 class="fs-6 fw-bold mb-3"><i class='bx bx-user-check me-1 text-info'></i> Staff daily workload</h3>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Staff</th>
                                    <th class="text-center">Today</th>
                                    <th class="text-center">Confirmed</th>
                                    <th class="text-center">Completed</th>
                                    <th class="text-center">No Show</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($staffWorkload ?? [] as $w)
                                    <tr>
                                        <td class="fw-semibold small">{{ $w['staff']->name }}</td>
                                        <td class="text-center"><span class="badge bg-light text-dark">{{ $w['total'] }}</span></td>
                                        <td class="text-center"><span class="badge bg-primary">{{ $w['confirmed'] }}</span></td>
                                        <td class="text-center"><span class="badge bg-success">{{ $w['completed'] }}</span></td>
                                        <td class="text-center"><span class="badge bg-secondary">{{ $w['no_show'] }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-3 text-muted small">No staff members found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-lg-8">
                <div class="bg-white rounded shadow-sm p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h3 class="fs-6 fw-bold mb-1">Monthly performance</h3>
                            <div class="text-muted small">Appointments and paid revenue from real records</div>
                        </div>
                    </div>
                    <div style="height: 320px"><canvas id="bookingTrendChart"></canvas></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="bg-white rounded shadow-sm p-4 h-100">
                    <h3 class="fs-6 fw-bold mb-3">Appointment status</h3>
                    <div style="height: 230px"><canvas id="bookingChannelChart"></canvas></div>
                    <div class="mt-3">
                        @foreach(['pending', 'booked', 'confirmed', 'completed', 'cancelled', 'no_show'] as $status)
                            <div class="d-flex justify-content-between small border-bottom py-2">
                                <span>{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                                <strong>{{ $statusSummary[$status] ?? 0 }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-lg-6">
                <div class="bg-white rounded shadow-sm p-4 h-100">
                    <h3 class="fs-6 fw-bold mb-3">Recent appointments</h3>
                    @forelse($recentAppointments as $appointment)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                            <div class="d-flex gap-3 align-items-center">
                                <div class="avatar-initials">{{ substr(optional($appointment->client)->name ?: 'NA', 0, 2) }}</div>
                                <div>
                                    <div class="fw-semibold">
                                        @if($appointment->client)
                                            <a href="{{ route('clients.show', $appointment->client->id) }}" class="text-dark text-decoration-none">{{ $appointment->client->name }}</a>
                                        @else
                                            Unassigned
                                        @endif
                                    </div>
                                    <div class="text-muted small">{{ optional($appointment->service)->name ?: 'Service' }} with {{ optional($appointment->staff)->name ?: 'N/A' }}</div>
                                </div>
                            </div>
                            <div class="text-end text-muted small">{{ optional($appointment->start_time)->format('M j, g:i A') }}</div>
                        </div>
                    @empty
                        <div class="empty-state"><i class='bx bx-calendar-x'></i><div>No recent appointments</div></div>
                    @endforelse
                </div>
            </div>
            <div class="col-lg-6">
                <div class="bg-white rounded shadow-sm p-4 h-100">
                    <h3 class="fs-6 fw-bold mb-3">Upcoming appointments</h3>
                    @forelse($upcomingAppointments as $appointment)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                            <div class="d-flex gap-3 align-items-center">
                                <div class="avatar-initials">{{ substr(optional($appointment->client)->name ?: 'NA', 0, 2) }}</div>
                                <div>
                                    <div class="fw-semibold">
                                        @if($appointment->client)
                                            <a href="{{ route('clients.show', $appointment->client->id) }}" class="text-dark text-decoration-none">{{ $appointment->client->name }}</a>
                                        @else
                                            Unassigned
                                        @endif
                                    </div>
                                    <div class="text-muted small">{{ optional($appointment->service)->name ?: 'Service' }} with {{ optional($appointment->staff)->name ?: 'N/A' }}</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="text-end text-muted small">{{ optional($appointment->start_time)->format('M j, g:i A') }}</div>
                                <a href="{{ route('calendar.index', ['staff_id' => $appointment->staff_id]) }}" class="btn btn-sm btn-outline-secondary" style="font-size: 0.75rem;">View</a>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state"><i class='bx bx-calendar'></i><div>No upcoming appointments</div></div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        window.dashboardAppointmentChart = {
            labels: @json($dailyLabels),
            datasets: [
                { type: 'bar', label: 'Appointments', data: @json($dailyAppointmentCounts), backgroundColor: '#4f46e5', borderRadius: 8 },
                { type: 'line', label: 'Revenue', data: @json($dailyRevenueCounts), borderColor: '#16a34a', backgroundColor: 'rgba(22, 163, 74, .12)', tension: .35, fill: true }
            ]
        };
        window.dashboardStatusChart = {
            labels: ['Pending', 'Booked', 'Confirmed', 'Completed', 'Cancelled', 'No Show'],
            datasets: [{ data: [
                {{ $statusSummary['pending'] ?? 0 }},
                {{ $statusSummary['booked'] ?? 0 }},
                {{ $statusSummary['confirmed'] ?? 0 }},
                {{ $statusSummary['completed'] ?? 0 }},
                {{ $statusSummary['cancelled'] ?? 0 }},
                {{ $statusSummary['no_show'] ?? 0 }}
            ], backgroundColor: ['#d97706', '#3699ff', '#6366f1', '#16a34a', '#dc2626', '#8b5cf6'], borderWidth: 0 }]
        };
    </script>
@endpush

