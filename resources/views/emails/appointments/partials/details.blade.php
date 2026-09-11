@php
    $start = $appointmentStart ?? ($appointment->start_time ? $appointment->start_time->copy() : null);
    $end = $appointmentEnd ?? ($appointment->end_time ? $appointment->end_time->copy() : null);
    $duration = $durationMinutes ?? ($start && $end ? $start->diffInMinutes($end) : null);
    $isStaff = ($recipientType ?? 'client') === 'staff';
    $showDuration = !($hideDuration ?? false);
    $showCost = !$isStaff && !($hideCost ?? false);
    $displayStatus = ucwords(str_replace('_', ' ', $appointment->status ?? 'booked'));
@endphp
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin:22px 0;">
    @if($isStaff)
        <tr>
            <td style="padding:14px 18px;background:#f9fafb;color:#6b7280;font-size:13px;width:38%;">Client</td>
            <td style="padding:14px 18px;font-weight:600;">{{ $appointment->client->name ?? 'Client' }}</td>
        </tr>
        <tr>
            <td style="padding:14px 18px;background:#f9fafb;color:#6b7280;font-size:13px;">Booking Reference</td>
            <td style="padding:14px 18px;font-weight:600;">{{ $reference }}</td>
        </tr>
    @else
        <tr>
            <td style="padding:14px 18px;background:#f9fafb;color:#6b7280;font-size:13px;width:38%;">Booking Reference</td>
            <td style="padding:14px 18px;font-weight:600;">{{ $reference }}</td>
        </tr>
        <tr>
            <td style="padding:14px 18px;background:#f9fafb;color:#6b7280;font-size:13px;">Staff</td>
            <td style="padding:14px 18px;">{{ $appointment->staff->name ?? '-' }}</td>
        </tr>
    @endif
    <tr>
        <td style="padding:14px 18px;background:#f9fafb;color:#6b7280;font-size:13px;">Service</td>
        <td style="padding:14px 18px;">{{ $appointment->service->name ?? '-' }}</td>
    </tr>
    <tr>
        <td style="padding:14px 18px;background:#f9fafb;color:#6b7280;font-size:13px;">Location</td>
        <td style="padding:14px 18px;">{{ $appointment->location->name ?? 'To be confirmed' }}</td>
    </tr>
    <tr>
        <td style="padding:14px 18px;background:#f9fafb;color:#6b7280;font-size:13px;">Date</td>
        <td style="padding:14px 18px;">{{ $start ? $start->format('l, F j, Y') : '-' }}</td>
    </tr>
    <tr>
        <td style="padding:14px 18px;background:#f9fafb;color:#6b7280;font-size:13px;">Time</td>
        <td style="padding:14px 18px;">{{ $start && $end ? $start->format('g:i A') . ' – ' . $end->format('g:i A') : '-' }}</td>
    </tr>
    @if($showDuration)
        <tr>
            <td style="padding:14px 18px;background:#f9fafb;color:#6b7280;font-size:13px;">Duration</td>
            <td style="padding:14px 18px;">{{ $duration !== null ? $duration . ' minutes' : '-' }}</td>
        </tr>
    @endif
    @if($showCost)
        <tr>
            <td style="padding:14px 18px;background:#f9fafb;color:#6b7280;font-size:13px;">Cost</td>
            <td style="padding:14px 18px;font-weight:600;">${{ number_format((float) ($appointment->service->price ?? 0), 2) }}</td>
        </tr>
    @endif
    <tr>
        <td style="padding:14px 18px;background:#f9fafb;color:#6b7280;font-size:13px;">Status</td>
        <td style="padding:14px 18px;">{{ $displayStatus }}</td>
    </tr>
    @if(!empty($appointment->notes))
        <tr>
            <td style="padding:14px 18px;background:#f9fafb;color:#6b7280;font-size:13px;">Notes</td>
            <td style="padding:14px 18px;">{{ $appointment->notes }}</td>
        </tr>
    @endif
</table>
