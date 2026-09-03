@php
    $rawBusinessName = $settings['business_name'] ?? config('app.name');
    $businessName = ($rawBusinessName && $rawBusinessName !== 'Laravel') ? $rawBusinessName : ($settings['business_address'] ?? 'Online Appointment Clinic');
    $businessEmail = $settings['business_email'] ?? null;
    $businessPhone = $settings['business_phone'] ?? null;
    $businessAddress = $settings['business_address'] ?? null;
    $businessWebsite = $settings['website'] ?? null;
    $taxNumber = $settings['tax_number'] ?? ($settings['gst_number'] ?? null);
    $invoiceNotes = $settings['invoice_notes'] ?? null;
    $invoiceFooter = $settings['invoice_footer'] ?? null;
    $logo = $settings['business_logo'] ?? ($settings['logo'] ?? null);
    $status = $invoice->status ?? 'outstanding';
    $statusLabel = ucfirst(str_replace('_', ' ', $status));
    $appointment = $invoice->appointment;
    $service = $appointment?->service;
    $staff = $invoice->staff;
    $location = $appointment?->location ?? $staff?->location;
    $locationAddress = $location?->address ?: $businessAddress;
    $locationPhone = $location?->phone ?: $businessPhone;
    $locationEmail = $location?->email ?: $businessEmail;
    $client = $invoice->client;
    $start = $appointment?->start_time;
    $end = $appointment?->end_time;
    $duration = $start && $end ? $start->diffInMinutes($end) : null;
    $money = fn ($amount) => $currency . number_format((float) $amount, 2);
@endphp

