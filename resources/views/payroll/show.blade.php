@extends('layouts.app')

@section('title', 'Payroll Record')

@section('styles')
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
            margin-bottom: 1.5rem;
            margin-top: 2rem;
        }

        .section-title:first-of-type {
            margin-top: 0;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .detail-item {
            padding: 1rem;
            background: #f9fafb;
            border-radius: 6px;
            border-left: 3px solid #eef0f7;
        }

        .detail-label {
            color: #7e8299;
            font-size: 0.85rem;
            margin-bottom: 0.3rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            color: #3f4254;
            font-weight: 600;
            font-size: 1rem;
        }

        .summary-box {
            background: linear-gradient(135deg, #f9fafb 0%, #fff 100%);
            border-radius: 8px;
            padding: 2rem;
            margin-top: 2rem;
            border: 1px solid #eef0f7;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eef0f7;
        }

        .summary-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .summary-label {
            color: #7e8299;
            font-size: 0.95rem;
        }

        .summary-value {
            font-weight: 600;
            color: #3f4254;
        }

        .total-payout {
            font-size: 1.5rem;
            color: #3699ff;
            font-weight: 700;
        }

        .badge-status {
            padding: 0.4rem 1rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-processing {
            background-color: #cfe2ff;
            color: #084298;
        }

        .badge-completed {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .badge-cancelled {
            background-color: #f8d7da;
            color: #842029;
        }

        .btn-edit {
            background-color: #3699ff;
            color: white;
        }

        .btn-edit:hover {
            background-color: #2681dd;
            color: white;
        }

        .btn-back {
            background-color: #f5f8fa;
            color: #3f4254;
        }

        .btn-back:hover {
            background-color: #e9ecf0;
            color: #3f4254;
        }

        .notes-box {
            background: #f9fafb;
            border-left: 3px solid #3699ff;
            padding: 1rem;
            border-radius: 6px;
            margin-top: 1.5rem;
        }

        .notes-label {
            color: #7e8299;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }

        .notes-content {
            color: #3f4254;
            font-size: 0.95rem;
            line-height: 1.5;
        }
    </style>
@endsection

@section('content')
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-transparent py-4 px-4">
            <div class="d-flex align-items-center justify-content-between w-100">
                <h2 class="fs-4 m-0 fw-bold">Payroll Details</h2>
                <div class="d-flex gap-2">
                    <a href="{{ route('payroll.edit', $payroll->id) }}" class="btn btn-edit"><i class='bx bx-edit'></i>
                        Edit</a>
                    <a href="{{ route('payroll.index') }}" class="btn btn-back"><i class='bx bx-arrow-back'></i> Back</a>
                </div>
            </div>
        </nav>

        <div class="container-fluid px-4">
            <div class="payroll-card">
                {{-- Staff and Period Info --}}
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Staff Member</div>
                        <div class="detail-value">{{ $payroll->staff->name ?? 'N/A' }}</div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Period Start</div>
                        <div class="detail-value">{{ $payroll->period_start->format('M d, Y') }}</div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Period End</div>
                        <div class="detail-value">{{ $payroll->period_end->format('M d, Y') }}</div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Status</div>
                        <div class="detail-value">
                            <span class="badge-status badge-{{ $payroll->status }}">{{ ucfirst($payroll->status) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Salary Details --}}
                <h5 class="section-title">Salary Breakdown</h5>
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Base Salary</div>
                        <div class="detail-value">{{ number_format($payroll->salary_amount, 2) }}</div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Commission</div>
                        <div class="detail-value">{{ number_format($payroll->commission_amount ?? 0, 2) }}</div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Bonus</div>
                        <div class="detail-value">{{ number_format($payroll->bonus ?? 0, 2) }}</div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Deductions</div>
                        <div class="detail-value">{{ number_format($payroll->deductions ?? 0, 2) }}</div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Total Hours</div>
                        <div class="detail-value">{{ $payroll->total_hours ?? 0 }} hrs</div>
                    </div>
                </div>

                {{-- Payment Details --}}
                <h5 class="section-title">Payment Information</h5>
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Payment Date</div>
                        <div class="detail-value">{{ $payroll->payment_date->format('M d, Y') }}</div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Payment Type</div>
                        <div class="detail-value">{{ ucfirst(str_replace('_', ' ', $payroll->payment_type)) }}</div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Total Payout</div>
                        <div class="detail-value" style="color: #3699ff; font-size: 1.2rem;">
                            {{ number_format($payroll->total_payout, 2) }}</div>
                    </div>
                </div>

                {{-- Summary Box --}}
                <div class="summary-box">
                    <h6 class="mb-3" style="color: #3f4254; font-weight: 600; font-size: 1rem;">Payroll Summary</h6>
                    <div class="summary-row">
                        <span class="summary-label">Base Salary</span>
                        <span class="summary-value">{{ number_format($payroll->salary_amount, 2) }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Commission</span>
                        <span class="summary-value">+ {{ number_format($payroll->commission_amount ?? 0, 2) }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Bonus</span>
                        <span class="summary-value">+ {{ number_format($payroll->bonus ?? 0, 2) }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Deductions</span>
                        <span class="summary-value">- {{ number_format($payroll->deductions ?? 0, 2) }}</span>
                    </div>
                    <div class="summary-row"
                        style="border-bottom: 2px solid #eef0f7; padding-bottom: 1rem; margin-bottom: 1rem; padding-top: 1rem;">
                        <span style="font-size: 1.1rem; font-weight: 600; color: #3f4254;">Total Payout</span>
                        <span class="total-payout">{{ number_format($payroll->total_payout, 2) }}</span>
                    </div>
                </div>

                {{-- Notes --}}
                @if($payroll->notes)
                    <div class="notes-box">
                        <div class="notes-label">Notes</div>
                        <div class="notes-content">{{ $payroll->notes }}</div>
                    </div>
                @endif

                {{-- Metadata --}}
                <div class="details-grid" style="margin-top: 2rem;">
                    <div class="detail-item">
                        <div class="detail-label">Created</div>
                        <div class="detail-value small">{{ $payroll->created_at->format('M d, Y at h:i A') }}</div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Last Updated</div>
                        <div class="detail-value small">{{ $payroll->updated_at->format('M d, Y at h:i A') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
