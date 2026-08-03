@extends('layouts.app')

@section('title', 'Reports')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">Reports</h1>
                <p class="text-muted mb-0">Business analytics and exportable summaries.</p>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">Appointments Today</div>
                        <div class="fs-4 fw-bold">{{ $stats['appointments_today'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">Appointments This Month</div>
                        <div class="fs-4 fw-bold">{{ $stats['appointments_month'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">Billed This Month</div>
                        <div class="fs-4 fw-bold text-primary">${{ number_format($stats['billed_month'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">Collected This Month</div>
                        <div class="fs-4 fw-bold text-success">${{ number_format($stats['collected_month'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            @foreach($reports as $key => $report)
                <div class="col-md-4">
                    <a href="{{ route('reports.' . $key) }}" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body d-flex gap-3 align-items-start">
                                <div class="rounded d-flex align-items-center justify-content-center text-white" style="width: 48px; height: 48px; background: #4f46e5;">
                                    <i class='bx {{ $report['icon'] }} fs-4'></i>
                                </div>
                                <div>
                                    <h2 class="fs-6 fw-bold mb-1">{{ $report['title'] }}</h2>
                                    <p class="text-muted small mb-0">{{ $report['description'] }}</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
@endsection
