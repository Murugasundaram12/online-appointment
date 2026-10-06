@extends('layouts.app')

@section('title', 'Create Quotation')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <div>
                <h2 class="fs-4 m-0 fw-bold">Create Quotation</h2>
                <div class="text-muted small">Generate a pricing estimate or quotation for a client or prospective appointment.</div>
            </div>
            <a href="{{ route('calendar.index') }}" class="btn btn-white border btn-sm text-muted">Back to Calendar</a>
        </div>
    </nav>

    <div class="container-fluid px-4 pt-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class='bx bx-check-circle me-1'></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="kpi-icon"><i class='bx bx-file-blank'></i></div>
                            <div>
                                <h3 class="fs-5 fw-bold mb-1">Quotation Details</h3>
                                <p class="text-muted mb-0 small">Select a client and add line items. Linking to an appointment is entirely optional.</p>
                            </div>
                        </div>

                        <form action="{{ route('quotations.store') }}" method="POST" id="quotation-create-form" novalidate>
                            @csrf
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="appointment_id" class="form-label">Appointment <span class="text-muted fw-normal">(Optional)</span></label>
                                    @error('appointment_id')
                                        <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                    <select id="appointment_id" name="appointment_id" class="form-select @error('appointment_id') is-invalid @enderror">
                                        <option value="">None (Standalone Quotation)</option>
                                        @foreach($appointments as $appointment)
                                            <option value="{{ $appointment->id }}"
                                                data-client-id="{{ $appointment->client_id }}"
                                                data-staff-id="{{ $appointment->staff_id }}"
                                                data-service-id="{{ $appointment->service_id }}"
                                                data-service-name="{{ $appointment->service->name ?? 'Service' }}"
                                                data-service-price="{{ $appointment->service->price ?? '' }}"
                                                {{ old('appointment_id') == $appointment->id ? 'selected' : '' }}>
                                                {{ optional($appointment->start_time)->format('M j, Y g:i A') }}
                                                - {{ $appointment->client->name ?? 'Client' }}
                                                - {{ $appointment->service->name ?? 'Service' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Optional: Link to an appointment to auto-fill client, practitioner, and service item.</div>
                                </div>

                                <div class="col-md-6">
                                    <label for="client_id" class="form-label">Client / Patient <span class="required-mark">*</span></label>
                                    @error('client_id')
                                        <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                             {{ $message }}
                                        </div>
                                    @enderror
                                    <select id="client_id" name="client_id" class="form-select @error('client_id') is-invalid @enderror" required>
                                        <option value="">Select client</option>
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                                {{ $client->name }}{{ $client->phone ? ' - ' . $client->phone : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="staff_id" class="form-label">Practitioner <span class="required-mark">*</span></label>
                                    @error('staff_id')
                                        <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                    <select id="staff_id" name="staff_id" class="form-select @error('staff_id') is-invalid @enderror" required>
                                        <option value="">Select practitioner</option>
                                        @foreach($staff as $member)
                                            <option value="{{ $member->id }}" {{ old('staff_id') == $member->id ? 'selected' : '' }}>
                                                {{ $member->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="issued_date" class="form-label">Issued Date <span class="required-mark">*</span></label>
                                    @error('issued_date')
                                        <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                    <input type="date" id="issued_date" name="issued_date" value="{{ old('issued_date', now()->toDateString()) }}"
                                        class="form-control @error('issued_date') is-invalid @enderror" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="valid_until" class="form-label">Valid Until <span class="required-mark">*</span></label>
                                    @error('valid_until')
                                        <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                    <input type="date" id="valid_until" name="valid_until" value="{{ old('valid_until', now()->addDays(30)->toDateString()) }}"
                                        class="form-control @error('valid_until') is-invalid @enderror" required>
                                </div>

                                {{-- Multi-Item Section --}}
                                <div class="col-12 mt-4 pt-3 border-top">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h4 class="fs-6 fw-bold mb-0 text-dark">
                                            <i class='bx bx-list-ul me-1 text-primary'></i> Quotation Items
                                        </h4>
                                        <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1 shadow-sm" id="btn-add-item">
                                            <i class='bx bx-plus'></i> Add Item
                                        </button>
                                    </div>

                                    @error('items')
                                        <div class="invalid-feedback d-block app-field-error text-danger small mb-2 fw-medium" role="alert">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                    @if($errors->has('items.*'))
                                        <div class="alert alert-danger py-2 small mb-3">
                                            <i class='bx bx-error-circle me-1'></i> Please check the item row details for errors.
                                        </div>
                                    @endif

                                    <div class="table-responsive border rounded bg-white">
                                        <table class="table table-hover align-middle mb-0" id="items-table" style="min-width: 760px;">
                                            <thead class="table-light text-muted small">
                                                <tr>
                                                    <th scope="col" style="width: 3%;" class="text-center py-2 border-0">#</th>
                                                    <th scope="col" style="width: 22%;" class="py-2 border-0">Service</th>
                                                    <th scope="col" style="width: 19%;" class="py-2 border-0">Description</th>
                                                    <th scope="col" style="width: 15%;" class="py-2 border-0">Practitioner</th>
                                                    <th scope="col" style="width: 8%;" class="py-2 border-0 text-center">Qty</th>
                                                    <th scope="col" style="width: 11%;" class="py-2 border-0 text-end">Rate</th>
                                                    <th scope="col" style="width: 9%;" class="py-2 border-0 text-end">Disc ($)</th>
                                                    <th scope="col" style="width: 7%;" class="py-2 border-0 text-end">Tax (%)</th>
                                                    <th scope="col" style="width: 10%;" class="py-2 border-0 text-end">Amount</th>
                                                    <th scope="col" style="width: 4%;" class="py-2 border-0 text-center"><i class='bx bx-cog'></i></th>
                                                </tr>
                                            </thead>
                                            <tbody id="items-table-body">
                                                @php
                                                    $initialItems = old('items');
                                                    if (!is_array($initialItems) || empty($initialItems)) {
                                                        $initialItems = [
                                                            [
                                                                'service_id' => '',
                                                                'staff_id' => '',
                                                                'description' => '',
                                                                'quantity' => '1',
                                                                'unit_price' => '0.00',
                                                                'discount_amount' => '0.00',
                                                                'tax_rate' => '0.00',
                                                                'sort_order' => '1',
                                                            ]
                                                        ];
                                                    }
                                                @endphp
                                                @foreach($initialItems as $index => $item)
                                                    <tr class="item-row" data-index="{{ $index }}">
                                                        <td class="text-muted small row-number text-center align-middle">
                                                            {{ $loop->iteration }}
                                                        </td>
                                                        <td>
                                                            <select name="items[{{ $index }}][service_id]" class="form-select form-select-sm item-service">
                                                                <option value="">-- Custom / Select Service --</option>
                                                                @foreach($services as $service)
                                                                    <option value="{{ $service->id }}"
                                                                        data-price="{{ $service->price }}"
                                                                        data-name="{{ $service->name }}"
                                                                        {{ (string) ($item['service_id'] ?? '') === (string) $service->id ? 'selected' : '' }}>
                                                                        {{ $service->name }} ({{ $currency }}{{ number_format($service->price, 2) }})
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="text" name="items[{{ $index }}][description]"
                                                                class="form-control form-control-sm item-description"
                                                                placeholder="Item description"
                                                                value="{{ $item['description'] ?? '' }}">
                                                        </td>
                                                        <td>
                                                            <select name="items[{{ $index }}][staff_id]" class="form-select form-select-sm item-staff">
                                                                <option value="">Same as Practitioner</option>
                                                                @foreach($staff as $member)
                                                                    <option value="{{ $member->id }}"
                                                                        {{ (string) ($item['staff_id'] ?? '') === (string) $member->id ? 'selected' : '' }}>
                                                                        {{ $member->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="items[{{ $index }}][quantity]"
                                                                class="form-control form-control-sm text-center item-qty"
                                                                step="0.01" min="0.01"
                                                                value="{{ $item['quantity'] ?? '1' }}" required>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="items[{{ $index }}][unit_price]"
                                                                class="form-control form-control-sm text-end item-price"
                                                                step="0.01" min="0"
                                                                value="{{ isset($item['unit_price']) ? number_format((float)$item['unit_price'], 2, '.', '') : '0.00' }}" required>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="items[{{ $index }}][discount_amount]"
                                                                class="form-control form-control-sm text-end item-discount"
                                                                step="0.01" min="0"
                                                                value="{{ isset($item['discount_amount']) ? number_format((float)$item['discount_amount'], 2, '.', '') : '0.00' }}">
                                                        </td>
                                                        <td>
                                                            <input type="number" name="items[{{ $index }}][tax_rate]"
                                                                class="form-control form-control-sm text-end item-tax"
                                                                step="0.01" min="0" max="100"
                                                                value="{{ isset($item['tax_rate']) ? number_format((float)$item['tax_rate'], 2, '.', '') : '0.00' }}">
                                                        </td>
                                                        <td class="text-end align-middle fw-semibold item-line-total">
                                                            {{ $currency }}0.00
                                                        </td>
                                                        <td class="text-center align-middle">
                                                            <input type="hidden" name="items[{{ $index }}][sort_order]" class="item-sort-order" value="{{ $index + 1 }}">
                                                            <button type="button" class="btn btn-link text-danger p-0 btn-remove-row" title="Remove Item" style="visibility: {{ count($initialItems) > 1 ? 'visible' : 'hidden' }};">
                                                                <i class='bx bx-trash fs-5'></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Summary Preview Card -->
                                    <div class="row justify-content-end mt-3">
                                        <div class="col-md-6 col-lg-5">
                                            <div class="bg-light p-3 rounded border">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span class="text-muted small">Subtotal</span>
                                                    <span class="fw-medium small" id="summary-subtotal">{{ $currency }}0.00</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span class="text-muted small">Total Discount</span>
                                                    <span class="text-danger small" id="summary-discount">-{{ $currency }}0.00</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span class="text-muted small">Total Tax</span>
                                                    <span class="text-muted small" id="summary-tax">{{ $currency }}0.00</span>
                                                </div>
                                                <div class="d-flex justify-content-between pt-2 border-top">
                                                    <span class="fw-bold">Grand Total</span>
                                                    <span class="fw-bold fs-5 text-primary" id="summary-total">{{ $currency }}0.00</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Notes and Terms --}}
                                <div class="col-md-6 mt-3">
                                    <label for="notes" class="form-label">Notes <span class="text-muted fw-normal">(Optional)</span></label>
                                    @error('notes')
                                        <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                    <textarea id="notes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror"
                                        placeholder="Optional remarks or instructions for the client...">{{ old('notes') }}</textarea>
                                </div>

                                <div class="col-md-6 mt-3">
                                    <label for="terms" class="form-label">Terms & Conditions <span class="text-muted fw-normal">(Optional)</span></label>
                                    @error('terms')
                                        <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                    <textarea id="terms" name="terms" rows="3" class="form-control @error('terms') is-invalid @enderror"
                                        placeholder="Validity, cancellation, or payment terms...">{{ old('terms') }}</textarea>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="{{ route('calendar.index') }}" class="btn btn-light">Cancel</a>
                                <button type="submit" class="btn btn-primary px-4" data-loading-text="Creating...">Create Quotation</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="text-muted small text-uppercase fw-bold mb-2">Next Quotation Number</div>
                        <div class="fs-4 fw-bold text-primary mb-3">{{ $nextQuotationNumber }}</div>
                        <div class="alert app-alert app-alert-info mb-0">
                            <i class='bx bx-info-circle' aria-hidden="true"></i>
                            <div>Quotation numbers are generated automatically using the sequential quote prefix.</div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mt-3">
                    <div class="card-body p-4">
                        <div class="text-muted small text-uppercase fw-bold mb-2">Quotation Status</div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-secondary text-white px-3 py-2 fs-6">Draft</span>
                        </div>
                        <div class="text-muted small">New quotations are saved with Draft status until sent or approved.</div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mt-3">
                    <div class="card-body p-4">
                        <div class="text-muted small text-uppercase fw-bold mb-2">Linked Appointment</div>
                        <div id="selected-appointment-info" class="fw-bold text-dark">No appointment selected (Standalone quote)</div>
                        <div class="text-muted small mt-2">Quotations can be standalone or linked to a scheduled appointment.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Row Template for Dynamic Addition --}}
    <template id="item-row-template">
        <tr class="item-row" data-index="__INDEX__">
            <td class="text-muted small row-number text-center align-middle">__NUM__</td>
            <td>
                <select name="items[__INDEX__][service_id]" class="form-select form-select-sm item-service">
                    <option value="">-- Custom / Select Service --</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}"
                            data-price="{{ $service->price }}"
                            data-name="{{ $service->name }}">
                            {{ $service->name }} ({{ $currency }}{{ number_format($service->price, 2) }})
                        </option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="text" name="items[__INDEX__][description]"
                    class="form-control form-control-sm item-description"
                    placeholder="Item description">
            </td>
            <td>
                <select name="items[__INDEX__][staff_id]" class="form-select form-select-sm item-staff">
                    <option value="">Same as Practitioner</option>
                    @foreach($staff as $member)
                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" name="items[__INDEX__][quantity]"
                    class="form-control form-control-sm text-center item-qty"
                    step="0.01" min="0.01" value="1" required>
            </td>
            <td>
                <input type="number" name="items[__INDEX__][unit_price]"
                    class="form-control form-control-sm text-end item-price"
                    step="0.01" min="0" value="0.00" required>
            </td>
            <td>
                <input type="number" name="items[__INDEX__][discount_amount]"
                    class="form-control form-control-sm text-end item-discount"
                    step="0.01" min="0" value="0.00">
            </td>
            <td>
                <input type="number" name="items[__INDEX__][tax_rate]"
                    class="form-control form-control-sm text-end item-tax"
                    step="0.01" min="0" max="100" value="0.00">
            </td>
            <td class="text-end align-middle fw-semibold item-line-total">
                {{ $currency }}0.00
            </td>
            <td class="text-center align-middle">
                <input type="hidden" name="items[__INDEX__][sort_order]" class="item-sort-order" value="__NUM__">
                <button type="button" class="btn btn-link text-danger p-0 btn-remove-row" title="Remove Item">
                    <i class='bx bx-trash fs-5'></i>
                </button>
            </td>
        </tr>
    </template>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currencySymbol = @json($currency);
            const appointment = document.getElementById('appointment_id');
            const client = document.getElementById('client_id');
            const staff = document.getElementById('staff_id');
            const appointmentInfo = document.getElementById('selected-appointment-info');
            const itemsTableBody = document.getElementById('items-table-body');
            const btnAddItem = document.getElementById('btn-add-item');
            const template = document.getElementById('item-row-template');

            const summarySubtotal = document.getElementById('summary-subtotal');
            const summaryDiscount = document.getElementById('summary-discount');
            const summaryTax = document.getElementById('summary-tax');
            const summaryTotal = document.getElementById('summary-total');

            function reindexRows() {
                const rows = itemsTableBody.querySelectorAll('.item-row');
                rows.forEach((row, i) => {
                    row.dataset.index = i;
                    const numEl = row.querySelector('.row-number');
                    if (numEl) numEl.textContent = i + 1;

                    row.querySelectorAll('input, select').forEach(input => {
                        const name = input.getAttribute('name');
                        if (name) {
                            input.setAttribute('name', name.replace(/items\[\d+\]/, `items[${i}]`));
                        }
                    });

                    const sortOrderInput = row.querySelector('.item-sort-order');
                    if (sortOrderInput) {
                        sortOrderInput.value = i + 1;
                    }

                    const removeBtn = row.querySelector('.btn-remove-row');
                    if (removeBtn) {
                        removeBtn.disabled = (rows.length <= 1);
                        removeBtn.style.visibility = (rows.length <= 1) ? 'hidden' : 'visible';
                    }
                });
            }

            function recalculateTotals() {
                let subtotalSum = 0;
                let discountSum = 0;
                let taxSum = 0;
                let grandTotalSum = 0;

                const rows = itemsTableBody.querySelectorAll('.item-row');
                rows.forEach(row => {
                    const qtyInput = row.querySelector('.item-qty');
                    const priceInput = row.querySelector('.item-price');
                    const discInput = row.querySelector('.item-discount');
                    const taxInput = row.querySelector('.item-tax');
                    const lineTotalEl = row.querySelector('.item-line-total');

                    const qty = Math.max(0, parseFloat(qtyInput?.value) || 0);
                    const price = Math.max(0, parseFloat(priceInput?.value) || 0);
                    const disc = Math.max(0, parseFloat(discInput?.value) || 0);
                    const taxRate = Math.min(100, Math.max(0, parseFloat(taxInput?.value) || 0));

                    const lineSubtotal = Math.round((qty * price) * 100) / 100;
                    const taxable = Math.max(0, Math.round((lineSubtotal - disc) * 100) / 100);
                    const taxAmount = Math.round((taxable * (taxRate / 100)) * 100) / 100;
                    const lineTotal = Math.round((taxable + taxAmount) * 100) / 100;

                    if (lineTotalEl) {
                        lineTotalEl.textContent = currencySymbol + lineTotal.toFixed(2);
                    }

                    subtotalSum += lineSubtotal;
                    discountSum += disc;
                    taxSum += taxAmount;
                    grandTotalSum += lineTotal;
                });

                subtotalSum = Math.round(subtotalSum * 100) / 100;
                discountSum = Math.round(discountSum * 100) / 100;
                taxSum = Math.round(taxSum * 100) / 100;
                grandTotalSum = Math.round(grandTotalSum * 100) / 100;

                if (summarySubtotal) summarySubtotal.textContent = currencySymbol + subtotalSum.toFixed(2);
                if (summaryDiscount) summaryDiscount.textContent = '-' + currencySymbol + discountSum.toFixed(2);
                if (summaryTax) summaryTax.textContent = currencySymbol + taxSum.toFixed(2);
                if (summaryTotal) summaryTotal.textContent = currencySymbol + grandTotalSum.toFixed(2);
            }

            function applyAppointment() {
                const selected = appointment?.selectedOptions?.[0];
                if (!selected || !selected.value) {
                    if (appointmentInfo) appointmentInfo.textContent = 'No appointment selected (Standalone quote)';
                    return;
                }

                if (appointmentInfo) {
                    appointmentInfo.textContent = selected.text.trim();
                }

                if (selected.dataset.clientId && client) {
                    client.value = selected.dataset.clientId;
                }
                if (selected.dataset.staffId && staff) {
                    staff.value = selected.dataset.staffId;
                }

                // Populate or update the first item row if available
                const firstRow = itemsTableBody.querySelector('.item-row');
                if (firstRow) {
                    const svcSelect = firstRow.querySelector('.item-service');
                    if (svcSelect && selected.dataset.serviceId) {
                        svcSelect.value = selected.dataset.serviceId;
                    }

                    const descInput = firstRow.querySelector('.item-description');
                    if (descInput) {
                        descInput.value = selected.dataset.serviceName || '';
                        descInput.dataset.autoFilled = 'true';
                    }

                    const priceInput = firstRow.querySelector('.item-price');
                    if (priceInput && selected.dataset.servicePrice !== undefined && selected.dataset.servicePrice !== '') {
                        priceInput.value = Number(selected.dataset.servicePrice).toFixed(2);
                    }

                    const itemStaffSelect = firstRow.querySelector('.item-staff');
                    if (itemStaffSelect && selected.dataset.staffId) {
                        itemStaffSelect.value = selected.dataset.staffId;
                    }
                }

                recalculateTotals();
            }

            function addItemRow() {
                if (!template) return;
                const clone = template.content.cloneNode(true);
                itemsTableBody.appendChild(clone);
                reindexRows();
                recalculateTotals();
            }

            // Event delegation on items table body
            itemsTableBody.addEventListener('change', (e) => {
                const target = e.target;
                if (target.classList.contains('item-service')) {
                    const selectedOpt = target.selectedOptions?.[0];
                    const row = target.closest('.item-row');
                    if (row && selectedOpt && selectedOpt.value) {
                        const descInput = row.querySelector('.item-description');
                        if (descInput && (!descInput.value || descInput.dataset.autoFilled === 'true')) {
                            descInput.value = selectedOpt.dataset.name || '';
                            descInput.dataset.autoFilled = 'true';
                        }
                        const priceInput = row.querySelector('.item-price');
                        if (priceInput && selectedOpt.dataset.price !== undefined) {
                            priceInput.value = Number(selectedOpt.dataset.price).toFixed(2);
                        }
                    }
                    recalculateTotals();
                } else if (target.classList.contains('item-qty') ||
                           target.classList.contains('item-price') ||
                           target.classList.contains('item-discount') ||
                           target.classList.contains('item-tax')) {
                    recalculateTotals();
                }
            });

            itemsTableBody.addEventListener('input', (e) => {
                const target = e.target;
                if (target.classList.contains('item-description')) {
                    target.dataset.autoFilled = 'false';
                } else if (target.classList.contains('item-qty') ||
                           target.classList.contains('item-price') ||
                           target.classList.contains('item-discount') ||
                           target.classList.contains('item-tax')) {
                    recalculateTotals();
                }
            });

            itemsTableBody.addEventListener('click', (e) => {
                const removeBtn = e.target.closest('.btn-remove-row');
                if (removeBtn) {
                    const rows = itemsTableBody.querySelectorAll('.item-row');
                    if (rows.length > 1) {
                        const row = removeBtn.closest('.item-row');
                        if (row) {
                            row.remove();
                            reindexRows();
                            recalculateTotals();
                        }
                    }
                }
            });

            btnAddItem?.addEventListener('click', addItemRow);
            appointment?.addEventListener('change', applyAppointment);

            // Initialize on load
            reindexRows();
            if (appointment && appointment.value) {
                applyAppointment();
            } else {
                recalculateTotals();
            }
        });
    </script>
@endpush
