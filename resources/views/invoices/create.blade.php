@extends('layouts.app')

@section('title', 'Create Invoice')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <div>
                <h2 class="fs-4 m-0 fw-bold">Create Invoice</h2>
                <div class="text-muted small">Generate a clinic billing invoice for a patient or appointment.</div>
            </div>
            <a href="{{ route('invoices.index') }}" class="btn btn-white border btn-sm text-muted">Back to invoices</a>
        </div>
    </nav>

    <div class="container-fluid px-4 pt-4">
        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="kpi-icon"><i class='bx bx-receipt'></i></div>
                            <div>
                                <h3 class="fs-5 fw-bold mb-1">Invoice Details</h3>
                                <p class="text-muted mb-0 small">Select an appointment to auto-fill patient, staff, service, and amount where available.</p>
                            </div>
                        </div>

                        <form action="{{ route('invoices.store') }}" method="POST" id="invoice-create-form">
                            @csrf
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="appointment_id" class="form-label">Appointment</label>
                                    <select id="appointment_id" name="appointment_id" class="form-select @error('appointment_id') is-invalid @enderror">
                                        <option value="">No appointment link</option>
                                        @foreach($appointments as $appointment)
                                            <option value="{{ $appointment->id }}"
                                                data-client-id="{{ $appointment->client_id }}"
                                                data-staff-id="{{ $appointment->staff_id }}"
                                                data-service-name="{{ $appointment->service->name ?? 'Service' }}"
                                                data-service-price="{{ $appointment->service->price ?? '' }}"
                                                {{ old('appointment_id') == $appointment->id ? 'selected' : '' }}>
                                                {{ optional($appointment->start_time)->format('M j, Y g:i A') }}
                                                - {{ $appointment->client->name ?? 'Client' }}
                                                - {{ $appointment->service->name ?? 'Service' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('appointment_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <div class="form-text">Appointments that already have invoices are hidden.</div>
                                </div>

                                <div class="col-md-6">
                                    <label for="client_id" class="form-label">Patient / Client <span class="required-mark">*</span></label>
                                    <select id="client_id" name="client_id" class="form-select @error('client_id') is-invalid @enderror" required>
                                        <option value="">Select patient</option>
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                                {{ $client->name }}{{ $client->phone ? ' - ' . $client->phone : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="staff_id" class="form-label">Practitioner Name <span class="required-mark">*</span></label>
                                    <select id="staff_id" name="staff_id" class="form-select @error('staff_id') is-invalid @enderror" required>
                                        <option value="">Select staff</option>
                                        @foreach($staff as $member)
                                            <option value="{{ $member->id }}" {{ old('staff_id') == $member->id ? 'selected' : '' }}>
                                                {{ $member->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('staff_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="total_amount" class="form-label">Total Amount <span class="required-mark">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">{{ $currency }}</span>
                                        <input type="number" step="0.01" min="0.01" id="total_amount" name="total_amount"
                                            value="{{ old('total_amount') }}"
                                            class="form-control @error('total_amount') is-invalid @enderror" required>
                                        @error('total_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="status" class="form-label">Status</label>
                                    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                                        @foreach(['outstanding', 'partially_paid', 'paid', 'void'] as $status)
                                            <option value="{{ $status }}" {{ old('status', 'outstanding') === $status ? 'selected' : '' }}>
                                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="issued_date" class="form-label">Issued Date <span class="required-mark">*</span></label>
                                    <input type="date" id="issued_date" name="issued_date" value="{{ old('issued_date', now()->toDateString()) }}"
                                        class="form-control @error('issued_date') is-invalid @enderror" required>
                                    @error('issued_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="due_date" class="form-label">Due Date</label>
                                    <input type="date" id="due_date" name="due_date" value="{{ old('due_date') }}"
                                        class="form-control @error('due_date') is-invalid @enderror">
                                    @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="{{ route('invoices.index') }}" class="btn btn-light">Cancel</a>
                                <button type="submit" class="btn btn-primary px-4" data-loading-text="Creating...">Create Invoice</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="text-muted small text-uppercase fw-bold mb-2">Next invoice number</div>
                        <div class="fs-4 fw-bold text-primary mb-3">{{ $nextInvoiceNumber }}</div>
                        <div class="alert app-alert app-alert-info mb-0">
                            <i class='bx bx-info-circle' aria-hidden="true"></i>
                            <div>Invoice numbers are generated automatically using the configured invoice prefix.</div>
                        </div>
                    </div>
                </div>
                <div class="card shadow-sm border-0 mt-3">
                    <div class="card-body p-4">
                        <div class="text-muted small text-uppercase fw-bold mb-2">Selected service</div>
                        <div id="selected-service-name" class="fw-bold">No appointment selected</div>
                        <div class="text-muted small mt-2">You can still create a manual invoice without linking an appointment.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const appointment = document.getElementById('appointment_id');
            const client = document.getElementById('client_id');
            const staff = document.getElementById('staff_id');
            const total = document.getElementById('total_amount');
            const serviceName = document.getElementById('selected-service-name');

            function applyAppointment() {
                const selected = appointment?.selectedOptions?.[0];
                if (!selected || !selected.value) {
                    if (serviceName) serviceName.textContent = 'No appointment selected';
                    return;
                }

                if (selected.dataset.clientId) client.value = selected.dataset.clientId;
                if (selected.dataset.staffId) staff.value = selected.dataset.staffId;
                if (selected.dataset.servicePrice) total.value = Number(selected.dataset.servicePrice).toFixed(2);
                if (serviceName) serviceName.textContent = selected.dataset.serviceName || 'Service';
            }

            appointment?.addEventListener('change', applyAppointment);
            applyAppointment();
        });
    </script>
@endpush
