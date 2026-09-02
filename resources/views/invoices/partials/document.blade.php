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
            <div class="clinic-brand-row">
                @if($logo)
                    <img src="{{ $logo }}" alt="{{ $businessName }}" class="clinic-logo">
                @else
                    <div class="clinic-mark" aria-hidden="true">{{ strtoupper(substr($businessName, 0, 2)) }}</div>
                @endif
                <div>
                    <h1 class="clinic-name">{{ ($businessName && $businessName !== 'Laravel') ? $businessName : ($location?->name ?: 'Clinic Invoice') }}</h1>
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

    <section class="invoice-panels">
        <div class="invoice-panel">
            <h2>Bill To / Patient Details</h2>
            <div class="primary-line">{{ $client->name ?? 'Not available' }}</div>
            @if($client?->email)<div class="muted">{{ $client->email }}</div>@endif
            @if($client?->phone)<div class="muted">{{ $client->phone }}</div>@endif
            @if($client?->city)<div class="muted">{{ $client->city }}</div>@endif
            @if($client?->client_since)<div class="muted">Patient since {{ $client->client_since->format($dateFormat) }}</div>@endif
            @if($client?->is_vip)<span class="mini-badge">VIP Patient</span>@endif
        </div>

        <div class="invoice-panel">
            <h2>Appointment Details</h2>
            <div class="detail-grid">
                <div><span>Practitioner Name</span><strong>{{ $staff->name ?? 'Not available' }}</strong></div>
                <div><span>Service</span><strong>{{ $service->name ?? 'Service' }}</strong></div>
                <div><span>Clinic Location</span><strong>{{ $location->name ?? 'Not available' }}</strong></div>
                <div><span>Appointment Date</span><strong>{{ $start ? $start->format($dateFormat) : 'Not available' }}</strong></div>
                <div><span>Appointment Time</span><strong>{{ $start && $end ? $start->format($timeFormat) . ' - ' . $end->format($timeFormat) : 'Not available' }}</strong></div>
                @if($duration !== null)<div><span>Duration</span><strong>{{ $duration }} minutes</strong></div>@endif
                @if($appointment?->status)<div><span>Status</span><strong>{{ ucfirst($appointment->status) }}</strong></div>@endif
            </div>
        </div>
    </section>

    <section class="invoice-section">
        <h2>Invoice Items</h2>
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Practitioner Name</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Rate</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>{{ $service->name ?? 'Clinic service' }}</strong>
                        @if($start)<div class="muted">{{ $start->format($dateFormat) }}</div>@endif
                    </td>
                    <td>{{ $staff->name ?? 'Not available' }}</td>
                    <td class="text-center">1</td>
                    <td class="text-right">{{ $money($invoice->total_amount) }}</td>
                    <td class="text-right strong">{{ $money($invoice->total_amount) }}</td>
                </tr>
            </tbody>
        </table>
    </section>

    <section class="invoice-bottom-grid">
        <div class="invoice-section payment-history">
            <h2>Payment History</h2>
            @if($invoice->payments->isNotEmpty())
                <table class="invoice-table compact">
                    <thead>
                        <tr>
                            <th>Payment Date</th>
                            <th>Method</th>
                            <th>Transaction ID</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->payments as $payment)
                            <tr>
                                <td>{{ optional($payment->payment_date)->format($dateFormat) ?: 'Not available' }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                <td>{{ $payment->transaction_id ?: '-' }}</td>
                                <td class="text-right">{{ $money($payment->amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted empty-note">No payments recorded.</p>
            @endif
        </div>

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
    </section>

    @if($invoiceNotes || $invoiceFooter)
        <section class="invoice-notes">
            <h2>Notes</h2>
            @if($invoiceNotes)<p>{{ $invoiceNotes }}</p>@endif
            @if($invoiceFooter)<p>{{ $invoiceFooter }}</p>@endif
        </section>
    @endif

    <footer class="invoice-footer">
        <strong>Thank you for choosing {{ $businessName }}.</strong>
        <div>
            @if($businessPhone || $businessEmail)
                For billing enquiries, contact
                @if($businessPhone) {{ $businessPhone }} @endif
                @if($businessPhone && $businessEmail) or @endif
                @if($businessEmail) {{ $businessEmail }} @endif.
            @endif
            This is a system-generated invoice.
        </div>
        <div class="generated-at">Generated {{ now()->format($dateFormat . ' ' . $timeFormat) }}</div>
    </footer>
</article>
