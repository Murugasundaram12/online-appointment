@extends('layouts.app')

@section('title', 'Payment Records')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">Payments</h1>
                <p class="text-muted mb-0">Record and track payments against invoices.</p>
            </div>
            <div>
                <button type="button" id="btn-show-add-payment" class="btn btn-primary d-inline-flex align-items-center gap-1 shadow-sm" aria-expanded="false" aria-controls="payment-form-card">
                    <i class='bx bx-plus me-1' aria-hidden="true"></i> Add Payment
                </button>
            </div>
        </div>
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row g-3 mb-4">
            @foreach([
                ['Total received', $summary['total'], 'bx-dollar'],
                ['Cash total', $summary['cash'], 'bx-money'],
                ['Card total', $summary['card'], 'bx-credit-card'],
                ['E-Transfer total', $summary['e_transfer'], 'bx-transfer'],
            ] as [$label, $amount, $icon])
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm kpi-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="text-muted small">{{ $label }}</div>
                                    <div class="fs-4 fw-bold financial">${{ number_format($amount, 2) }}</div>
                                </div>
                                <div class="kpi-icon"><i class='bx {{ $icon }}'></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card shadow-sm border-0 rounded mb-4 {{ ($errors->any() || request()->filled('invoice_id')) ? '' : 'd-none' }}" id="payment-form-card" tabindex="-1" aria-labelledby="payment-form-title">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <h5 class="fs-6 fw-bold m-0 text-dark" id="payment-form-title">
                        <i class='bx bx-credit-card me-1 text-primary'></i> Record Payment
                    </h5>
                    <button type="button" class="btn-close btn-cancel-payment-trigger" aria-label="Cancel and close form" title="Cancel"></button>
                </div>

                <div id="invoice-summary-banner" class="alert alert-light border mb-3 {{ $selectedInvoice ? '' : 'd-none' }}">
                    <div class="row g-2 align-items-center text-dark">
                        <div class="col-sm-6 col-md-3">
                            <span class="text-muted small d-block">Invoice Number:</span>
                            <strong id="summary-inv-number">{{ $selectedInvoice?->invoice_number ?: '-' }}</strong>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <span class="text-muted small d-block">Client:</span>
                            <strong id="summary-inv-client">{{ optional($selectedInvoice?->client)->name ?: '-' }}</strong>
                        </div>
                        <div class="col-sm-4 col-md-2">
                            <span class="text-muted small d-block">Invoice Total:</span>
                            <strong id="summary-inv-total">${{ number_format((float) ($selectedInvoice?->total_amount ?? 0), 2) }}</strong>
                        </div>
                        <div class="col-sm-4 col-md-2">
                            <span class="text-muted small d-block">Already Paid:</span>
                            <strong id="summary-inv-paid" class="text-success">${{ number_format((float) ($selectedInvoice?->paid_amount ?? 0), 2) }}</strong>
                        </div>
                        <div class="col-sm-4 col-md-2">
                            <span class="text-muted small d-block">Balance Due:</span>
                            <strong id="summary-inv-balance" class="text-danger">${{ number_format(max((float) ($selectedInvoice?->total_amount ?? 0) - (float) ($selectedInvoice?->paid_amount ?? 0), 0), 2) }}</strong>
                        </div>
                        <div class="col-12 mt-2 pt-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small fw-medium">Projected Payment Status:</span>
                                <span id="payment-status-badge" class="badge bg-secondary">Pending</span>
                            </div>
                            <div id="payment-validation-msg" class="small text-danger d-none fw-semibold">
                                <i class="bx bx-error-circle me-1"></i>Paid amount cannot exceed the remaining balance.
                            </div>
                        </div>
                    </div>
                </div>

                <form action="{{ route('payment-records.store') }}" method="POST" class="row g-3" id="payment-record-form">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">Invoice <span class="required-mark">*</span></label>
                        <select name="invoice_id" id="payment-invoice" class="form-select" required>
                            <option value="">Select invoice</option>
                            @foreach($invoices as $invoice)
                                @php
                                    $invBal = max((float) $invoice->total_amount - (float) $invoice->paid_amount, 0);
                                @endphp
                                <option value="{{ $invoice->id }}"
                                    data-number="{{ $invoice->invoice_number }}"
                                    data-client="{{ optional($invoice->client)->name }}"
                                    data-total="${{ number_format((float) $invoice->total_amount, 2) }}"
                                    data-paid="${{ number_format((float) $invoice->paid_amount, 2) }}"
                                    data-balance="{{ number_format($invBal, 2, '.', '') }}"
                                    data-balance-formatted="${{ number_format($invBal, 2) }}"
                                    data-insurance='@json($invoice->client?->insuranceInformations)'
                                    @selected(($selectedInvoice?->id ?? null) === $invoice->id)>
                                    {{ $invoice->invoice_number }} - {{ optional($invoice->client)->name }} - Balance ${{ number_format($invBal, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Method <span class="required-mark">*</span></label>
                        <select name="payment_method" id="pmt-method-select" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="e_transfer">E-Transfer</option>
                            <option value="both">Both / Split Payment</option>
                            <option value="insurance">Insurance</option>
                        </select>
                    </div>
                    <div class="col-md-2" id="pmt-amount-wrapper">
                        <label class="form-label" id="pmt-amount-label">Cash Amount <span class="required-mark">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="payment-amount" class="form-control" value="{{ $selectedInvoice ? number_format(max((float) $selectedInvoice->total_amount - (float) $selectedInvoice->paid_amount, 0), 2, '.', '') : old('amount') }}" placeholder="0.00" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date <span class="required-mark">*</span></label>
                        <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" id="pmt-submit-btn" class="btn btn-primary flex-grow-1">Add payment</button>
                        <button type="button" class="btn btn-light border btn-cancel-payment-trigger" id="btn-cancel-payment">Cancel</button>
                    </div>

                    {{-- Split Payment Component Inputs --}}
                    <div id="pmt-split-block" class="col-12 d-none">
                        <div class="p-3 bg-light rounded border border-primary-subtle">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold mb-0 text-primary"><i class="bx bx-git-repo-forked me-1"></i> Split Payment Breakdown</h6>
                                <span class="small text-muted">Select payment methods (2 to 4) and enter amounts</span>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark d-block mb-1">Select payment methods (2, 3, or 4): <span class="required-mark">*</span></label>
                                <div class="d-flex flex-wrap gap-4 align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input split-method-chk" type="checkbox" name="split_methods[]" id="split-chk-cash" value="cash" checked>
                                        <label class="form-check-label small fw-medium" for="split-chk-cash">Cash</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input split-method-chk" type="checkbox" name="split_methods[]" id="split-chk-card" value="card" checked>
                                        <label class="form-check-label small fw-medium" for="split-chk-card">Card</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input split-method-chk" type="checkbox" name="split_methods[]" id="split-chk-etransfer" value="e_transfer">
                                        <label class="form-check-label small fw-medium" for="split-chk-etransfer">E-Transfer</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input split-method-chk" type="checkbox" name="split_methods[]" id="split-chk-insurance" value="insurance">
                                        <label class="form-check-label small fw-medium" for="split-chk-insurance">Insurance</label>
                                    </div>
                                </div>
                                <div id="split-selection-warning" class="small text-danger d-none mt-1">Please select at least 2 payment methods for Split Payment.</div>
                            </div>

                            <div class="row g-2">
                                <div id="pmt-cash-amount-col" class="col-md-6 col-lg-3">
                                    <label class="form-label small" for="split-cash-amount">Cash Amount <span class="required-mark">*</span></label>
                                    <input type="number" step="0.01" min="0" name="cash_amount" id="split-cash-amount" class="form-control form-control-sm split-amt-input" placeholder="0.00">
                                </div>
                                <div id="pmt-card-amount-col" class="col-md-6 col-lg-3">
                                    <label class="form-label small" for="split-card-amount">Card Amount <span class="required-mark">*</span></label>
                                    <input type="number" step="0.01" min="0" name="card_amount" id="split-card-amount" class="form-control form-control-sm split-amt-input" placeholder="0.00">
                                </div>
                                <div id="pmt-etransfer-amount-col" class="col-md-6 col-lg-3 d-none">
                                    <label class="form-label small" for="split-etransfer-amount">E-Transfer Amount <span class="required-mark">*</span></label>
                                    <input type="number" step="0.01" min="0" name="e_transfer_amount" id="split-etransfer-amount" class="form-control form-control-sm split-amt-input" placeholder="0.00">
                                </div>
                                <div id="pmt-insurance-amount-col" class="col-md-6 col-lg-3 d-none">
                                    <label class="form-label small" for="split-insurance-amount">Insurance Amount <span class="required-mark">*</span></label>
                                    <input type="number" step="0.01" min="0" name="insurance_amount" id="split-insurance-amount" class="form-control form-control-sm split-amt-input" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Card Specific Fields --}}
                    <div id="pmt-card-block" class="col-12 d-none">
                        <div class="p-3 bg-light rounded border">
                            <h6 class="fw-bold mb-2 text-dark"><i class="bx bx-credit-card me-1"></i> Card Details (Metadata Only)</h6>
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label class="form-label small" for="card_brand_input">Card Brand <span class="required-mark">*</span></label>
                                    <select name="card_brand" id="card_brand_input" class="form-select form-select-sm">
                                        <option value="">Select Brand</option>
                                        <option value="Visa">Visa</option>
                                        <option value="Mastercard">Mastercard</option>
                                        <option value="American Express">American Express</option>
                                        <option value="Discover">Discover</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <div class="invalid-feedback small" id="card-brand-feedback">Please select a Card Brand.</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small" for="cardholder_name_input">Cardholder Name</label>
                                    <input type="text" name="cardholder_name" id="cardholder_name_input" class="form-control form-control-sm" placeholder="e.g. John Smith">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small" for="card_last_four_input">Last 4 Digits</label>
                                    <input type="text" name="card_last_four" id="card_last_four_input" maxlength="4" class="form-control form-control-sm" placeholder="1234">
                                    <div class="invalid-feedback small" id="card-last-four-feedback">Card last 4 digits must be exactly 4 numeric digits.</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small" for="transaction_reference_input">Transaction Reference</label>
                                    <input type="text" name="transaction_reference" id="transaction_reference_input" class="form-control form-control-sm" placeholder="TXN-2026-00123">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- E-Transfer Specific Fields --}}
                    <div id="pmt-etransfer-block" class="col-12 d-none">
                        <div class="p-3 bg-light rounded border">
                            <h6 class="fw-bold mb-2 text-dark"><i class="bx bx-transfer me-1"></i> E-Transfer Details</h6>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label small">Reference Number</label>
                                    <input type="text" name="e_transfer_reference" class="form-control form-control-sm" placeholder="e.g. ETR-839291">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Sender Name</label>
                                    <input type="text" name="sender_name" class="form-control form-control-sm" placeholder="e.g. Sarah Connor">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Transfer Date</label>
                                    <input type="date" name="transfer_date" class="form-control form-control-sm" value="{{ now()->toDateString() }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Insurance Specific Fields --}}
                    <div id="pmt-insurance-block" class="col-12 d-none">
                        <div class="p-3 bg-light rounded border">
                            <h6 class="fw-bold mb-2 text-dark"><i class="bx bx-shield-quarter me-1"></i> Insurance Payment Details</h6>
                            <div class="row g-2 mb-2" id="client-saved-insurance-row">
                                <div class="col-12">
                                    <label class="form-label small">Select Client's Saved Insurance</label>
                                    <select name="insurance_information_id" id="pmt-saved-insurance-select" class="form-select form-select-sm">
                                        <option value="">-- Choose saved policy or enter details --</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label small">Insurance Company <span class="required-mark">*</span></label>
                                    <select name="insurance_company_id" id="pmt-insurance-company-id" class="form-select form-select-sm">
                                        <option value="">Select Company</option>
                                        @foreach($insuranceCompanies as $comp)
                                            <option value="{{ $comp->id }}">{{ $comp->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Policy ID <span class="required-mark">*</span></label>
                                    <input type="text" name="policy_id" id="pmt-policy-id" class="form-control form-control-sm" placeholder="Enter policy ID">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Member ID / Contract No. <span class="required-mark">*</span></label>
                                    <input type="text" name="member_id_or_contract_number" id="pmt-member-id" class="form-control form-control-sm" placeholder="Enter member ID or contract no.">
                                </div>
                                <div class="col-md-4 mt-2">
                                    <label class="form-label small">Claim / Reference Number</label>
                                    <input type="text" name="claim_reference" class="form-control form-control-sm" placeholder="CLM-88391">
                                </div>
                                <div class="col-md-4 mt-2">
                                    <label class="form-label small">Amount Submitted</label>
                                    <input type="number" step="0.01" name="amount_submitted" class="form-control form-control-sm" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label small">Notes / Reference</label>
                        <input name="notes" class="form-control form-control-sm" placeholder="Optional payment note">
                    </div>
                </form>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const form = document.getElementById('payment-record-form');
                const invoice = document.getElementById('payment-invoice');
                const amount = document.getElementById('payment-amount');
                const amountLabel = document.getElementById('pmt-amount-label');
                const submitBtn = document.getElementById('pmt-submit-btn');

                const banner = document.getElementById('invoice-summary-banner');
                const numEl = document.getElementById('summary-inv-number');
                const clientEl = document.getElementById('summary-inv-client');
                const totalEl = document.getElementById('summary-inv-total');
                const paidEl = document.getElementById('summary-inv-paid');
                const balanceEl = document.getElementById('summary-inv-balance');
                const statusBadge = document.getElementById('payment-status-badge');
                const validationMsg = document.getElementById('payment-validation-msg');

                const pmtMethod = document.getElementById('pmt-method-select');
                const splitBlock = document.getElementById('pmt-split-block');
                const splitCheckboxes = document.querySelectorAll('.split-method-chk');
                const splitWarning = document.getElementById('split-selection-warning');
                const chkCash = document.getElementById('split-chk-cash');
                const chkCard = document.getElementById('split-chk-card');
                const chkEtransfer = document.getElementById('split-chk-etransfer');
                const chkInsurance = document.getElementById('split-chk-insurance');
                const cashCol = document.getElementById('pmt-cash-amount-col');
                const cardCol = document.getElementById('pmt-card-amount-col');
                const etransferCol = document.getElementById('pmt-etransfer-amount-col');
                const insuranceCol = document.getElementById('pmt-insurance-amount-col');
                const cashInput = document.getElementById('split-cash-amount');
                const cardInput = document.getElementById('split-card-amount');
                const etransferInput = document.getElementById('split-etransfer-amount');
                const insuranceInput = document.getElementById('split-insurance-amount');

                const cardBlock = document.getElementById('pmt-card-block');
                const cardBrandInput = document.getElementById('card_brand_input');
                const cardholderNameInput = document.getElementById('cardholder_name_input');
                const cardLastFourInput = document.getElementById('card_last_four_input');
                const transactionReferenceInput = document.getElementById('transaction_reference_input');

                const etransferBlock = document.getElementById('pmt-etransfer-block');
                const insuranceBlock = document.getElementById('pmt-insurance-block');
                const savedInsSelect = document.getElementById('pmt-saved-insurance-select');
                const insuranceCompanyInput = document.getElementById('pmt-insurance-company-id');
                const policyIdInput = document.getElementById('pmt-policy-id');
                const memberIdInput = document.getElementById('pmt-member-id');
                const claimReferenceInput = form?.querySelector('input[name="claim_reference"]');
                const amountSubmittedInput = form?.querySelector('input[name="amount_submitted"]');
                const addPaymentBtn = document.getElementById('btn-show-add-payment');
                const paymentFormCard = document.getElementById('payment-form-card');
                const cancelBtns = document.querySelectorAll('.btn-cancel-payment-trigger');

                function clearInsuranceFields() {
                    if (savedInsSelect) savedInsSelect.value = '';
                    if (insuranceCompanyInput) {
                        insuranceCompanyInput.value = '';
                        insuranceCompanyInput.classList.remove('is-invalid');
                    }
                    if (policyIdInput) {
                        policyIdInput.value = '';
                        policyIdInput.classList.remove('is-invalid');
                    }
                    if (memberIdInput) {
                        memberIdInput.value = '';
                        memberIdInput.classList.remove('is-invalid');
                    }
                    if (claimReferenceInput) claimReferenceInput.value = '';
                    if (amountSubmittedInput) amountSubmittedInput.value = '';
                }

                function updateSplitControls() {
                    const checked = Array.from(splitCheckboxes).filter(c => c.checked);
                    const count = checked.length;

                    // Allow 2, 3, or 4 selections
                    splitCheckboxes.forEach(c => c.disabled = false);

                    if (count >= 2) {
                        if (splitWarning) splitWarning.classList.add('d-none');
                    } else {
                        if (splitWarning) {
                            splitWarning.classList.remove('d-none');
                            splitWarning.textContent = 'Please select at least 2 payment methods for Split Payment (currently ' + count + ' selected).';
                        }
                    }

                    if (cashCol) {
                        const isSelected = chkCash && chkCash.checked;
                        cashCol.classList.toggle('d-none', !isSelected);
                        if (!isSelected && cashInput) cashInput.value = '';
                    }
                    if (cardCol) {
                        const isSelected = chkCard && chkCard.checked;
                        cardCol.classList.toggle('d-none', !isSelected);
                        if (!isSelected && cardInput) cardInput.value = '';
                    }
                    if (etransferCol) {
                        const isSelected = chkEtransfer && chkEtransfer.checked;
                        etransferCol.classList.toggle('d-none', !isSelected);
                        if (!isSelected && etransferInput) etransferInput.value = '';
                    }
                    if (insuranceCol) {
                        const isSelected = chkInsurance && chkInsurance.checked;
                        insuranceCol.classList.toggle('d-none', !isSelected);
                        if (!isSelected && insuranceInput) insuranceInput.value = '';
                    }

                    toggleMetadataBlocks();
                    validateAmounts();
                }

                splitCheckboxes.forEach(chk => {
                    chk.addEventListener('change', () => {
                        updateSplitControls();
                    });
                });

                addPaymentBtn?.addEventListener('click', () => {
                    paymentFormCard?.classList.remove('d-none');
                    addPaymentBtn.setAttribute('aria-expanded', 'true');
                    paymentFormCard?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    setTimeout(() => {
                        if (invoice) {
                            invoice.focus();
                        } else if (paymentFormCard) {
                            paymentFormCard.focus();
                        }
                    }, 100);
                });

                cancelBtns.forEach(btn => {
                    btn.addEventListener('click', () => {
                        if (form) {
                            form.reset();
                        }
                        if (chkCash) chkCash.checked = true;
                        if (chkCard) chkCard.checked = true;
                        if (chkEtransfer) chkEtransfer.checked = false;
                        if (chkInsurance) chkInsurance.checked = false;
                        splitCheckboxes.forEach(c => c.disabled = false);

                        if (splitBlock) splitBlock.classList.add('d-none');
                        if (cardBlock) cardBlock.classList.add('d-none');
                        if (etransferBlock) etransferBlock.classList.add('d-none');
                        if (insuranceBlock) insuranceBlock.classList.add('d-none');
                        clearInsuranceFields();
                        if (banner) banner.classList.add('d-none');
                        if (amount) {
                            amount.removeAttribute('readonly');
                            amount.classList.remove('bg-light', 'is-invalid');
                            amount.value = '';
                        }
                        [cashInput, cardInput, etransferInput, insuranceInput, cardBrandInput, cardLastFourInput, cardholderNameInput, transactionReferenceInput].forEach(inp => {
                            if (inp) {
                                inp.value = '';
                                inp.classList.remove('is-invalid');
                            }
                        });
                        if (cardBrandInput) {
                            cardBrandInput.removeAttribute('required');
                            cardBrandInput.required = false;
                        }
                        if (validationMsg) validationMsg.classList.add('d-none');
                        if (statusBadge) {
                            statusBadge.className = 'badge bg-secondary';
                            statusBadge.textContent = 'Pending';
                        }
                        if (submitBtn) submitBtn.disabled = false;
                        isOverpaid = false;

                        paymentFormCard?.classList.add('d-none');
                        addPaymentBtn?.setAttribute('aria-expanded', 'false');
                        addPaymentBtn?.focus();
                    });
                });

                let isOverpaid = false;

                function getRemainingBalance() {
                    const opt = invoice?.selectedOptions[0];
                    if (!opt || !opt.value) return 0;
                    return parseFloat(opt.dataset.balance || '0') || 0;
                }

                function toggleMethodBlocks() {
                    const val = pmtMethod ? pmtMethod.value : 'cash';
                    const isBoth = val === 'both';

                    if (splitBlock) splitBlock.classList.toggle('d-none', !isBoth);

                    if (amountLabel && amount) {
                        if (val === 'cash') {
                            amountLabel.innerHTML = 'Cash Amount <span class="required-mark">*</span>';
                            amount.removeAttribute('readonly');
                            amount.classList.remove('bg-light');
                            amount.placeholder = '0.00';
                        } else if (val === 'card') {
                            amountLabel.innerHTML = 'Card Amount <span class="required-mark">*</span>';
                            amount.removeAttribute('readonly');
                            amount.classList.remove('bg-light');
                            amount.placeholder = '0.00';
                        } else if (val === 'e_transfer') {
                            amountLabel.innerHTML = 'E-Transfer Amount <span class="required-mark">*</span>';
                            amount.removeAttribute('readonly');
                            amount.classList.remove('bg-light');
                            amount.placeholder = '0.00';
                        } else if (val === 'insurance') {
                            amountLabel.innerHTML = 'Insurance Amount <span class="required-mark">*</span>';
                            amount.removeAttribute('readonly');
                            amount.classList.remove('bg-light');
                            amount.placeholder = '0.00';
                        } else if (isBoth) {
                            amountLabel.innerHTML = 'Total Paid Amount <span class="required-mark">*</span>';
                            amount.setAttribute('readonly', 'readonly');
                            amount.classList.add('bg-light');
                            amount.placeholder = '0.00';
                        }
                    }

                    if (!isBoth) {
                        if (cashInput) cashInput.value = '';
                        if (cardInput) cardInput.value = '';
                        if (etransferInput) etransferInput.value = '';
                        if (insuranceInput) insuranceInput.value = '';
                    } else {
                        updateSplitControls();
                    }

                    toggleMetadataBlocks();
                    validateAmounts();
                }

                function toggleMetadataBlocks() {
                    const val = (pmtMethod ? pmtMethod.value : 'cash').toLowerCase();
                    const isBoth = val === 'both';

                    const cardVal = parseFloat(cardInput?.value || 0) || 0;

                    const isCardSelectedInSplit = isBoth && chkCard && chkCard.checked;
                    const isEtransferSelectedInSplit = isBoth && chkEtransfer && chkEtransfer.checked;
                    const isInsuranceSelectedInSplit = isBoth && chkInsurance && chkInsurance.checked;

                    const showCard = val === 'card' || isCardSelectedInSplit;
                    const showEtransfer = val === 'e_transfer' || isEtransferSelectedInSplit;
                    const showInsurance = val === 'insurance' || isInsuranceSelectedInSplit;

                    if (cardBlock) {
                        cardBlock.classList.toggle('d-none', !showCard);
                        if (showCard && (val === 'card' || cardVal > 0)) {
                            if (cardBrandInput) {
                                cardBrandInput.setAttribute('required', 'required');
                                cardBrandInput.required = true;
                            }
                        } else {
                            if (cardBrandInput) {
                                cardBrandInput.removeAttribute('required');
                                cardBrandInput.required = false;
                                cardBrandInput.classList.remove('is-invalid');
                            }
                        }

                        if (!showCard) {
                            if (cardBrandInput) {
                                cardBrandInput.removeAttribute('required');
                                cardBrandInput.required = false;
                                cardBrandInput.classList.remove('is-invalid');
                                cardBrandInput.value = '';
                            }
                            if (cardLastFourInput) {
                                cardLastFourInput.classList.remove('is-invalid');
                                cardLastFourInput.value = '';
                            }
                            if (cardholderNameInput) {
                                cardholderNameInput.value = '';
                            }
                            if (transactionReferenceInput) {
                                transactionReferenceInput.value = '';
                            }
                        }
                    }
                    if (etransferBlock) {
                        etransferBlock.classList.toggle('d-none', !showEtransfer);
                        if (!showEtransfer) {
                            const etRef = form?.querySelector('input[name="e_transfer_reference"]');
                            const etSender = form?.querySelector('input[name="sender_name"]');
                            if (etRef) etRef.value = '';
                            if (etSender) etSender.value = '';
                        }
                    }
                    if (insuranceBlock) {
                        insuranceBlock.classList.toggle('d-none', !showInsurance);
                        if (!showInsurance) {
                            clearInsuranceFields();
                        }
                    }
                }

                function calculateTotalEntered() {
                    const val = pmtMethod ? pmtMethod.value : 'cash';
                    if (val === 'both') {
                        const c = (chkCash && chkCash.checked) ? (parseFloat(cashInput?.value || 0) || 0) : 0;
                        const cd = (chkCard && chkCard.checked) ? (parseFloat(cardInput?.value || 0) || 0) : 0;
                        const et = (chkEtransfer && chkEtransfer.checked) ? (parseFloat(etransferInput?.value || 0) || 0) : 0;
                        const ins = (chkInsurance && chkInsurance.checked) ? (parseFloat(insuranceInput?.value || 0) || 0) : 0;
                        const total = c + cd + et + ins;
                        if (amount) {
                            amount.value = total > 0 ? total.toFixed(2) : '';
                        }
                        return total;
                    } else {
                        return parseFloat(amount?.value || 0) || 0;
                    }
                }

                function showOverpaymentToast() {
                    if (window.AppToast && typeof window.AppToast.show === 'function') {
                        window.AppToast.show({
                            type: 'danger',
                            title: 'Payment Error',
                            message: 'Paid amount cannot exceed the remaining balance.'
                        });
                    }
                }

                function setInputInvalidClass(invalid) {
                    const val = pmtMethod ? pmtMethod.value : 'cash';
                    if (val === 'both') {
                        [cashInput, cardInput, etransferInput, insuranceInput].forEach(inp => {
                            if (inp) {
                                if (invalid) inp.classList.add('is-invalid');
                                else inp.classList.remove('is-invalid');
                            }
                        });
                        if (amount) {
                            if (invalid) amount.classList.add('is-invalid');
                            else amount.classList.remove('is-invalid');
                        }
                    } else {
                        if (amount) {
                            if (invalid) amount.classList.add('is-invalid');
                            else amount.classList.remove('is-invalid');
                        }
                    }
                }

                function validateAmounts() {
                    const balance = getRemainingBalance();
                    const total = calculateTotalEntered();
                    const EPS = 0.005;

                    // Compare against the invoice remaining balance
                    if (balance > 0 && total > (balance + EPS)) {
                        if (!isOverpaid) {
                            showOverpaymentToast();
                            isOverpaid = true;
                        }

                        if (submitBtn) submitBtn.disabled = true;
                        if (validationMsg) validationMsg.classList.remove('d-none');
                        setInputInvalidClass(true);

                        if (statusBadge) {
                            statusBadge.className = 'badge bg-danger';
                            statusBadge.textContent = 'Overpayment';
                        }
                    } else {
                        // Valid range (Lower or Equal)
                        isOverpaid = false;
                        if (submitBtn) submitBtn.disabled = false;
                        if (validationMsg) validationMsg.classList.add('d-none');
                        setInputInvalidClass(false);

                        if (statusBadge) {
                            if (total <= 0) {
                                statusBadge.className = 'badge bg-secondary';
                                statusBadge.textContent = 'Pending';
                            } else if (Math.abs(total - balance) <= EPS) {
                                statusBadge.className = 'badge bg-success';
                                statusBadge.textContent = 'Paid';
                            } else {
                                statusBadge.className = 'badge bg-warning text-dark';
                                statusBadge.textContent = 'Partially Paid';
                            }
                        }
                    }
                }

                // Strictly ensure card last 4 digits only accepts digits and max 4
                cardLastFourInput?.addEventListener('input', (e) => {
                    e.target.value = e.target.value.replace(/\D/g, '').slice(0, 4);
                    if (e.target.value.length === 4 || e.target.value.length === 0) {
                        e.target.classList.remove('is-invalid');
                    }
                });

                cardBrandInput?.addEventListener('change', () => {
                    if (cardBrandInput.value) {
                        cardBrandInput.classList.remove('is-invalid');
                    }
                });

                [amount, cashInput, cardInput, etransferInput, insuranceInput].forEach(inp => {
                    if (inp) {
                        ['input', 'keyup', 'change', 'blur', 'paste'].forEach(evt => {
                            inp.addEventListener(evt, () => {
                                toggleMetadataBlocks();
                                validateAmounts();
                            });
                        });
                    }
                });

                pmtMethod?.addEventListener('change', () => {
                    toggleMethodBlocks();
                });

                let currentClientInsurances = [];

                function populateClientInsurance(opt) {
                    currentClientInsurances = [];
                    if (!savedInsSelect) return;
                    savedInsSelect.innerHTML = '<option value="">-- Choose saved policy or enter details --</option>';
                    if (!opt || !opt.dataset.insurance) return;
                    try {
                        const raw = opt.dataset.insurance;
                        const list = typeof raw === 'string' ? JSON.parse(raw) : raw;
                        if (Array.isArray(list) && list.length > 0) {
                            currentClientInsurances = list;
                            list.forEach(item => {
                                const o = document.createElement('option');
                                o.value = item.id;
                                const compName = (item.insurance_company && item.insurance_company.name)
                                    ? item.insurance_company.name
                                    : ((item.insuranceCompany && item.insuranceCompany.name)
                                        ? item.insuranceCompany.name
                                        : 'Insurance');
                                const pol = item.policy_id ?? item.policyId ?? '';
                                const mem = item.member_id_or_contract_number ?? item.memberIdOrContractNumber ?? '';
                                const compId = item.insurance_company_id ?? item.insuranceCompany?.id ?? item.insurance_company?.id ?? '';

                                o.textContent = `${compName} - Policy: ${pol || 'N/A'} (Member: ${mem || 'N/A'})`;
                                o.setAttribute('data-company-id', compId);
                                o.setAttribute('data-policy-id', pol);
                                o.setAttribute('data-member-id', mem);
                                o.dataset.companyId = compId;
                                o.dataset.policyId = pol;
                                o.dataset.memberId = mem;
                                savedInsSelect.appendChild(o);
                            });
                        }
                    } catch (e) {
                        console.warn('Insurance parse error', e);
                    }
                }

                function handleSavedInsuranceChange() {
                    const selVal = savedInsSelect ? String(savedInsSelect.value).trim() : '';
                    if (!selVal) {
                        return;
                    }
                    const record = currentClientInsurances.find(item => String(item.id) === selVal);
                    if (record) {
                        const compId = record.insurance_company_id ?? record.insurance_company?.id ?? record.insuranceCompany?.id ?? '';
                        const pol = record.policy_id ?? record.policyId ?? '';
                        const mem = record.member_id_or_contract_number ?? record.memberIdOrContractNumber ?? '';

                        if (insuranceCompanyInput && compId) {
                            insuranceCompanyInput.value = String(compId);
                            insuranceCompanyInput.classList.remove('is-invalid');
                        }
                        if (policyIdInput) {
                            policyIdInput.value = String(pol);
                            policyIdInput.classList.remove('is-invalid');
                        }
                        if (memberIdInput) {
                            memberIdInput.value = String(mem);
                            memberIdInput.classList.remove('is-invalid');
                        }
                    } else {
                        const selOpt = savedInsSelect.selectedOptions ? savedInsSelect.selectedOptions[0] : null;
                        if (selOpt) {
                            const compId = selOpt.dataset.companyId || selOpt.getAttribute('data-company-id') || '';
                            const pol = selOpt.dataset.policyId || selOpt.getAttribute('data-policy-id') || '';
                            const mem = selOpt.dataset.memberId || selOpt.getAttribute('data-member-id') || '';
                            if (insuranceCompanyInput && compId) {
                                insuranceCompanyInput.value = String(compId);
                                insuranceCompanyInput.classList.remove('is-invalid');
                            }
                            if (policyIdInput) {
                                policyIdInput.value = String(pol);
                                policyIdInput.classList.remove('is-invalid');
                            }
                            if (memberIdInput) {
                                memberIdInput.value = String(mem);
                                memberIdInput.classList.remove('is-invalid');
                            }
                        }
                    }
                }

                function handleInsuranceCompanyChange() {
                    const selectedCompanyId = insuranceCompanyInput ? String(insuranceCompanyInput.value).trim() : '';
                    if (!selectedCompanyId) {
                        if (savedInsSelect) savedInsSelect.value = '';
                        if (policyIdInput) policyIdInput.value = '';
                        if (memberIdInput) memberIdInput.value = '';
                        return;
                    }

                    // Find all saved insurance records for the CURRENTLY SELECTED CLIENT matching this company
                    const matching = currentClientInsurances.filter(item => {
                        const compId = item.insurance_company_id ?? item.insurance_company?.id ?? item.insuranceCompany?.id;
                        return compId !== undefined && compId !== null && String(compId).trim() === selectedCompanyId;
                    });

                    if (matching.length === 1) {
                        // Exactly one matching saved insurance record exists:
                        const match = matching[0];
                        const pol = match.policy_id ?? match.policyId ?? '';
                        const mem = match.member_id_or_contract_number ?? match.memberIdOrContractNumber ?? '';

                        if (policyIdInput) {
                            policyIdInput.value = String(pol);
                            policyIdInput.classList.remove('is-invalid');
                        }
                        if (memberIdInput) {
                            memberIdInput.value = String(mem);
                            memberIdInput.classList.remove('is-invalid');
                        }
                        if (savedInsSelect) {
                            savedInsSelect.value = String(match.id);
                        }
                        insuranceCompanyInput.classList.remove('is-invalid');
                    } else if (matching.length > 1) {
                        // Multiple saved insurance records exist for the same company:
                        // DO NOT guess which policy to use.
                        // Instead:
                        // - keep the saved insurance dropdown available/selected for choosing the exact policy
                        // - do not overwrite the wrong policy/member values.
                        const currentSavedId = savedInsSelect ? String(savedInsSelect.value).trim() : '';
                        const currentMatchesCompany = matching.some(m => String(m.id) === currentSavedId);
                        if (!currentMatchesCompany && savedInsSelect) {
                            savedInsSelect.value = '';
                        }
                    } else {
                        // matching.length === 0
                        // No saved insurance record exists for the selected company:
                        // - leave Policy ID and Member ID fields available for manual entry
                        // - do not insert fake/default values
                        // - do not break validation.
                        if (savedInsSelect) {
                            savedInsSelect.value = '';
                        }
                        if (policyIdInput) policyIdInput.value = '';
                        if (memberIdInput) memberIdInput.value = '';
                    }
                }

                savedInsSelect?.addEventListener('change', handleSavedInsuranceChange);

                insuranceCompanyInput?.addEventListener('change', handleInsuranceCompanyChange);
                insuranceCompanyInput?.addEventListener('input', handleInsuranceCompanyChange);

                [insuranceCompanyInput, policyIdInput, memberIdInput].forEach(inp => {
                    inp?.addEventListener('input', () => inp.classList.remove('is-invalid'));
                    inp?.addEventListener('change', () => inp.classList.remove('is-invalid'));
                });

                invoice?.addEventListener('change', () => {
                    const opt = invoice.selectedOptions[0];
                    clearInsuranceFields();
                    if (!opt || !opt.value) {
                        if (banner) banner.classList.add('d-none');
                        if (amount) { amount.value = ''; }
                        if (cashInput) cashInput.value = '';
                        if (cardInput) cardInput.value = '';
                        if (etransferInput) etransferInput.value = '';
                        if (insuranceInput) insuranceInput.value = '';
                        populateClientInsurance(null);
                        validateAmounts();
                        return;
                    }

                    const balance = opt.dataset.balance;
                    const val = pmtMethod ? pmtMethod.value : 'cash';
                    if (balance) {
                        if (val === 'both') {
                            if (cashInput) cashInput.value = '';
                            if (cardInput) cardInput.value = '';
                            if (etransferInput) etransferInput.value = '';
                            if (insuranceInput) insuranceInput.value = '';
                            if (amount) amount.value = '';
                        } else {
                            if (amount) amount.value = balance;
                        }
                    }

                    if (numEl) numEl.textContent = opt.dataset.number || '-';
                    if (clientEl) clientEl.textContent = opt.dataset.client || '-';
                    if (totalEl) totalEl.textContent = opt.dataset.total || '$0.00';
                    if (paidEl) paidEl.textContent = opt.dataset.paid || '$0.00';
                    if (balanceEl) balanceEl.textContent = opt.dataset.balanceFormatted || '$0.00';
                    if (banner) banner.classList.remove('d-none');
                    populateClientInsurance(opt);
                    validateAmounts();
                });

                form?.addEventListener('submit', (e) => {
                    const balance = getRemainingBalance();
                    const total = calculateTotalEntered();
                    const EPS = 0.005;
                    const val = pmtMethod ? pmtMethod.value : 'cash';
                    const isBoth = val === 'both';
                    const isCardSelectedInSplit = isBoth && chkCard && chkCard.checked;
                    const isInsuranceSelectedInSplit = isBoth && chkInsurance && chkInsurance.checked;
                    const cardVal = isBoth ? (parseFloat(cardInput?.value || 0) || 0) : (val === 'card' ? total : 0);

                    // Overpayment check
                    if (balance > 0 && total > (balance + EPS)) {
                        e.preventDefault();
                        e.stopPropagation();
                        showOverpaymentToast();
                        if (submitBtn) submitBtn.disabled = true;
                        return false;
                    }

                    if (total <= 0) {
                        e.preventDefault();
                        return false;
                    }

                    if (isBoth) {
                        const checked = Array.from(splitCheckboxes).filter(c => c.checked);
                        if (checked.length < 2) {
                            e.preventDefault();
                            e.stopPropagation();
                            if (window.AppToast && typeof window.AppToast.show === 'function') {
                                window.AppToast.show({
                                    type: 'danger',
                                    title: 'Validation Error',
                                    message: 'Please select at least two payment methods for Split Payment.'
                                });
                            }
                            return false;
                        }

                        let hasPositiveAmount = false;
                        let validAmounts = true;
                        checked.forEach(chk => {
                            const amtInput = chk.value === 'cash' ? cashInput
                                : (chk.value === 'card' ? cardInput
                                : (chk.value === 'e_transfer' ? etransferInput : insuranceInput));
                            const aVal = parseFloat(amtInput?.value || 0);
                            if (!amtInput || isNaN(aVal) || aVal < 0) {
                                validAmounts = false;
                                if (amtInput) amtInput.classList.add('is-invalid');
                            } else if (aVal > 0) {
                                hasPositiveAmount = true;
                            }
                        });

                        if (!validAmounts || !hasPositiveAmount) {
                            e.preventDefault();
                            e.stopPropagation();
                            if (window.AppToast && typeof window.AppToast.show === 'function') {
                                window.AppToast.show({
                                    type: 'danger',
                                    title: 'Validation Error',
                                    message: 'Selected payment method amounts must be greater than or equal to 0.00, and at least one must be greater than 0.00.'
                                });
                            }
                            return false;
                        }
                    }

                    // When Card Amount > 0: Card details must be validated before allowing submission
                    if ((val === 'card' && total > 0) || (isCardSelectedInSplit && cardVal > 0)) {
                        if (cardBrandInput && !cardBrandInput.value) {
                            e.preventDefault();
                            e.stopPropagation();
                            cardBrandInput.classList.add('is-invalid');
                            cardBrandInput.focus();
                            if (window.AppToast && typeof window.AppToast.show === 'function') {
                                window.AppToast.show({
                                    type: 'danger',
                                    title: 'Validation Error',
                                    message: 'The card brand field is required when paying with card.'
                                });
                            }
                            return false;
                        }

                        if (cardLastFourInput && cardLastFourInput.value && !/^\d{4}$/.test(cardLastFourInput.value)) {
                            e.preventDefault();
                            e.stopPropagation();
                            cardLastFourInput.classList.add('is-invalid');
                            cardLastFourInput.focus();
                            if (window.AppToast && typeof window.AppToast.show === 'function') {
                                window.AppToast.show({
                                    type: 'danger',
                                    title: 'Validation Error',
                                    message: 'Card last 4 digits must be exactly 4 numeric digits.'
                                });
                            }
                            return false;
                        }
                    } else {
                        // When Card Amount = 0 or payment method does not include card: clear card metadata so it is not stored
                        if (cardBrandInput) cardBrandInput.value = '';
                        if (cardholderNameInput) cardholderNameInput.value = '';
                        if (cardLastFourInput) cardLastFourInput.value = '';
                        if (transactionReferenceInput) transactionReferenceInput.value = '';
                    }

                    if (val === 'insurance' || isInsuranceSelectedInSplit) {
                        if (insuranceCompanyInput && !insuranceCompanyInput.value) {
                            e.preventDefault();
                            e.stopPropagation();
                            insuranceCompanyInput.classList.add('is-invalid');
                            insuranceCompanyInput.focus();
                            if (window.AppToast && typeof window.AppToast.show === 'function') {
                                window.AppToast.show({
                                    type: 'danger',
                                    title: 'Validation Error',
                                    message: 'Please select an insurance company when paying with insurance.'
                                });
                            }
                            return false;
                        }

                        if (policyIdInput && !policyIdInput.value.trim()) {
                            e.preventDefault();
                            e.stopPropagation();
                            policyIdInput.classList.add('is-invalid');
                            policyIdInput.focus();
                            if (window.AppToast && typeof window.AppToast.show === 'function') {
                                window.AppToast.show({
                                    type: 'danger',
                                    title: 'Validation Error',
                                    message: 'The policy ID is required when paying with insurance.'
                                });
                            }
                            return false;
                        }

                        if (memberIdInput && !memberIdInput.value.trim()) {
                            e.preventDefault();
                            e.stopPropagation();
                            memberIdInput.classList.add('is-invalid');
                            memberIdInput.focus();
                            if (window.AppToast && typeof window.AppToast.show === 'function') {
                                window.AppToast.show({
                                    type: 'danger',
                                    title: 'Validation Error',
                                    message: 'The member ID or contract number is required when paying with insurance.'
                                });
                            }
                            return false;
                        }
                    } else {
                        clearInsuranceFields();
                    }
                });

                toggleMethodBlocks();
                if (invoice && invoice.value) {
                    populateClientInsurance(invoice.selectedOptions[0]);
                }
                validateAmounts();
            });
        </script>

        <!-- Toolbar -->
        <x-list-toolbar :paginator="$paymentRecords" searchAction="{{ route('payment-records.index') }}" searchPlaceholder="Search payments">
            <x-slot name="filters">
                <x-list-toolbar-filters
                    :showClear="(request()->has('search') && request('search') !== '') || request()->filled('payment_method')"
                    :clearUrl="route('payment-records.index', ['per_page' => request('per_page', $paymentRecords->perPage())])" />
                <div class="dropdown">
                    <button class="btn btn-light border dropdown-toggle btn-sm text-muted" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        {{ request()->filled('payment_method') ? (request('payment_method') === 'e_transfer' ? 'E-Transfer' : ucfirst(str_replace('_', ' ', request('payment_method')))) : 'Payment method' }}
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('payment-records.index', request()->except(['payment_method', 'page'])) }}">All</a></li>
                        <li><a class="dropdown-item" href="{{ route('payment-records.index', array_merge(request()->except(['payment_method', 'page']), ['payment_method' => 'cash'])) }}">Cash</a></li>
                        <li><a class="dropdown-item" href="{{ route('payment-records.index', array_merge(request()->except(['payment_method', 'page']), ['payment_method' => 'card'])) }}">Card</a></li>
                        <li><a class="dropdown-item" href="{{ route('payment-records.index', array_merge(request()->except(['payment_method', 'page']), ['payment_method' => 'e_transfer'])) }}">E-Transfer</a></li>
                        <li><a class="dropdown-item" href="{{ route('payment-records.index', array_merge(request()->except(['payment_method', 'page']), ['payment_method' => 'insurance'])) }}">Insurance</a></li>
                        <li><a class="dropdown-item" href="{{ route('payment-records.index', array_merge(request()->except(['payment_method', 'page']), ['payment_method' => 'both'])) }}">Both (Split)</a></li>
                        <li><a class="dropdown-item" href="{{ route('payment-records.index', array_merge(request()->except(['payment_method', 'page']), ['payment_method' => 'cash_card'])) }}">Cash + Card</a></li>
                        <li><a class="dropdown-item" href="{{ route('payment-records.index', array_merge(request()->except(['payment_method', 'page']), ['payment_method' => 'card_e_transfer'])) }}">Card + E-Transfer</a></li>
                        <li><a class="dropdown-item" href="{{ route('payment-records.index', array_merge(request()->except(['payment_method', 'page']), ['payment_method' => 'cash_e_transfer'])) }}">Cash + E-Transfer</a></li>
                        <li><a class="dropdown-item" href="{{ route('payment-records.index', array_merge(request()->except(['payment_method', 'page']), ['payment_method' => 'cash_insurance'])) }}">Cash + Insurance</a></li>
                        <li><a class="dropdown-item" href="{{ route('payment-records.index', array_merge(request()->except(['payment_method', 'page']), ['payment_method' => 'card_insurance'])) }}">Card + Insurance</a></li>
                        <li><a class="dropdown-item" href="{{ route('payment-records.index', array_merge(request()->except(['payment_method', 'page']), ['payment_method' => 'e_transfer_insurance'])) }}">E-Transfer + Insurance</a></li>
                    </ul>
                </div>
            </x-slot>
        </x-list-toolbar>

        <!-- Table -->
        <div class="bg-white rounded shadow-sm overflow-hidden mb-5">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 250px;">Date <i class='bx bx-down-arrow-alt'></i></th>
                            <th>Client name</th>
                            <th>Status</th>
                            <th>Method</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paymentRecords as $record)
                            <tr>
                                <td class="text-muted">{{ $record->created_at->format('M j, Y - h:i A') }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="client-avatar">
                                            <i class='bx bx-user'></i>
                                        </div>
                                            <span>{{ optional(optional($record->invoice)->client)->name ?: 'N/A' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge badge-paid">
                                        <i class='bx bx-check-circle'></i> Paid
                                    </span>
                                </td>
                                <td class="small">
                                    <span class="fw-medium text-dark">{{ $record->formatted_method_label }}</span>
                                </td>
                                <td class="small fw-500 text-end">${{ number_format($record->amount, 2) }}</td>
                                <td class="text-end">
                                    @if($record->invoice)
                                        <a href="{{ route('invoices.show', $record->invoice_id) }}" class="btn btn-link text-muted p-0 me-2"><i class='bx bx-show'></i></a>
                                    @endif
                                    <form action="{{ route('payment-records.destroy', $record->id) }}" method="POST" class="d-inline"
                                        data-confirm="This payment record will be permanently removed if the server allows it."
                                        data-confirm-title="Delete payment?"
                                        data-confirm-record="{{ number_format((float) $record->amount, 2) }}"
                                        data-confirm-text="Delete"
                                        data-confirm-loading="Deleting...">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-link text-muted p-0"><i class='bx bx-trash'></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No payment records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            @include('partials.pagination', ['paginator' => $paymentRecords])
        </div>
    </div>
@endsection
