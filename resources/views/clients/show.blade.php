@extends('layouts.app')

@section('title', 'Client Details')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <h2 class="fs-4 m-0 fw-bold">{{ $client->name }}</h2>
            <div class="d-flex gap-2">
                <a href="{{ route('clients.edit', $client->id) }}" class="btn btn-primary btn-sm">Edit Client</a>
                <a href="{{ route('clients.index') }}" class="btn btn-white border btn-sm text-muted">Back to List</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 pt-4">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded d-flex align-items-center justify-content-center text-white fw-bold"
                                style="width: 56px; height: 56px; background: #4f46e5;">
                                {{ strtoupper(substr($client->name, 0, 2)) }}
                            </div>
                            <div>
                                <h3 class="fs-5 mb-1">{{ $client->name }}</h3>
                                @if($client->is_vip)
                                    <span class="badge bg-warning text-dark">VIP</span>
                                @endif
                            </div>
                        </div>

                        <dl class="row small mb-0">
                            <dt class="col-5 text-muted">Email</dt>
                            <dd class="col-7">{{ $client->email ?: '-' }}</dd>
                            <dt class="col-5 text-muted">Phone</dt>
                            <dd class="col-7">{{ $client->phone ?: '-' }}</dd>
                            <dt class="col-5 text-muted">City</dt>
                            <dd class="col-7">{{ $client->city ?: '-' }}</dd>
                            <dt class="col-5 text-muted">Client Since</dt>
                            <dd class="col-7">{{ $client->client_since ? $client->client_since->format('M d, Y') : '-' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <h3 class="fs-6 fw-bold mb-3">Notes</h3>
                        <p class="text-muted mb-0">{{ $client->notes ?: 'No notes recorded.' }}</p>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="text-muted small">Appointments</div>
                                <div class="fs-4 fw-bold">{{ $client->appointments_count }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="text-muted small">Invoices</div>
                                <div class="fs-4 fw-bold">{{ $client->invoices->count() }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="text-muted small">Outstanding</div>
                                <div class="fs-4 fw-bold">
                                    {{ number_format((float) $client->invoices->sum(fn ($invoice) => max(0, $invoice->total_amount - $invoice->paid_amount)), 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <div class="p-4 border-bottom">
                            <h3 class="fs-6 fw-bold mb-0">Recent Appointments</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="bg-light text-muted small">
                                    <tr>
                                        <th class="ps-4">Date</th>
                                        <th>Service</th>
                                        <th>Staff</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($client->appointments->sortByDesc('start_time')->take(5) as $appointment)
                                        <tr>
                                            <td class="ps-4">{{ $appointment->start_time ? $appointment->start_time->format('M d, Y H:i') : '-' }}</td>
                                            <td>{{ $appointment->service->name ?? '-' }}</td>
                                            <td>{{ $appointment->staff->name ?? '-' }}</td>
                                            <td><span class="badge bg-light text-dark">{{ ucfirst($appointment->status) }}</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No appointments found.</td>
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
