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
            </div>
        </div>

        @php
            $cards = [
                ['Total clients', $stats['clients'], 'bx-user', 'Client records in your database'],
                ['Active staff', $stats['active_staff'], 'bx-group', 'Available providers and operators'],
                ['Active services', $stats['active_services'], 'bx-layer', 'Bookable services'],
                ['Today appointments', $stats['today_appointments'], 'bx-calendar', 'Scheduled for today'],
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
                        @foreach(['pending', 'booked', 'completed', 'cancelled'] as $status)
                            <div class="d-flex justify-content-between small border-bottom py-2">
                                <span>{{ ucfirst($status) }}</span>
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
                        <div class="d-flex justify-content-between gap-3 border-bottom py-3">
                            <div class="d-flex gap-3">
                                <div class="avatar-initials">{{ substr(optional($appointment->client)->name ?: 'NA', 0, 2) }}</div>
                                <div>
                                    <div class="fw-semibold">{{ optional($appointment->client)->name ?: 'Unassigned' }}</div>
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
                        <div class="d-flex justify-content-between gap-3 border-bottom py-3">
                            <div class="d-flex gap-3">
                                <div class="avatar-initials">{{ substr(optional($appointment->client)->name ?: 'NA', 0, 2) }}</div>
                                <div>
                                    <div class="fw-semibold">{{ optional($appointment->client)->name ?: 'Unassigned' }}</div>
                                    <div class="text-muted small">{{ optional($appointment->service)->name ?: 'Service' }} with {{ optional($appointment->staff)->name ?: 'N/A' }}</div>
                                </div>
                            </div>
                            <div class="text-end text-muted small">{{ optional($appointment->start_time)->format('M j, g:i A') }}</div>
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
            labels: ['Pending', 'Booked', 'Completed', 'Cancelled'],
            datasets: [{ data: [
                {{ $statusSummary['pending'] ?? 0 }},
                {{ $statusSummary['booked'] ?? 0 }},
                {{ $statusSummary['completed'] ?? 0 }},
                {{ $statusSummary['cancelled'] ?? 0 }}
            ], backgroundColor: ['#d97706', '#4f46e5', '#16a34a', '#dc2626'], borderWidth: 0 }]
        };
    </script>
@endpush
