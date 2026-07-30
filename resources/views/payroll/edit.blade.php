@extends('layouts.app')

@section('title', 'Edit Payroll')

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
        }

        .form-label {
            font-weight: 500;
            color: #3f4254;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control,
        .form-select {
            border-color: #eef0f7;
            padding: 0.6rem 0.75rem;
            font-size: 0.9rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #3699ff;
            box-shadow: 0 0 0 0.2rem rgba(54, 153, 255, 0.15);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .btn-submit {
            background-color: #3699ff;
            color: white;
            border: none;
            padding: 0.6rem 2rem;
            font-weight: 500;
            border-radius: 4px;
        }

        .btn-submit:hover {
            background-color: #2681dd;
            color: white;
        }

        .btn-cancel {
            background-color: #f5f8fa;
            color: #3f4254;
            border: none;
            padding: 0.6rem 2rem;
            font-weight: 500;
            border-radius: 4px;
        }

        .btn-cancel:hover {
            background-color: #e9ecf0;
            color: #3f4254;
        }

        .summary-box {
            background: #f9fafb;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
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
            font-size: 0.9rem;
        }

        .summary-value {
            font-weight: 600;
            color: #3f4254;
        }

        .total-payout {
            font-size: 1.3rem;
            color: #3699ff;
            font-weight: 700;
        }

        .info-box {
            background: #f1f6ff;
            border-left: 4px solid #3699ff;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: #3f4254;
        }
    </style>
@endsection

@section('content')
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-transparent py-4 px-4">
            <div class="d-flex align-items-center">
                <h2 class="fs-4 m-0 fw-bold">Edit Payroll Record</h2>
            </div>
        </nav>

        <div class="container-fluid px-4">
            {{-- Error Messages --}}
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h5>Validation Errors</h5>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="payroll-card">
                <div class="info-box">
                    <strong>Staff Member:</strong> {{ $payroll->staff->name ?? 'N/A' }}<br>
                    <strong>Period:</strong> {{ $payroll->period_start->format('M d, Y') }} –
                    {{ $payroll->period_end->format('M d, Y') }}
                </div>

                <form action="{{ route('payroll.update', $payroll->id) }}" method="POST" id="payrollForm">
                    @csrf
                    @method('PUT')

                    {{-- Period --}}
                    <h5 class="section-title mt-4">Period</h5>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="period_start" class="form-label">Start Date <span
                                    class="text-danger">*</span></label>
                            <input type="date" id="period_start" name="period_start"
                                class="form-control @error('period_start') is-invalid @enderror"
                                value="{{ old('period_start', $payroll->period_start->format('Y-m-d')) }}" required>
                            @error('period_start')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="period_end" class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" id="period_end" name="period_end"
                                class="form-control @error('period_end') is-invalid @enderror"
                                value="{{ old('period_end', $payroll->period_end->format('Y-m-d')) }}" required>
                            @error('period_end')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="total_hours" class="form-label">Total Hours</label>
                            <input type="number" id="total_hours" name="total_hours"
                                class="form-control @error('total_hours') is-invalid @enderror"
                                value="{{ old('total_hours', $payroll->total_hours ?? 0) }}" step="0.5" min="0">
                            @error('total_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Salary Details --}}
                    <h5 class="section-title mt-4">Salary Details</h5>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="salary_amount" class="form-label">Base Salary <span
                                    class="text-danger">*</span></label>
                            <input type="number" id="salary_amount" name="salary_amount"
                                class="form-control @error('salary_amount') is-invalid @enderror"
                                value="{{ old('salary_amount', $payroll->salary_amount) }}" step="0.01" min="0" required
                                onchange="calculateTotal()">
                            @error('salary_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="commission_amount" class="form-label">Commission</label>
                            <input type="number" id="commission_amount" name="commission_amount"
                                class="form-control @error('commission_amount') is-invalid @enderror"
                                value="{{ old('commission_amount', $payroll->commission_amount ?? 0) }}" step="0.01" min="0"
                                onchange="calculateTotal()">
                            @error('commission_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="bonus" class="form-label">Bonus</label>
                            <input type="number" id="bonus" name="bonus"
                                class="form-control @error('bonus') is-invalid @enderror"
                                value="{{ old('bonus', $payroll->bonus ?? 0) }}" step="0.01" min="0"
                                onchange="calculateTotal()">
                            @error('bonus')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="deductions" class="form-label">Deductions</label>
                            <input type="number" id="deductions" name="deductions"
                                class="form-control @error('deductions') is-invalid @enderror"
                                value="{{ old('deductions', $payroll->deductions ?? 0) }}" step="0.01" min="0"
                                onchange="calculateTotal()">
                            @error('deductions')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Payment Details --}}
                    <h5 class="section-title mt-4">Payment Details</h5>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="payment_date" class="form-label">Payment Date <span
                                    class="text-danger">*</span></label>
                            <input type="date" id="payment_date" name="payment_date"
                                class="form-control @error('payment_date') is-invalid @enderror"
                                value="{{ old('payment_date', $payroll->payment_date->format('Y-m-d')) }}" required>
                            @error('payment_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="payment_type" class="form-label">Payment Type <span
                                    class="text-danger">*</span></label>
                            <select id="payment_type" name="payment_type"
                                class="form-select @error('payment_type') is-invalid @enderror" required>
                                <option value="">Select payment type</option>
                                <option value="cash" {{ old('payment_type', $payroll->payment_type) == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="check" {{ old('payment_type', $payroll->payment_type) == 'check' ? 'selected' : '' }}>Check</option>
                                <option value="transfer" {{ old('payment_type', $payroll->payment_type) == 'transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="mobile_money" {{ old('payment_type', $payroll->payment_type) == 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                            </select>
                            @error('payment_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror"
                                required>
                                <option value="pending" {{ old('status', $payroll->status) == 'pending' ? 'selected' : '' }}>
                                    Pending</option>
                                <option value="processing" {{ old('status', $payroll->status) == 'processing' ? 'selected' : '' }}>Processing</option>
                                <option value="completed" {{ old('status', $payroll->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ old('status', $payroll->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="form-group mt-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea id="notes" name="notes" class="form-control @error('notes') is-invalid @enderror"
                            rows="3">{{ old('notes', $payroll->notes ?? '') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Summary Box --}}
                    <div class="summary-box">
                        <h6 class="mb-3" style="color: #3f4254; font-weight: 600;">Payroll Summary</h6>
                        <div class="summary-row">
                            <span class="summary-label">Base Salary</span>
                            <span class="summary-value"
                                id="display-salary">{{ number_format($payroll->salary_amount, 2) }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Commission</span>
                            <span class="summary-value"
                                id="display-commission">{{ number_format($payroll->commission_amount ?? 0, 2) }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Bonus</span>
                            <span class="summary-value"
                                id="display-bonus">{{ number_format($payroll->bonus ?? 0, 2) }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Deductions</span>
                            <span class="summary-value"
                                id="display-deductions">{{ number_format($payroll->deductions ?? 0, 2) }}</span>
                        </div>
                        <div class="summary-row"
                            style="border-bottom: 2px solid #eef0f7; padding-bottom: 1rem; margin-bottom: 1rem;">
                            <span style="font-size: 1.1rem; font-weight: 600; color: #3f4254;">Total Payout</span>
                            <span class="total-payout"
                                id="display-total">{{ number_format($payroll->total_payout, 2) }}</span>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-submit">Update Payroll</button>
                        <a href="{{ route('payroll.index') }}" class="btn btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function calculateTotal() {
            const salary = parseFloat(document.getElementById('salary_amount').value) || 0;
            const commission = parseFloat(document.getElementById('commission_amount').value) || 0;
            const bonus = parseFloat(document.getElementById('bonus').value) || 0;
            const deductions = parseFloat(document.getElementById('deductions').value) || 0;
            const total = salary + commission + bonus - deductions;

            document.getElementById('display-salary').textContent = salary.toFixed(2);
            document.getElementById('display-commission').textContent = commission.toFixed(2);
            document.getElementById('display-bonus').textContent = bonus.toFixed(2);
            document.getElementById('display-deductions').textContent = deductions.toFixed(2);
            document.getElementById('display-total').textContent = total.toFixed(2);
        }

        // Calculate on page load
        window.addEventListener('load', calculateTotal);
    </script>
@endsection
