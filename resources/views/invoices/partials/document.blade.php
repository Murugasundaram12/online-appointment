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
    $formatQty = fn ($qty) => ((float) $qty == (int) $qty) ? (string) (int) $qty : rtrim(rtrim(number_format((float) $qty, 2, '.', ''), '0'), '.');
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
                @if($appointment)
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
                @else
                    <div class="detail-grid">
                        <div class="detail-grid-row">
                            <div class="detail-grid-label">Practitioner Name</div>
                            <div class="detail-grid-value">{{ $staff->name ?? 'Not available' }}</div>
                        </div>
                        <div class="detail-grid-row">
                            <div class="detail-grid-label">Clinic Location</div>
                            <div class="detail-grid-value">{{ $location->address ?? ($location->name ?? 'Not available') }}</div>
                        </div>
                        <div class="detail-grid-row">
                            <div class="detail-grid-label">Type</div>
                            <div class="detail-grid-value">Direct Clinic Billing</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="invoice-section">
        <h2>Invoice Items</h2>
        <table class="invoice-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 4%;">#</th>
                    <th style="width: 30%;">Description</th>
                    <th style="width: 18%;">Practitioner</th>
                    <th class="text-center" style="width: 8%;">Qty</th>
                    <th class="text-right" style="width: 11%;">Rate</th>
                    <th class="text-right" style="width: 11%;">Discount</th>
                    <th class="text-right" style="width: 8%;">Tax</th>
                    <th class="text-right" style="width: 10%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoice->items ?? [] as $item)
                    @php
                        $itemDesc = $item->description ?: ($item->service?->name ?? 'Invoice item');
                        $itemStaff = $item->staff ?: $staff;
                    @endphp
                    <tr>
                        <td class="text-center text-muted">{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $itemDesc }}</strong>
                            @if($item->service && $item->service->name !== $itemDesc)
                                <div class="muted">{{ $item->service->name }}</div>
                            @elseif($loop->first && $start)
                                <div class="muted">{{ $start->format($dateFormat) }}</div>
                            @endif
                        </td>
                        <td>
                            {{ $itemStaff?->name ?? 'Not available' }}
                            @if(!empty($itemStaff?->registration_number))
                                <div class="muted" style="font-size: 0.8rem;">Reg. No: {{ $itemStaff->registration_number }}</div>
                            @endif
                        </td>
                        <td class="text-center">{{ $formatQty($item->quantity) }}</td>
                        <td class="text-right">{{ $money($item->unit_price) }}</td>
                        <td class="text-right">
                            @if((float) $item->discount_amount > 0)
                                <span class="text-danger">-{{ $money($item->discount_amount) }}</span>
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if((float) $item->tax_amount > 0)
                                {{ $money($item->tax_amount) }}
                                @if((float) $item->tax_rate > 0)
                                    <div class="muted" style="font-size: 0.75rem;">({{ (float) $item->tax_rate }}%)</div>
                                @endif
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>
                        <td class="text-right strong">{{ $money($item->line_total) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-3">No invoice items recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="invoice-bottom-table">
        <div class="payment-history-cell">
            <div class="invoice-section" style="margin-top: 0;">
                <h2>Payment History</h2>
                @if(optional($invoice->payments)->isNotEmpty())
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
                                    $methodLabel = $payment->getFormattedMethodLabel($currency);
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
                <div class="summary-row">
                    <span>Subtotal</span>
                    <strong>{{ $money($invoice->subtotal ?? $invoice->total_amount) }}</strong>
                </div>
                @if((float) ($invoice->discount_amount ?? 0) > 0)
                    <div class="summary-row">
                        <span>Discount</span>
                        <strong class="text-danger">-{{ $money($invoice->discount_amount) }}</strong>
                    </div>
                @else
                    <div class="summary-row">
                        <span>Discount</span>
                        <strong>{{ $money(0) }}</strong>
                    </div>
                @endif
                <div class="summary-row">
                    <span>Tax</span>
                    <strong>{{ $money($invoice->tax_amount ?? 0) }}</strong>
                </div>
                <div class="summary-row">
                    <span>Total</span>
                    <strong>{{ $money($invoice->total_amount) }}</strong>
                </div>
                <div class="summary-row">
                    <span>Amount Paid</span>
                    <strong>{{ $money($invoice->paid_amount) }}</strong>
                </div>
                <div class="summary-row balance">
                    <span>Balance Due</span>
                    <strong>{{ $money($balance) }}</strong>
                </div>
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
