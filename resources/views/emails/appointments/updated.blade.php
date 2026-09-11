@extends('emails.appointments.layout')

@php
    $isStaff = ($recipientType ?? 'client') === 'staff';
    $clientName = $appointment->client->name ?? 'Client';
    $staffName = $appointment->staff->name ?? 'Staff';
    $oldStart = $previousStart ?? ($previous?->start_time ? $previous->start_time->copy() : null);
    $oldEnd = $previousEnd ?? ($previous?->end_time ? $previous->end_time->copy() : null);
    $newStart = $appointmentStart ?? ($appointment->start_time ? $appointment->start_time->copy() : null);
    $newEnd = $appointmentEnd ?? ($appointment->end_time ? $appointment->end_time->copy() : null);
@endphp

@section('title', $isStaff ? 'Appointment Updated' : 'Appointment Updated')
@section('heading', $isStaff ? 'Appointment Updated' : 'Your Appointment Has Been Updated')

@section('content')
    @if($isStaff)
        <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hello {{ $staffName }},</p>
        <p style="margin:0;color:#4b5563;line-height:1.7;">The following appointment details have been updated. Please review the updated schedule below.</p>
    @else
        <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hello {{ $clientName }},</p>
        <p style="margin:0;color:#4b5563;line-height:1.7;">Your appointment details have changed. Please review the updated schedule below.</p>
    @endif

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:22px 0;">
        <tr>
            <td style="padding:18px;border:1px solid #e5e7eb;border-radius:10px;background:#f9fafb;">
                <div style="font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">Old Schedule</div>
                <div style="margin-top:8px;font-weight:700;">{{ $oldStart ? $oldStart->format('M j, Y g:i A') : '-' }}{{ $oldEnd ? ' – ' . $oldEnd->format('g:i A') : '' }}</div>
                <div style="margin-top:6px;color:#4b5563;">
                    @if($isStaff)
                        {{ $previous?->client->name ?? 'Client' }} • {{ $previous?->service->name ?? '-' }} • {{ $previous?->location->name ?? 'To be confirmed' }}
                    @else
                        {{ $previous?->staff->name ?? '-' }} • {{ $previous?->service->name ?? '-' }} • {{ $previous?->location->name ?? 'To be confirmed' }}
                    @endif
                </div>
            </td>
        </tr>
        <tr><td style="text-align:center;padding:12px 0;color:#4f46e5;font-size:22px;">↓</td></tr>
        <tr>
            <td style="padding:18px;border:1px solid #c7d2fe;border-radius:10px;background:#eef2ff;">
                <div style="font-size:12px;color:#4f46e5;text-transform:uppercase;letter-spacing:.04em;">New Schedule</div>
                <div style="margin-top:8px;font-weight:700;">{{ $newStart ? $newStart->format('M j, Y g:i A') : '-' }}{{ $newEnd ? ' – ' . $newEnd->format('g:i A') : '' }}</div>
                <div style="margin-top:6px;color:#4b5563;">
                    @if($isStaff)
                        {{ $appointment->client->name ?? 'Client' }} • {{ $appointment->service->name ?? '-' }} • {{ $appointment->location->name ?? 'To be confirmed' }}
                    @else
                        {{ $appointment->staff->name ?? '-' }} • {{ $appointment->service->name ?? '-' }} • {{ $appointment->location->name ?? 'To be confirmed' }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div style="margin-top:20px;font-weight:700;font-size:15px;color:#111827;">Appointment Details</div>

    @include('emails.appointments.partials.details', ['recipientType' => $recipientType ?? 'client'])
@endsection
