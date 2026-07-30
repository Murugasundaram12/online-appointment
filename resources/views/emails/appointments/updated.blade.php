@extends('emails.appointments.layout')

@section('title', 'Appointment Updated')
@section('heading', 'Your appointment has been updated')

@section('content')
    @php
        $oldStart = $previous?->start_time?->copy()->timezone($business['timezone'] ?? config('app.timezone'));
        $oldEnd = $previous?->end_time?->copy()->timezone($business['timezone'] ?? config('app.timezone'));
    @endphp
    <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hello {{ $appointment->client->name ?? 'there' }},</p>
    <p style="margin:0;color:#4b5563;line-height:1.7;">Your appointment details have changed. Please review the updated schedule below.</p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:22px 0;">
        <tr>
            <td style="padding:18px;border:1px solid #e5e7eb;border-radius:10px;background:#f9fafb;">
                <div style="font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">Old Schedule</div>
                <div style="margin-top:8px;font-weight:700;">{{ $oldStart ? $oldStart->format('M j, Y g:i A') : '-' }}{{ $oldEnd ? ' - ' . $oldEnd->format('g:i A') : '' }}</div>
                <div style="margin-top:6px;color:#4b5563;">{{ $previous?->staff->name ?? '-' }} • {{ $previous?->service->name ?? '-' }} • {{ $previous?->location->name ?? 'To be confirmed' }}</div>
            </td>
        </tr>
        <tr><td style="text-align:center;padding:12px 0;color:#4f46e5;font-size:22px;">↓</td></tr>
        <tr>
            <td style="padding:18px;border:1px solid #c7d2fe;border-radius:10px;background:#eef2ff;">
                <div style="font-size:12px;color:#4f46e5;text-transform:uppercase;letter-spacing:.04em;">New Schedule</div>
                <div style="margin-top:8px;font-weight:700;">{{ $appointment->start_time?->copy()->timezone($business['timezone'] ?? config('app.timezone'))->format('M j, Y g:i A') }} - {{ $appointment->end_time?->copy()->timezone($business['timezone'] ?? config('app.timezone'))->format('g:i A') }}</div>
                <div style="margin-top:6px;color:#4b5563;">{{ $appointment->staff->name ?? '-' }} • {{ $appointment->service->name ?? '-' }} • {{ $appointment->location->name ?? 'To be confirmed' }}</div>
            </td>
        </tr>
    </table>

    @include('emails.appointments.partials.details')
@endsection
