@extends('emails.appointments.layout')

@php
    $isStaff = ($recipientType ?? 'client') === 'staff';
    $clientName = $appointment->client->name ?? 'Client';
    $staffName = $appointment->staff->name ?? 'Staff';
@endphp

@section('title', $isStaff ? 'Appointment Completed' : 'Thank You for Your Visit')
@section('heading', $isStaff ? 'Appointment Completed' : 'Thank You for Your Visit')

@section('content')
    @if($isStaff)
        <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hello {{ $staffName }},</p>
        <p style="margin:0;color:#4b5563;line-height:1.7;">The following appointment has been marked as completed.</p>
    @else
        <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hello {{ $clientName }},</p>
        <p style="margin:0;color:#4b5563;line-height:1.7;">Thank you for visiting us. We hope your appointment was helpful and comfortable.</p>
    @endif

    <div style="margin-top:20px;font-weight:700;font-size:15px;color:#111827;">Appointment Details</div>

    @include('emails.appointments.partials.details', ['recipientType' => $recipientType ?? 'client'])

    @if(!$isStaff)
        <div style="padding:18px;border-radius:10px;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;line-height:1.6;">
            We appreciate your feedback. Please contact us anytime if you have questions after your visit or would like to book a future appointment.
        </div>
    @endif
@endsection
