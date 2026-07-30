@extends('layouts.app')

@section('title', 'Payroll')

@push('styles')
    <style>
        .payroll-card {
            background: #fff;
            border-radius: 8px;
            border: 1px solid #eef0f7;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #3f4254;
            margin-bottom: 0.5rem;
        }

        .section-subtitle {
            font-size: 0.85rem;
            color: #7e8299;
            margin-bottom: 1.5rem;
        }

        .btn-save {
            background-color: #f5f8fa;
            color: #a1a5b7;
            border: none;
            padding: 0.4rem 1.5rem;
            font-weight: 500;
            border-radius: 4px;
        }

        .badge-upgrade {
            background-color: #fff1f2;
            color: #f64e60;
            font-size: 0.75rem;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-weight: 500;
        }

        .form-check-input:checked {
            background-color: #10b981;
            border-color: #10b981;
        }

        .reports-banner {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .illustration-box {
            width: 80px;
            height: 80px;
        }

        .staff-table th {
            font-weight: 600;
            color: #7e8299;
            font-size: 0.85rem;
            background-color: #f9fafb;
            border-top: none;
            padding: 1rem;
        }

        .staff-table td {
            padding: 1rem;
            vertical-align: middle;
            font-size: 0.9rem;
            color: #3f4254;
        }

        .avatar-box {
            width: 32px;
            height: 32px;
            background-color: #fff1f2;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            margin-right: 10px;
        }
    </style>
@endpush

@section('content')
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-transparent py-4 px-4">
            <div class="d-flex align-items-center">
                <h2 class="fs-4 m-0 fw-bold">Payroll</h2>
            </div>
        </nav>

        <div class="container-fluid px-4">
            <!-- General Settings Card -->
            <div class="payroll-card">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h3 class="section-title">General settings</h3>
                        <p class="section-subtitle">Staff commissions general settings.</p>
                    </div>
                    <button class="btn btn-save">Save</button>
                </div>

                <div class="d-flex justify-content-between align-items-center py-3 border-top">
                    <div>
                        <span class="fw-500 me-2">Run payroll reports automatically</span>
                        <span class="badge-upgrade">Upgrade plan</span>
                        <p class="small text-muted mb-0 mt-1">Choose a frequency for payroll reports or create them manually
                            on
                            the report page.</p>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="autoReportsToggle">
                    </div>
                </div>
            </div>

            <!-- Payroll Reports Banner -->
            <div class="payroll-card">
                <div class="reports-banner">
                    <div>
                        <h3 class="section-title">Payroll Reports</h3>
                        <p class="section-subtitle mb-3">You can view and download your payrolls on the reports page.</p>
                        <a href="{{ route('payroll.report') }}" class="btn btn-outline-primary btn-sm px-4">Go to
                            reports</a>
                    </div>
                    <div class="illustration-box">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="4" y="2" width="16" height="20" rx="2" fill="#E5E7EB" />
                            <rect x="6" y="6" width="12" height="2" rx="1" fill="#3B82F6" />
                            <rect x="6" y="10" width="12" height="2" rx="1" fill="#10B981" />
                            <rect x="6" y="14" width="8" height="2" rx="1" fill="#F59E0B" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Staff Payroll Settings -->
            <div class="payroll-card pb-0">
                <h3 class="section-title mb-4">Staff payroll settings</h3>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="input-group" style="max-width: 300px;">
                        <span class="input-group-text bg-light border-0"><i class='bx bx-search text-muted'></i></span>
                        <input type="text" class="form-control bg-light border-0" placeholder="Search">
                    </div>
                    <button class="btn btn-light"><i class='bx bx-slider-alt'></i></button>
                </div>

                <div class="table-responsive">
                    <table class="table staff-table mb-0">
                        <thead>
                            <tr>
                                <th>Staff Name <i class='bx bx-up-arrow-alt'></i></th>
                                <th>Booking Commission</th>
                                <th>Service Commission</th>
                                <th>Product Commission</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staff as $member)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-box">
                                                <i class='bx bx-user text-danger'></i>
                                            </div>
                                            <span class="text-dark fw-500">{{ $member->name }}</span>
                                        </div>
                                    </td>
                                    <td class="text-muted">No Commission</td>
                                    <td class="text-muted">No Commission</td>
                                    <td class="text-muted">No Commission</td>
                                    <td class="text-end text-muted">
                                        <i class='bx bx-show fs-5 pointer'></i>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No staff found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
