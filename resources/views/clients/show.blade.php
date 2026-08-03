@extends('layouts.app')

@section('title', 'Client Details')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">{{ $client->name }}</h1>
                <p class="text-muted mb-0">Client profile, appointments, invoices and records.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('clients.edit', $client->id) }}" class="btn btn-primary">Edit Client</a>
                <a href="{{ route('clients.index') }}" class="btn btn-light border text-muted">Back to List</a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded d-flex align-items-center justify-content-center text-white fw-bold"
                                style="width: 56px; height: 56px; background: #4f46e5;">
                                {{ strtoupper(substr($client->name, 0, 2)) }}
                            </div>
                            <div>
                                <h2 class="fs-5 mb-1">{{ $client->name }}</h2>
                                @if($client->is_vip)
                                    <span class="badge bg-warning text-dark"><i class='bx bx-star me-1'></i>VIP</span>
                                @else
                                    <span class="badge bg-light text-muted">Regular</span>
                                @endif
                            </div>
                        </div>

                        <dl class="row small mb-0">
                            <dt class="col-5 text-muted">Email</dt>
                            <dd class="col-7 text-break">{{ $client->email ?: '-' }}</dd>
                            <dt class="col-5 text-muted">Phone</dt>
                            <dd class="col-7">{{ $client->phone ?: '-' }}</dd>
                            <dt class="col-5 text-muted">City</dt>
                            <dd class="col-7">{{ $client->city ?: '-' }}</dd>
                            <dt class="col-5 text-muted">Client Since</dt>
                            <dd class="col-7">{{ $client->client_since ? $client->client_since->format('M d, Y') : '-' }}</dd>
                        </dl>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h3 class="fs-6 fw-bold mb-3">Notes</h3>
                        @if(trim((string) $client->notes) !== '')
                            <p class="text-muted mb-0 text-break">{{ $client->notes }}</p>
                        @else
                            <div class="text-center py-4">
                                <i class='bx bx-note fs-2 text-muted'></i>
                                <p class="text-muted small mb-0 mt-2">No notes recorded.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="fs-6 fw-bold mb-3">Lifetime Summary</h3>
                        <dl class="row small mb-0">
                            <dt class="col-7 text-muted">Total appointments</dt>
                            <dd class="col-5 text-end fw-semibold">{{ $client->appointments_count }}</dd>
                            <dt class="col-7 text-muted">Upcoming</dt>
                            <dd class="col-5 text-end fw-semibold">{{ $client->upcoming_appointments_count }}</dd>
                            <dt class="col-7 text-muted">Completed</dt>
                            <dd class="col-5 text-end fw-semibold">{{ $client->completed_appointments_count }}</dd>
                            <dt class="col-7 text-muted">Cancelled</dt>
                            <dd class="col-5 text-end fw-semibold">{{ $client->cancelled_appointments_count }}</dd>
                            <dt class="col-7 text-muted">Total billed</dt>
                            <dd class="col-5 text-end fw-semibold">${{ number_format($totalInvoiced, 2) }}</dd>
                            <dt class="col-7 text-muted">Total paid</dt>
                            <dd class="col-5 text-end fw-semibold">${{ number_format($totalPaid, 2) }}</dd>
                            <dt class="col-7 text-muted text-danger">Outstanding balance</dt>
                            <dd class="col-5 text-end fw-bold {{ $outstanding > 0 ? 'text-danger' : '' }}">${{ number_format($outstanding, 2) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="text-muted small">Appointments</div>
                                <div class="fs-4 fw-bold">{{ $client->appointments_count }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="text-muted small">Upcoming</div>
                                <div class="fs-4 fw-bold">{{ $client->upcoming_appointments_count }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="text-muted small">Completed</div>
                                <div class="fs-4 fw-bold text-success">{{ $client->completed_appointments_count }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="text-muted small">Outstanding</div>
                                <div class="fs-4 fw-bold {{ $outstanding > 0 ? 'text-danger' : '' }}">${{ number_format($outstanding, 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-0">
                        <div class="p-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <h3 class="fs-6 fw-bold mb-0">Upcoming Appointments</h3>
                            <a href="{{ route('calendar.index') }}" class="btn btn-light border btn-sm">Open Calendar</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="bg-light text-muted small">
                                    <tr>
                                        <th class="ps-4">Date / Time</th>
                                        <th>Staff</th>
                                        <th>Service</th>
                                        <th>Location</th>
                                        <th class="pe-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($upcoming as $appointment)
                                        <tr>
                                            <td class="ps-4">
                                                <a href="{{ route('calendar.index') }}" class="text-decoration-none">
                                                    {{ $appointment->start_time ? $appointment->start_time->format('M d, Y g:i A') : '-' }}
                                                </a>
                                            </td>
                                            <td>{{ $appointment->staff->name ?? 'N/A' }}</td>
                                            <td>{{ $appointment->service->name ?? 'N/A' }}</td>
                                            <td>{{ $appointment->location->name ?? 'N/A' }}</td>
                                            <td class="pe-4">
                                                <span class="badge {{ $appointment->status === 'completed' ? 'bg-success-subtle text-success-emphasis' : ($appointment->status === 'cancelled' ? 'bg-danger-subtle text-danger-emphasis' : ($appointment->status === 'pending' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-primary-subtle text-primary-emphasis')) }}">
                                                    {{ ucfirst($appointment->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-5">
                                                <i class='bx bx-calendar-x fs-2 d-block mb-2'></i>
                                                No upcoming appointments.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-0">
                        <div class="p-4 border-bottom">
                            <h3 class="fs-6 fw-bold mb-0">Appointment History</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="bg-light text-muted small">
                                    <tr>
                                        <th class="ps-4">Date / Time</th>
                                        <th>Staff</th>
                                        <th>Service</th>
                                        <th>Location</th>
                                        <th class="pe-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($history as $appointment)
                                        <tr>
                                            <td class="ps-4">{{ $appointment->start_time ? $appointment->start_time->format('M d, Y g:i A') : '-' }}</td>
                                            <td>{{ $appointment->staff->name ?? 'N/A' }}</td>
                                            <td>{{ $appointment->service->name ?? 'N/A' }}</td>
                                            <td>{{ $appointment->location->name ?? 'N/A' }}</td>
                                            <td class="pe-4">
                                                <span class="badge {{ $appointment->status === 'completed' ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis' }}">
                                                    {{ ucfirst($appointment->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-5">
                                                <i class='bx bx-history fs-2 d-block mb-2'></i>
                                                No completed or cancelled appointments yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @include('partials.pagination', ['paginator' => $history])
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-0">
                        <div class="p-4 border-bottom">
                            <h3 class="fs-6 fw-bold mb-0">Invoice History</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="bg-light text-muted small">
                                    <tr>
                                        <th class="ps-4">Invoice</th>
                                        <th>Issued</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-end">Paid</th>
                                        <th class="text-end">Balance</th>
                                        <th class="pe-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoices as $invoice)
                                        <tr>
                                            <td class="ps-4">
                                                <a href="{{ route('invoices.show', $invoice->id) }}" class="text-decoration-none fw-semibold">
                                                    {{ $invoice->invoice_number }}
                                                </a>
                                            </td>
                                            <td>{{ $invoice->issued_date ? $invoice->issued_date->format('M d, Y') : '-' }}</td>
                                            <td class="text-end">${{ number_format((float) $invoice->total_amount, 2) }}</td>
                                            <td class="text-end">${{ number_format((float) $invoice->paid_amount, 2) }}</td>
                                            <td class="text-end {{ max(0, (float) $invoice->total_amount - (float) $invoice->paid_amount) > 0 ? 'text-danger fw-semibold' : '' }}">
                                                ${{ number_format(max(0, (float) $invoice->total_amount - (float) $invoice->paid_amount), 2) }}
                                            </td>
                                            <td class="pe-4">
                                                <span class="badge {{ $invoice->status === 'paid' ? 'bg-success-subtle text-success-emphasis' : ($invoice->status === 'partially_paid' ? 'bg-info-subtle text-info-emphasis' : ($invoice->status === 'void' ? 'bg-secondary-subtle text-secondary-emphasis' : 'bg-danger-subtle text-danger-emphasis')) }}">
                                                    {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-5">
                                                <i class='bx bx-receipt fs-2 d-block mb-2'></i>
                                                No invoices for this client.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @include('partials.pagination', ['paginator' => $invoices])
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-0">
                        <div class="p-4 border-bottom">
                            <h3 class="fs-6 fw-bold mb-0">Payment History</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="bg-light text-muted small">
                                    <tr>
                                        <th class="ps-4">Date</th>
                                        <th class="text-end">Amount</th>
                                        <th>Method</th>
                                        <th>Transaction ID</th>
                                        <th class="pe-4">Invoice</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($payments as $payment)
                                        <tr>
                                            <td class="ps-4">{{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : '-' }}</td>
                                            <td class="text-end fw-semibold">${{ number_format((float) $payment->amount, 2) }}</td>
                                            <td><span class="badge bg-light text-dark text-capitalize">{{ $payment->payment_method }}</span></td>
                                            <td>{{ $payment->transaction_id ?: '-' }}</td>
                                            <td class="pe-4">
                                                @if($payment->invoice)
                                                    <a href="{{ route('invoices.show', $payment->invoice->id) }}" class="text-decoration-none">{{ $payment->invoice->invoice_number }}</a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-5">
                                                <i class='bx bx-credit-card fs-2 d-block mb-2'></i>
                                                No payments recorded.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @include('partials.pagination', ['paginator' => $payments])
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-0">
                        <div class="p-4 border-bottom">
                            <h3 class="fs-6 fw-bold mb-0">Form Records</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="bg-light text-muted small">
                                    <tr>
                                        <th class="ps-4">Form</th>
                                        <th class="pe-4">Submitted</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($formRecords as $record)
                                        <tr>
                                            <td class="ps-4">
                                                <a href="{{ route('form-records.show', $record->id) }}" class="text-decoration-none fw-semibold">
                                                    {{ $record->form->name ?? 'Unnamed form' }}
                                                </a>
                                            </td>
                                            <td class="pe-4">{{ $record->submitted_at ? $record->submitted_at->format('M d, Y g:i A') : '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center text-muted py-5">
                                                <i class='bx bx-file fs-2 d-block mb-2'></i>
                                                No form submissions for this client.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