<article class="invoice-document" aria-labelledby="invoice-title">
    <header class="invoice-header">
        <div class="clinic-block">
            <div class="clinic-brand-table">
                @if($logo)
                    <div class="clinic-brand-cell" style="width: auto;">
                        <img src="{{ $logo }}" alt="{{ $businessName }}" class="clinic-logo">
                    </div>
                @else
                    <div class="clinic-brand-cell" style="width: 50px;">
                        <div class="clinic-mark" aria-hidden="true">{{ strtoupper(substr($location?->name ?: $businessName, 0, 2)) }}</div>
                    </div>
                @endif
                <div class="clinic-brand-cell">
                    <h1 class="clinic-name">{{ $location?->name ?: (($businessName && $businessName !== 'Laravel') ? $businessName : 'Clinic Invoice') }}</h1>
                    @if($locationAddress)<div class="muted">{{ $locationAddress }}</div>@endif
                    @if($locationPhone)<div class="muted">Phone: {{ $locationPhone }}</div>@endif
                    @if($locationEmail)<div class="muted">Email: {{ $locationEmail }}</div>@endif
                    @if($businessWebsite)<div class="muted">Website: {{ $businessWebsite }}</div>@endif
                    @if($taxNumber)<div class="muted">Tax/GST: {{ $taxNumber }}</div>@endif
                </div>
            </div>
        </div>

        <div class="invoice-meta">
            <div class="invoice-kicker" id="invoice-title">INVOICE</div>
            <div class="invoice-number">{{ $invoice->invoice_number }}</div>
            <span class="status-badge status-{{ $status }}">{{ $statusLabel }}</span>
            <dl>
                <div><dt>Issued</dt><dd>{{ optional($invoice->issued_date)->format($dateFormat) ?: 'Not available' }}</dd></div>
                <div><dt>Due</dt><dd>{{ optional($invoice->due_date)->format($dateFormat) ?: 'Not available' }}</dd></div>
            </dl>
        </div>
    </header>

    <section class="invoice-panels-table">
        <div class="invoice-panel-cell">
            <div class="invoice-panel">
                <h2>Bill To / Patient Details</h2>
                <div class="primary-line">{{ $client->name ?? 'Not available' }}</div>
                @if($client?->email)<div class="muted">{{ $client->email }}</div>@endif
                @if($client?->phone)<div class="muted">{{ $client->phone }}</div>@endif
                @if($client?->city)<div class="muted">{{ $client->city }}</div>@endif
                @if($client?->is_vip)<span class="mini-badge">VIP Patient</span>@endif
            </div>
        </div>

        <div class="invoice-panel-cell">
            <div class="invoice-panel">
                <h2>Appointment Details</h2>
                <div class="detail-grid">
                    <div class="detail-grid-row">
                        <div class="detail-grid-label">Practitioner Name</div>
                        <div class="detail-grid-value">
                            {{ $staff->name ?? 'Not available' }}
                            @if(!empty($staff?->registration_number))
                                <br><span class="muted" style="font-size: 0.82rem;">Reg. No: {{ $staff->registration_number }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="detail-grid-row"><div class="detail-grid-label">Service</div><div class="detail-grid-value">{{ $service->name ?? 'Service' }}</div></div>
                    <div class="detail-grid-row"><div class="detail-grid-label">Clinic Location</div><div class="detail-grid-value">{{ $location->address ?? 'Not available' }}</div></div>
                    <div class="detail-grid-row"><div class="detail-grid-label">Appointment Date</div><div class="detail-grid-value">{{ $start ? $start->format($dateFormat) : 'Not available' }}</div></div>
                    <div class="detail-grid-row"><div class="detail-grid-label">Appointment Time</div><div class="detail-grid-value">{{ $start && $end ? $start->format($timeFormat) . ' - ' . $end->format($timeFormat) : 'Not available' }}</div></div>
                    @if($duration !== null)<div class="detail-grid-row"><div class="detail-grid-label">Duration</div><div class="detail-grid-value">{{ $duration }} minutes</div></div>@endif
                    @if($appointment?->status)<div class="detail-grid-row"><div class="detail-grid-label">Status</div><div class="detail-grid-value">{{ ucfirst($appointment->status) }}</div></div>@endif
                </div>
            </div>
        </div>
    </section>

    <section class="invoice-section">
        <h2>Invoice Items</h2>
        <table class="invoice-table">
            <thead>
                <tr>
                    <th style="width: 38%;">Description</th>
                    <th style="width: 28%;">Practitioner Name</th>
                    <th class="text-center" style="width: 10%;">Qty</th>
                    <th class="text-right" style="width: 12%;">Rate</th>
                    <th class="text-right" style="width: 12%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>{{ $service->name ?? 'Clinic service' }}</strong>
                        @if($start)<div class="muted">{{ $start->format($dateFormat) }}</div>@endif
                    </td>
                    <td>
                        {{ $staff->name ?? 'Not available' }}
                        @if(!empty($staff?->registration_number))
                            <div class="muted" style="font-size: 0.8rem;">Reg. No: {{ $staff->registration_number }}</div>
                        @endif
                    </td>
                    <td class="text-center">1</td>
                    <td class="text-right">{{ $money($invoice->total_amount) }}</td>
                    <td class="text-right strong">{{ $money($invoice->total_amount) }}</td>
                </tr>
            </tbody>
        </table>
    </section>

    <section class="invoice-bottom-table">
        <div class="payment-history-cell">
            <div class="invoice-section" style="margin-top: 0;">
                <h2>Payment History</h2>
                @if($invoice->payments->isNotEmpty())
                    <table class="invoice-table compact">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Ref / Txn ID</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->payments as $payment)
                                @php
                                    $methodLabel = match($payment->payment_method) {
                                        'cash' => 'Cash',
                                        'card' => 'Card' . ($payment->card_brand ? ' • ' . $payment->card_brand : '') . ($payment->card_last_four ? ' • ****' . $payment->card_last_four : ''),
                                        'e_transfer' => 'E-Transfer' . ($payment->e_transfer_reference ? ' • ' . $payment->e_transfer_reference : ''),
                                        'insurance' => 'Insurance' . ($payment->insuranceCompany ? ' • ' . $payment->insuranceCompany->name : ''),
                                        'cash_card' => 'Cash + Card (Cash: ' . $money($payment->primary_amount) . ' | Card: ' . ($payment->card_brand ?: 'Card') . ($payment->card_last_four ? ' ****' . $payment->card_last_four : '') . ' — ' . $money($payment->secondary_amount) . ')',
                                        'card_e_transfer' => 'Card + E-Transfer (Card: ' . ($payment->card_brand ?: 'Card') . ($payment->card_last_four ? ' ****' . $payment->card_last_four : '') . ' — ' . $money($payment->primary_amount) . ' | E-Transfer: ' . ($payment->e_transfer_reference ?: 'ETR') . ' — ' . $money($payment->secondary_amount) . ')',
                                        'cash_e_transfer' => 'Cash + E-Transfer (Cash: ' . $money($payment->primary_amount) . ' | E-Transfer: ' . ($payment->e_transfer_reference ?: 'ETR') . ' — ' . $money($payment->secondary_amount) . ')',
                                        default => ucfirst(str_replace('_', ' ', $payment->payment_method))
                                    };
                                    $refId = $payment->transaction_reference ?: ($payment->transaction_id ?: ($payment->e_transfer_reference ?: ($payment->claim_reference ?: '-')));
                                @endphp
                                <tr>
                                    <td>{{ optional($payment->payment_date)->format($dateFormat) ?: 'Not available' }}</td>
                                    <td>{{ $methodLabel }}</td>
                                    <td>{{ $refId }}</td>
                                    <td class="text-right">{{ $money($payment->amount) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="muted empty-note">No payments recorded.</p>
                @endif
            </div>
        </div>

        <div class="totals-card-cell">
            <aside class="totals-card" aria-label="Invoice financial summary">
                <div class="summary-row"><span>Subtotal</span><strong>{{ $money($invoice->total_amount) }}</strong></div>
                <div class="summary-row"><span>Tax</span><strong>{{ $money(0) }}</strong></div>
                <div class="summary-row"><span>Discount</span><strong>{{ $money(0) }}</strong></div>
                <div class="summary-row"><span>Total</span><strong>{{ $money($invoice->total_amount) }}</strong></div>
                <div class="summary-row"><span>Amount Paid</span><strong>{{ $money($invoice->paid_amount) }}</strong></div>
                <div class="summary-row balance"><span>Balance Due</span><strong>{{ $money($balance) }}</strong></div>
                <div class="payment-status-box">
                    <span>Payment Status</span>
                    <strong>{{ $statusLabel }}</strong>
                </div>
            </aside>
        </div>
    </section>

    @if($invoiceNotes || $invoiceFooter)
        <section class="invoice-notes">
            <h2>Notes</h2>
            @if($invoiceNotes)<p>{{ $invoiceNotes }}</p>@endif
            @if($invoiceFooter)<p>{{ $invoiceFooter }}</p>@endif
        </section>
    @endif

    <footer class="invoice-footer">
        <strong>Thank you for choosing {{ $location?->name ?: $businessName }}.</strong>
        <div>
            @if($locationPhone || $locationEmail || $businessPhone || $businessEmail)
                For billing enquiries, contact
                @if($locationPhone ?: $businessPhone) {{ $locationPhone ?: $businessPhone }} @endif
                @if(($locationPhone ?: $businessPhone) && ($locationEmail ?: $businessEmail)) or @endif
                @if($locationEmail ?: $businessEmail) {{ $locationEmail ?: $businessEmail }} @endif.
            @endif
            This is a system-generated invoice.
        </div>
        <div class="generated-at">Generated {{ now()->format($dateFormat . ' ' . $timeFormat) }}</div>
    </footer>
</article>
