@extends('emails.appointments.layout')

@php
    $isStaff = ($recipientType ?? 'client') === 'staff';
    $clientName = $appointment->client->name ?? 'Client';
    $staffName = $appointment->staff->name ?? 'Staff';
    $cancellationReason = !empty(trim($appointment->cancellation_reason ?? '')) ? $appointment->cancellation_reason : 'No reason provided.';
@endphp

@section('title', $isStaff ? 'Appointment Cancelled' : 'Appointment Cancelled')
@section('heading', $isStaff ? 'Appointment Cancelled' : 'Your Appointment Has Been Cancelled')

@section('content')
    @if($isStaff)
        <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hello {{ $staffName }},</p>
        <p style="margin:0;color:#4b5563;line-height:1.7;">The following appointment has been cancelled.</p>
    @else
        <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hello {{ $clientName }},</p>
        <p style="margin:0;color:#4b5563;line-height:1.7;">Your appointment has been cancelled.</p>
    @endif

    <div style="margin-top:20px;font-weight:700;font-size:15px;color:#111827;">Appointment Details</div>

    @include('emails.appointments.partials.details', [
        'recipientType' => $recipientType ?? 'client',
        'hideDuration' => true,
        'hideCost' => true
    ])

    <div style="margin:20px 0;padding:16px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;">
        <strong style="color:#991b1b;font-size:13px;text-transform:uppercase;letter-spacing:0.04em;">Cancellation Reason:</strong>
        <p style="margin:6px 0 0;color:#7f1d1d;line-height:1.5;">{{ $cancellationReason }}</p>
    </div>

    @if(!$isStaff)
        <p style="margin:16px 0 0;color:#4b5563;line-height:1.7;">If you have any questions or would like to rebook, please contact the clinic using the details below.</p>
    @endif
@endsection
