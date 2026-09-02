@extends('layouts.app')

@section('title', 'Staff Details')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <h2 class="fs-4 m-0 fw-bold">{{ $staff->name }}</h2>
            <div class="d-flex gap-2">
                <a href="{{ route('staff.edit', $staff->id) }}" class="btn btn-primary btn-sm">Edit Staff</a>
                <a href="{{ route('staff.index') }}" class="btn btn-white border btn-sm text-muted">Back to List</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 pt-4">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                                style="width: 56px; height: 56px; background: {{ $staff->color ?: '#4f46e5' }};">
                                {{ strtoupper(substr($staff->name, 0, 2)) }}
                            </div>
                            <div>
                                <h3 class="fs-5 mb-1">{{ $staff->name }}</h3>
                                <span class="badge {{ $staff->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $staff->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>

                        <dl class="row small mb-0">
                            <dt class="col-5 text-muted">Email</dt>
                            <dd class="col-7">{{ $staff->email }}</dd>
                            <dt class="col-5 text-muted">Phone</dt>
                            <dd class="col-7">{{ $staff->phone ?: '-' }}</dd>
                            <dt class="col-5 text-muted">Location</dt>
                            <dd class="col-7">{{ $staff->location->name ?? '-' }}</dd>
                            <dt class="col-5 text-muted">Access</dt>
                            <dd class="col-7">{{ $staff->access_level ? ucfirst(str_replace('_', ' ', $staff->access_level)) : '-' }}</dd>
                            <dt class="col-5 text-muted">Registration No.</dt>
                            <dd class="col-7">{{ $staff->registration_number ?: '-' }}</dd>
                            <dt class="col-5 text-muted">Designation</dt>
                            <dd class="col-7">{{ $staff->designation ?: '-' }}</dd>
                            <dt class="col-5 text-muted">Category</dt>
                            <dd class="col-7">{{ $staff->category ?: '-' }}</dd>
                            <dt class="col-5 text-muted">Salary</dt>
                            <dd class="col-7">{{ number_format((float) ($staff->salary ?? 0), 2) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <h3 class="fs-6 fw-bold mb-3">Profile Notes</h3>
                        <p class="text-muted mb-0">{{ $staff->bio ?: 'No notes recorded.' }}</p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="text-muted small">Appointments</div>
                                <div class="fs-4 fw-bold">{{ $staff->appointments_count }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="text-muted small">Schedules</div>
                                <div class="fs-4 fw-bold">{{ $staff->schedules_count }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="text-muted small">Payroll Records</div>
                                <div class="fs-4 fw-bold">{{ $staff->payrolls_count }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="text-muted small">Pending Payroll</div>
                                <div class="fs-4 fw-bold">${{ number_format($pendingPayroll ?? 0, 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-body p-4">
                        <h3 class="fs-6 fw-bold mb-3">Payroll Snapshot</h3>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="text-muted small">Current Salary</div>
                                <div class="fw-bold">${{ number_format((float) ($staff->salary ?? 0), 2) }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Last Payroll</div>
                                <div class="fw-bold">{{ $lastPayroll ? $lastPayroll->payroll_number . ' - $' . number_format($lastPayroll->total_payout, 2) : 'Not available' }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Total Payrolls</div>
                                <div class="fw-bold">{{ $staff->payrolls_count }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
