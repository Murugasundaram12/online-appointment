@extends('layouts.app')

@section('title', 'Edit Payroll')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">Edit Payroll</h1>
                <p class="text-muted mb-0">{{ $payroll->payroll_number }} for {{ optional($payroll->staff)->name ?? 'Not available' }}</p>
            </div>
            <a href="{{ route('payroll.index') }}" class="btn btn-light border">Back</a>
        </div>

        <form action="{{ route('payroll.update', $payroll->id) }}" method="POST" id="payrollForm">
            @csrf
            @method('PUT')
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h2 class="fs-5 fw-bold mb-3">Payroll Details</h2>
                            <div class="alert alert-info">Staff Member: <strong>{{ optional($payroll->staff)->name ?? 'Not available' }}</strong></div>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="period_start" class="form-label">Period Start <span class="required-mark">*</span></label>
                                    <input type="date" id="period_start" name="period_start" value="{{ old('period_start', $payroll->period_start->format('Y-m-d')) }}" class="form-control @error('period_start') is-invalid @enderror" required>
                                    @error('period_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label for="period_end" class="form-label">Period End <span class="required-mark">*</span></label>
                                    <input type="date" id="period_end" name="period_end" value="{{ old('period_end', $payroll->period_end->format('Y-m-d')) }}" class="form-control @error('period_end') is-invalid @enderror" required>
                                    @error('period_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label for="total_hours" class="form-label">Worked Hours</label>
                                    <input type="number" step="0.5" min="0" id="total_hours" name="total_hours" value="{{ old('total_hours', $payroll->total_hours ?? 0) }}" class="form-control @error('total_hours') is-invalid @enderror">
                                    @error('total_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label for="payment_date" class="form-label">Payment Date <span class="required-mark" id="payment_date_mark">*</span></label>
                                    <input type="date" id="payment_date" name="payment_date" value="{{ old('payment_date', optional($payroll->payment_date)->format('Y-m-d')) }}" class="form-control @error('payment_date') is-invalid @enderror">
                                    @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="salary_amount" class="form-label">Basic Salary <span class="required-mark">*</span></label>
                                    <input type="number" step="0.01" min="0" id="salary_amount" name="salary_amount" value="{{ old('salary_amount', $payroll->salary_amount) }}" class="form-control payroll-money @error('salary_amount') is-invalid @enderror" required>
                                    @error('salary_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="commission_amount" class="form-label">Commission</label>
                                    <input type="number" step="0.01" min="0" id="commission_amount" name="commission_amount" value="{{ old('commission_amount', $payroll->commission_amount ?? 0) }}" class="form-control payroll-money @error('commission_amount') is-invalid @enderror">
                                    @error('commission_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="bonus" class="form-label">Bonus</label>
                                    <input type="number" step="0.01" min="0" id="bonus" name="bonus" value="{{ old('bonus', $payroll->bonus ?? 0) }}" class="form-control payroll-money @error('bonus') is-invalid @enderror">
                                    @error('bonus')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="deductions" class="form-label">Deductions</label>
                                    <input type="number" step="0.01" min="0" id="deductions" name="deductions" value="{{ old('deductions', $payroll->deductions ?? 0) }}" class="form-control payroll-money @error('deductions') is-invalid @enderror">
                                    @error('deductions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="payment_type" class="form-label">Payment Method <span class="required-mark">*</span></label>
                                    <select id="payment_type" name="payment_type" class="form-select @error('payment_type') is-invalid @enderror" required>
                                        @foreach(['cash' => 'Cash', 'check' => 'Check', 'transfer' => 'Bank Transfer', 'mobile_money' => 'Mobile Money'] as $value => $label)
                                            <option value="{{ $value }}" {{ old('payment_type', $payroll->payment_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('payment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="status" class="form-label">Status <span class="required-mark">*</span></label>
                                    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                                        <option value="pending" {{ old('status', $payroll->status) === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="processing" {{ old('status', $payroll->status) === 'processing' ? 'selected' : '' }}>Processing</option>
                                        <option value="completed" {{ old('status', $payroll->status) === 'completed' ? 'selected' : '' }}>Paid</option>
                                        <option value="paid" {{ old('status', $payroll->status) === 'paid' ? 'selected' : '' }}>Paid (Direct)</option>
                                        <option value="cancelled" {{ old('status', $payroll->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    </select>
                                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea id="notes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $payroll->notes) }}</textarea>
                                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm position-sticky" style="top: 1rem;">
                        <div class="card-body p-4">
                            <h2 class="fs-5 fw-bold mb-3">Total Payout</h2>
                            <div class="d-flex justify-content-between mb-2"><span>Salary</span><strong id="display-salary">0.00</strong></div>
                            <div class="d-flex justify-content-between mb-2"><span>Commission</span><strong id="display-commission">0.00</strong></div>
                            <div class="d-flex justify-content-between mb-2"><span>Bonus</span><strong id="display-bonus">0.00</strong></div>
                            <div class="d-flex justify-content-between mb-3"><span>Deductions</span><strong id="display-deductions">0.00</strong></div>
                            <div class="border-top pt-3 d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Net Total</span>
                                <span class="fs-4 fw-bold text-primary" id="display-total">0.00</span>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mt-4" data-loading-text="Updating...">Update Payroll</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        const moneyIds = ['salary_amount', 'commission_amount', 'bonus', 'deductions'];
        function valueOf(id) { return parseFloat(document.getElementById(id).value) || 0; }
        function calculateTotal() {
            const salary = valueOf('salary_amount');
            const commission = valueOf('commission_amount');
            const bonus = valueOf('bonus');
            const deductions = valueOf('deductions');
            document.getElementById('display-salary').textContent = salary.toFixed(2);
            document.getElementById('display-commission').textContent = commission.toFixed(2);
            document.getElementById('display-bonus').textContent = bonus.toFixed(2);
            document.getElementById('display-deductions').textContent = deductions.toFixed(2);
            document.getElementById('display-total').textContent = Math.max(0, salary + commission + bonus - deductions).toFixed(2);
        }
        moneyIds.forEach(id => document.getElementById(id).addEventListener('input', calculateTotal));
        const statusSelect = document.getElementById('status');
        const paymentDate = document.getElementById('payment_date');
        const paymentDateMark = document.getElementById('payment_date_mark');
        function togglePaymentDateRequired() {
            const needsDate = ['completed', 'paid'].includes(statusSelect.value);
            if (paymentDateMark) paymentDateMark.style.display = needsDate ? '' : 'none';
            if (paymentDate) {
                if (needsDate) {
                    paymentDate.setAttribute('required', 'required');
                } else {
                    paymentDate.removeAttribute('required');
                }
            }
        }
        if (statusSelect) {
            statusSelect.addEventListener('change', togglePaymentDateRequired);
            togglePaymentDateRequired();
        }
        calculateTotal();
    </script>
@endsection
