@php
    $rawBusinessName = $settings['business_name'] ?? config('app.name');
    $businessName = ($rawBusinessName && $rawBusinessName !== 'Laravel') ? $rawBusinessName : ($settings['business_address'] ?? 'Online Appointment Clinic');
    $businessEmail = $settings['business_email'] ?? null;
    $businessPhone = $settings['business_phone'] ?? null;
    $businessAddress = $settings['business_address'] ?? null;
    $businessWebsite = $settings['website'] ?? null;
    $taxNumber = $settings['tax_number'] ?? ($settings['gst_number'] ?? null);
    $logo = $settings['business_logo'] ?? ($settings['logo'] ?? null);
    $status = $quotation->status ?? 'draft';
    $statusLabel = ucfirst(str_replace('_', ' ', $status));
    $appointment = $quotation->appointment;
    $service = $appointment?->service;
    $staff = $quotation->staff;
    $location = $appointment?->location ?? $staff?->location;
    $locationAddress = $location?->address ?: $businessAddress;
    $locationPhone = $location?->phone ?: $businessPhone;
    $locationEmail = $location?->email ?: $businessEmail;
    $client = $quotation->client;
    $start = $appointment?->start_time;
    $end = $appointment?->end_time;
    $duration = $start && $end ? $start->diffInMinutes($end) : null;
    $money = fn ($amount) => $currency . number_format((float) $amount, 2);
    $formatQty = fn ($qty) => ((float) $qty == (int) $qty) ? (string) (int) $qty : rtrim(rtrim(number_format((float) $qty, 2, '.', ''), '0'), '.');
@endphp

