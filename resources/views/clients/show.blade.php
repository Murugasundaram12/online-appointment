@extends('layouts.app')

@section('title', 'Client Profile - ' . ($client->name ?? 'Client Details'))

@push('styles')
    <style>
        .profile-card {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
        }

        .avatar-circle-lg {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: #ffffff;
            font-size: 1.6rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }

        .stat-card-metric {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1e293b;
        }

        .nav-tabs-custom .nav-link {
            border: none;
            color: #64748b;
            font-weight: 600;
            padding: 0.85rem 1.25rem;
            border-bottom: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .nav-tabs-custom .nav-link:hover {
            color: #3b82f6;
        }

        .nav-tabs-custom .nav-link.active {
            color: #3b82f6;
            background: transparent;
            border-bottom-color: #3b82f6;
        }

        .timeline-item {
            position: relative;
            padding-left: 32px;
            padding-bottom: 24px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 11px;
            top: 24px;
            bottom: 0;
            width: 2px;
            background: #e2e8f0;
        }

        .timeline-item:last-child::before {
            display: none;
        }

        .timeline-badge {
            position: absolute;
            left: 0;
            top: 0;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 0.75rem;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-4 py-4">
        <!-- Success / Error Alert Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-1 fs-5 align-middle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Profile Header Bar -->
        <div class="card profile-card mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-circle-lg">
                            @if($client->photo)
                                <img src="{{ asset('storage/' . $client->photo) }}" alt="{{ $client->name }}" class="rounded-circle w-100 h-100 object-fit-cover">
                            @else
                                {{ strtoupper(substr($client->first_name ?? $client->name, 0, 1) . substr($client->last_name ?? '', 0, 1)) }}
                            @endif
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h1 class="fs-4 fw-bold text-dark m-0">{{ $client->name }}</h1>
                                <span class="badge bg-primary text-white rounded-pill px-3">{{ $client->client_code ?? 'CLI-' . $client->id }}</span>
                                <span class="badge {{ strtolower($client->status) === 'active' ? 'bg-success' : 'bg-secondary' }} text-white rounded-pill px-2">
                                    {{ ucfirst($client->status ?? 'Active') }}
                                </span>
                                @if($client->is_vip)
                                    <span class="badge bg-warning text-dark rounded-pill px-2"><i class="bx bx-star me-1"></i>VIP</span>
                                @endif
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-3 text-muted small mt-1">
                                <span><i class="bx bx-calendar-check me-1 text-primary"></i> Created: {{ $client->client_since ? $client->client_since->format('M d, Y') : $client->created_at->format('M d, Y') }}</span>
                                <span><i class="bx bx-time me-1 text-info"></i> Last Visit: {{ $lastVisit ? $lastVisit->format('M d, Y') : 'No past visits' }}</span>
                                <span><i class="bx bx-calendar-event me-1 text-success"></i> Next Appointment: {{ $nextAppointment ? $nextAppointment->format('M d, Y g:i A') : 'None scheduled' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('clients.edit', $client->id) }}" class="btn btn-outline-primary rounded-pill px-3 fw-semibold">
                            <i class="bx bx-edit me-1"></i> Edit Profile
                        </a>
                        <a href="{{ route('clients.index') }}" class="btn btn-light border text-muted rounded-pill px-3">
                            <i class="bx bx-arrow-back me-1"></i> Client List
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Summary Metric Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card profile-card p-3 text-center">
                    <div class="text-muted small fw-semibold">Total Visits</div>
                    <div class="stat-card-metric text-primary mt-1">{{ $client->completed_appointments_count }}</div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <div class="card profile-card p-3 text-center">
                    <div class="text-muted small fw-semibold">Upcoming</div>
                    <div class="stat-card-metric text-info mt-1">{{ $client->upcoming_appointments_count }}</div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <div class="card profile-card p-3 text-center">
                    <div class="text-muted small fw-semibold">Completed</div>
                    <div class="stat-card-metric text-success mt-1">{{ $client->completed_appointments_count }}</div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <div class="card profile-card p-3 text-center">
                    <div class="text-muted small fw-semibold">Cancelled</div>
                    <div class="stat-card-metric text-danger mt-1">{{ $client->cancelled_appointments_count }}</div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <div class="card profile-card p-3 text-center">
                    <div class="text-muted small fw-semibold">No Show</div>
                    <div class="stat-card-metric text-secondary mt-1">{{ $client->no_show_appointments_count ?? 0 }}</div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <div class="card profile-card p-3 text-center">
                    <div class="text-muted small fw-semibold">Lifetime Revenue</div>
                    <div class="stat-card-metric text-success mt-1">${{ number_format($totalPaid, 2) }}</div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <div class="card profile-card p-3 text-center">
                    <div class="text-muted small fw-semibold">Outstanding</div>
                    <div class="stat-card-metric {{ $outstanding > 0 ? 'text-danger' : 'text-muted' }} mt-1">${{ number_format($outstanding, 2) }}</div>
                </div>
            </div>
        </div>

        <!-- Multi-Tab Navigation -->
        <div class="card profile-card mb-4">
            <div class="card-header bg-white border-bottom p-0">
                <ul class="nav nav-tabs nav-tabs-custom px-3" id="clientTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="tab-overview-btn" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button"><i class="bx bx-user me-1"></i> Overview</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="tab-appts-btn" data-bs-toggle="tab" data-bs-target="#tab-appts" type="button"><i class="bx bx-calendar me-1"></i> Appointment History</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="tab-invoices-btn" data-bs-toggle="tab" data-bs-target="#tab-invoices" type="button"><i class="bx bx-receipt me-1"></i> Invoices</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="tab-payments-btn" data-bs-toggle="tab" data-bs-target="#tab-payments" type="button"><i class="bx bx-credit-card me-1"></i> Payments</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="tab-packages-btn" data-bs-toggle="tab" data-bs-target="#tab-packages" type="button"><i class="bx bx-package me-1"></i> Packages</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="tab-forms-btn" data-bs-toggle="tab" data-bs-target="#tab-forms" type="button"><i class="bx bx-file me-1"></i> Forms</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="tab-timeline-btn" data-bs-toggle="tab" data-bs-target="#tab-timeline" type="button"><i class="bx bx-history me-1"></i> Timeline</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="tab-notes-btn" data-bs-toggle="tab" data-bs-target="#tab-notes" type="button"><i class="bx bx-note me-1"></i> Notes</button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="clientTabsContent">
                    <!-- TAB 1: OVERVIEW -->
                    <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-12 col-md-6">
                                <h6 class="fw-bold text-dark mb-3"><i class="bx bx-id-card me-2 text-primary"></i>Personal Details</h6>
                                <table class="table table-sm table-borderless">
                                    <tr><td class="text-muted w-50">First Name</td><td class="fw-semibold">{{ $client->first_name ?: '-' }}</td></tr>
                                    <tr><td class="text-muted">Last Name</td><td class="fw-semibold">{{ $client->last_name ?: '-' }}</td></tr>
                                    <tr><td class="text-muted">Full Name</td><td class="fw-semibold">{{ $client->name }}</td></tr>
                                    <tr><td class="text-muted">Gender</td><td class="fw-semibold text-capitalize">{{ $client->gender ?: '-' }}</td></tr>
                                    <tr><td class="text-muted">Date of Birth</td><td class="fw-semibold">{{ $client->dob ? $client->dob->format('M d, Y') : '-' }}</td></tr>
                                    <tr><td class="text-muted">Auto-calculated Age</td><td class="fw-semibold">{{ $client->age ? $client->age . ' years old' : '-' }}</td></tr>
                                    <tr><td class="text-muted">Phone Number</td><td class="fw-semibold">{{ $client->phone ?: '-' }}</td></tr>
                                    <tr><td class="text-muted">Alternate Phone</td><td class="fw-semibold">{{ $client->alternate_phone ?: '-' }}</td></tr>
                                    <tr><td class="text-muted">Email Address</td><td class="fw-semibold">{{ $client->email ?: '-' }}</td></tr>
                                </table>
                            </div>

                            <div class="col-12 col-md-6">
                                <h6 class="fw-bold text-dark mb-3"><i class="bx bx-map me-2 text-primary"></i>Address & Emergency Details</h6>
                                <table class="table table-sm table-borderless">
                                    <tr><td class="text-muted w-50">Address Line 1</td><td class="fw-semibold">{{ $client->address_line1 ?: '-' }}</td></tr>
                                    <tr><td class="text-muted">Address Line 2</td><td class="fw-semibold">{{ $client->address_line2 ?: '-' }}</td></tr>
                                    <tr><td class="text-muted">City</td><td class="fw-semibold">{{ $client->city ?: '-' }}</td></tr>
                                    <tr><td class="text-muted">State</td><td class="fw-semibold">{{ $client->state ?: '-' }}</td></tr>
                                    <tr><td class="text-muted">Country</td><td class="fw-semibold">{{ $client->country ?: '-' }}</td></tr>
                                    <tr><td class="text-muted">Postal Code</td><td class="fw-semibold">{{ $client->postal_code ?: '-' }}</td></tr>
                                    <tr><td class="text-muted">Emergency Contact</td><td class="fw-semibold">{{ $client->emergency_contact ?: '-' }}</td></tr>
                                    <tr><td class="text-muted">Emergency Phone</td><td class="fw-semibold">{{ $client->emergency_phone ?: '-' }}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: APPOINTMENT HISTORY -->
                    <div class="tab-pane fade" id="tab-appts" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Service</th>
                                        <th>Staff</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Invoice</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($allAppointments as $app)
                                        <tr>
                                            <td class="fw-semibold">{{ $app->start_time ? $app->start_time->format('M d, Y g:i A') : '-' }}</td>
                                            <td>{{ $app->service?->name ?? 'N/A' }}</td>
                                            <td>{{ $app->staff?->name ?? 'N/A' }}</td>
                                            <td>{{ $app->location?->name ?? 'Flexible' }}</td>
                                            <td>
                                                @php
                                                    $badgeClass = match($app->status) {
                                                        'completed' => 'bg-success',
                                                        'cancelled' => 'bg-danger',
                                                        'confirmed' => 'bg-primary',
                                                        'no_show'   => 'bg-secondary',
                                                        'pending'   => 'bg-warning text-dark',
                                                        default     => 'bg-info',
                                                    };
                                                @endphp
                                                <span class="badge {{ $badgeClass }}">
                                                    {{ ucfirst(str_replace('_', ' ', $app->status)) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($app->invoice)
                                                    <a href="{{ route('invoices.show', $app->invoice->id) }}" class="text-decoration-none fw-semibold">
                                                        #{{ $app->invoice->invoice_number }}
                                                    </a>
                                                @else
                                                    <span class="text-muted small">No Invoice</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('calendar.index', ['staff_id' => $app->staff_id]) }}" class="btn btn-sm btn-outline-secondary">View Calendar</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">No appointments found for this client.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">{{ $allAppointments->appends(request()->except('appts_page'))->links() }}</div>
                    </div>

                    <!-- TAB 3: INVOICES -->
                    <div class="tab-pane fade" id="tab-invoices" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Issued Date</th>
                                        <th>Amount</th>
                                        <th>Paid</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoices as $inv)
                                        <tr>
                                            <td class="fw-bold text-primary">#{{ $inv->invoice_number }}</td>
                                            <td>{{ $inv->issued_date ? $inv->issued_date->format('M d, Y') : '-' }}</td>
                                            <td class="fw-semibold">${{ number_format($inv->total_amount, 2) }}</td>
                                            <td class="text-success fw-semibold">${{ number_format($inv->paid_amount, 2) }}</td>
                                            <td>
                                                <span class="badge {{ $inv->status === 'paid' ? 'bg-success' : 'bg-warning' }} text-white">
                                                    {{ ucfirst($inv->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('invoices.download', $inv->id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bx bx-download me-1"></i> Download PDF
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">No invoices generated for this client.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">{{ $invoices->appends(request()->except('invoices_page'))->links() }}</div>
                    </div>

                    <!-- TAB 4: PAYMENTS -->
                    <div class="tab-pane fade" id="tab-payments" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Paid Date</th>
                                        <th>Method</th>
                                        <th>Amount</th>
                                        <th>Reference #</th>
                                        <th>Invoice #</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($payments as $pay)
                                        <tr>
                                            <td>{{ $pay->payment_date ? $pay->payment_date->format('M d, Y') : '-' }}</td>
                                            <td class="text-capitalize fw-semibold">{{ $pay->payment_method ?? 'Cash' }}</td>
                                            <td class="fw-bold text-success">${{ number_format($pay->amount, 2) }}</td>
                                            <td>{{ $pay->transaction_id ?: '-' }}</td>
                                            <td>
                                                @if($pay->invoice)
                                                    <a href="{{ route('invoices.show', $pay->invoice->id) }}">#{{ $pay->invoice->invoice_number }}</a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">No payment records found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">{{ $payments->appends(request()->except('payments_page'))->links() }}</div>
                    </div>

                    <!-- TAB 5: PACKAGES -->
                    <div class="tab-pane fade" id="tab-packages" role="tabpanel">
                        <div class="row g-3">
                            @forelse($packages as $pkg)
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="card border rounded-3 p-3 h-100">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="fw-bold text-dark mb-0">{{ $pkg->name }}</h6>
                                            <span class="badge bg-success text-white">Active</span>
                                        </div>
                                        <p class="small text-muted mb-3">{{ $pkg->description ?: 'Multi-session care package.' }}</p>
                                        <div class="d-flex justify-content-between small border-top pt-2">
                                            <span class="text-muted">Price: ${{ number_format($pkg->price, 2) }}</span>
                                            <span class="fw-semibold text-primary">Validity: {{ $pkg->validity_days }} Days</span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-center py-4 text-muted">No active packages purchased by client.</div>
                            @endforelse
                        </div>
                    </div>

                    <!-- TAB 6: FORMS -->
                    <div class="tab-pane fade" id="tab-forms" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Form Name</th>
                                        <th>Submitted At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($formRecords as $fr)
                                        <tr>
                                            <td class="fw-semibold">{{ $fr->form?->name ?? 'Form Record' }}</td>
                                            <td>{{ $fr->submitted_at ? $fr->submitted_at->format('M d, Y g:i A') : '-' }}</td>
                                            <td>
                                                <a href="{{ route('form-records.show', $fr->id) }}" class="btn btn-sm btn-outline-secondary">View Form Response</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted">No form responses submitted for this client.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 7: TIMELINE -->
                    <div class="tab-pane fade" id="tab-timeline" role="tabpanel">
                        <div class="pt-2 ps-2">
                            @foreach($timeline as $event)
                                <div class="timeline-item">
                                    <div class="timeline-badge {{ $event['color'] }}">
                                        <i class="bx {{ $event['icon'] }}"></i>
                                    </div>
                                    <div class="fw-bold text-dark">{{ $event['title'] }}</div>
                                    <div class="small text-muted mb-1">{{ $event['date'] ? Carbon\Carbon::parse($event['date'])->format('M d, Y g:i A') : 'N/A' }}</div>
                                    <div class="small text-secondary">{{ $event['description'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- TAB 8: NOTES -->
                    <div class="tab-pane fade" id="tab-notes" role="tabpanel">
                        <form action="{{ route('clients.update', $client->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="first_name" value="{{ $client->first_name }}">
                            <input type="hidden" name="last_name" value="{{ $client->last_name }}">
                            <input type="hidden" name="phone" value="{{ $client->phone }}">

                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark">Client Notes & Medical History</label>
                                <textarea name="notes" class="form-control" rows="5" placeholder="Enter clinical notes, patient preferences, or allergy details...">{{ old('notes', $client->notes) }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Save Notes</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
