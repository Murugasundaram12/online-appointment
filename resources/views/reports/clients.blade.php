@extends('layouts.app')

@section('title', 'Clients Report')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">Clients Report</h1>
                <p class="text-muted mb-0">Client growth, engagement and spend.</p>
            </div>
            <a href="{{ route('reports.index') }}" class="btn btn-light border">All Reports</a>
        </div>

        @include('reports._date_filter', [
            'exportType' => 'clients',
            'textFields' => ['search' => 'Search (name, email, phone)'],
            'extraFields' => [
                'vip' => ['label' => 'Client type', 'items' => ['0' => 'Regular', '1' => 'VIP']],
            ],
        ])

        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">New in Period</div><div class="fs-4 fw-bold text-primary">{{ $summary['new'] }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Matching Clients</div><div class="fs-4 fw-bold">{{ $summary['total'] }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">VIP</div><div class="fs-4 fw-bold text-warning">{{ $summary['vip'] }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Top Spender (this page)</div><div class="fs-4 fw-bold text-success">${{ number_format($summary['top_spend'], 2) }}</div></div></div></div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light text-muted small">
                        <tr>
                            <th class="ps-4">Client</th>
                            <th>Contact</th>
                            <th>Client Since</th>
                            <th class="text-end">Appointments</th>
                            <th class="text-end">Invoices</th>
                            <th class="text-end pe-4">Total Spent</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($clients as $client)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('clients.show', $client->id) }}" class="text-decoration-none fw-semibold">
                                    {{ $client->name }}
                                </a>
                                @if($client->is_vip)
                                    <span class="badge bg-warning text-dark ms-1"><i class='bx bx-star'></i></span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $client->email ?: '-' }}</div>
                                <div class="text-muted small">{{ $client->phone ?: '' }}</div>
                            </td>
                            <td>{{ $client->client_since ? $client->client_since->format('M d, Y') : '-' }}</td>
                            <td class="text-end">{{ $client->appointments_count }}</td>
                            <td class="text-end">{{ $client->invoices_count }}</td>
                            <td class="text-end pe-4 fw-semibold">${{ number_format((float) ($client->total_spent ?? 0), 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No clients match the current filters.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.pagination', ['paginator' => $clients])
        </div>
    </div>
@endsection