<article class="quotation-document" aria-labelledby="quotation-title">
    <header class="quotation-header">
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
                    <h1 class="clinic-name">{{ $location?->name ?: (($businessName && $businessName !== 'Laravel') ? $businessName : 'Clinic Quotation') }}</h1>
                    @if($locationAddress)<div class="muted">{{ $locationAddress }}</div>@endif
                    @if($locationPhone)<div class="muted">Phone: {{ $locationPhone }}</div>@endif
                    @if($locationEmail)<div class="muted">Email: {{ $locationEmail }}</div>@endif
                    @if($businessWebsite)<div class="muted">Website: {{ $businessWebsite }}</div>@endif
                    @if($taxNumber)<div class="muted">Tax/GST: {{ $taxNumber }}</div>@endif
                </div>
            </div>
        </div>

        <div class="quotation-meta">
            <div class="quotation-kicker" id="quotation-title">QUOTATION</div>
            <div class="quotation-number">{{ $quotation->quotation_number }}</div>
            <span class="status-badge status-{{ $status }}">{{ $statusLabel }}</span>
            <dl>
                <div><dt>Issued Date:</dt> <dd>{{ optional($quotation->issued_date)->format($dateFormat) ?: 'Not available' }}</dd></div>
                <div><dt>Valid Until:</dt> <dd>{{ optional($quotation->valid_until)->format($dateFormat) ?: 'Not available' }}</dd></div>
                <div><dt>Status:</dt> <dd>{{ $statusLabel }}</dd></div>
            </dl>
        </div>
    </header>

    <section class="quotation-panels-table">
        <div class="quotation-panel-cell">
            <div class="quotation-panel">
                <h2>Recipient / Client Details</h2>
                <div class="primary-line">{{ $client->name ?? 'Not available' }}</div>
                @if($client?->email)<div class="muted">Email: {{ $client->email }}</div>@endif
                @if($client?->phone)<div class="muted">Phone: {{ $client->phone }}</div>@endif
                @if($client?->formatted_address && $client->formatted_address !== '-')
                    <div class="muted">Address: {{ $client->formatted_address }}</div>
                @elseif($client?->city)
                    <div class="muted">Address: {{ $client->city }}</div>
                @endif
                @if($client?->is_vip)<span class="mini-badge">VIP Client</span>@endif
            </div>
        </div>

        <div class="quotation-panel-cell">
            <div class="quotation-panel">
                @if($appointment)
                    <h2>Appointment &amp; Practitioner</h2>
                    <div class="detail-grid">
                        <div class="detail-grid-row">
                            <div class="detail-grid-label">Practitioner</div>
                            <div class="detail-grid-value">
                                {{ $staff->name ?? 'Not available' }}
                                @if(!empty($staff?->registration_number))
                                    <br><span class="muted" style="font-size: 0.82rem;">Reg. No: {{ $staff->registration_number }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="detail-grid-row"><div class="detail-grid-label">Service</div><div class="detail-grid-value">{{ $service->name ?? 'Service' }}</div></div>
                        <div class="detail-grid-row"><div class="detail-grid-label">Clinic Location</div><div class="detail-grid-value">{{ $location->address ?? ($location->name ?? 'Not available') }}</div></div>
                        <div class="detail-grid-row"><div class="detail-grid-label">Appointment Date</div><div class="detail-grid-value">{{ $start ? $start->format($dateFormat) : 'Not available' }}</div></div>
                        <div class="detail-grid-row"><div class="detail-grid-label">Appointment Time</div><div class="detail-grid-value">{{ $start && $end ? $start->format($timeFormat) . ' - ' . $end->format($timeFormat) : 'Not available' }}</div></div>
                        @if($duration !== null)<div class="detail-grid-row"><div class="detail-grid-label">Duration</div><div class="detail-grid-value">{{ $duration }} minutes</div></div>@endif
                        @if($appointment?->status)<div class="detail-grid-row"><div class="detail-grid-label">Status</div><div class="detail-grid-value">{{ ucfirst($appointment->status) }}</div></div>@endif
                    </div>
                @else
                    <h2>Practitioner &amp; Clinic</h2>
                    <div class="detail-grid">
                        <div class="detail-grid-row">
                            <div class="detail-grid-label">Practitioner</div>
                            <div class="detail-grid-value">
                                {{ $staff->name ?? 'Not available' }}
                                @if(!empty($staff?->registration_number))
                                    <br><span class="muted" style="font-size: 0.82rem;">Reg. No: {{ $staff->registration_number }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="detail-grid-row">
                            <div class="detail-grid-label">Clinic Location</div>
                            <div class="detail-grid-value">{{ $location->address ?? ($location->name ?? 'Not available') }}</div>
                        </div>
                        <div class="detail-grid-row">
                            <div class="detail-grid-label">Type</div>
                            <div class="detail-grid-value">Standalone Quotation</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="quotation-section">
        <h2>Quotation Items</h2>
        <div class="table-responsive-wrapper">
            <table class="quotation-table">
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
                    @forelse($quotation->items ?? [] as $item)
                        @php
                            $itemDesc = $item->description ?: ($item->service?->name ?? 'Quotation item');
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
                                @elseif((float) $item->tax_rate > 0)
                                    {{ (float) $item->tax_rate }}%
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>
                            <td class="text-right strong">{{ $money($item->line_total) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">No quotation items recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="quotation-bottom-table">
        <div class="quotation-notes-cell">
            @if(!empty($quotation->notes))
                <div class="quotation-panel" style="margin-bottom: 10px;">
                    <h2>Notes</h2>
                    <p class="mb-0" style="font-size: 11px; white-space: pre-line;">{{ $quotation->notes }}</p>
                </div>
            @endif

            @if(!empty($quotation->terms))
                <div class="quotation-panel">
                    <h2>Terms &amp; Conditions</h2>
                    <p class="mb-0" style="font-size: 11px; white-space: pre-line;">{{ $quotation->terms }}</p>
                </div>
            @endif
        </div>

        <div class="totals-card-cell">
            <aside class="totals-card" aria-label="Quotation financial summary">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <strong>{{ $money($quotation->subtotal) }}</strong>
                </div>
                @if((float) ($quotation->discount_amount ?? 0) > 0)
                    <div class="summary-row">
                        <span>Discount</span>
                        <strong class="text-danger">-{{ $money($quotation->discount_amount) }}</strong>
                    </div>
                @else
                    <div class="summary-row">
                        <span>Discount</span>
                        <strong>{{ $money(0) }}</strong>
                    </div>
                @endif
                <div class="summary-row">
                    <span>Tax</span>
                    <strong>{{ $money($quotation->tax_amount ?? 0) }}</strong>
                </div>
                <div class="summary-row total-row">
                    <span>Total</span>
                    <strong>{{ $money($quotation->total_amount) }}</strong>
                </div>
                <div class="quotation-status-box">
                    <span>Status</span>
                    <strong class="status-badge status-{{ $status }}">{{ $statusLabel }}</strong>
                </div>
            </aside>
        </div>
    </section>

    <footer class="quotation-footer">
        <strong>Thank you for choosing {{ $location?->name ?: $businessName }}.</strong>
        <div>
            @if($locationPhone || $locationEmail || $businessPhone || $businessEmail)
                For enquiries regarding this quotation, contact
                @if($locationPhone ?: $businessPhone) {{ $locationPhone ?: $businessPhone }} @endif
                @if(($locationPhone ?: $businessPhone) && ($locationEmail ?: $businessEmail)) or @endif
                @if($locationEmail ?: $businessEmail) {{ $locationEmail ?: $businessEmail }} @endif.
            @endif
            This is an official clinic quotation.
        </div>
        <div class="generated-at">Generated {{ now()->format($dateFormat . ' ' . $timeFormat) }}</div>
    </footer>
</article>
