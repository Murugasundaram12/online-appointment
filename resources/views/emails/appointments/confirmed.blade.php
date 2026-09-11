@extends('emails.appointments.layout')

@php
    $isStaff = ($recipientType ?? 'client') === 'staff';
    $clientName = $appointment->client->name ?? 'Client';
    $staffName = $appointment->staff->name ?? 'Staff';
@endphp

@section('title', $isStaff ? 'Appointment Confirmed' : 'Appointment Confirmed')
@section('heading', $isStaff ? 'Appointment Confirmed' : 'Your Appointment is Confirmed')

@section('content')
    @if($isStaff)
        <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hello {{ $staffName }},</p>
        <p style="margin:0;color:#4b5563;line-height:1.7;">The following appointment has been confirmed.</p>
    @else
        <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hello {{ $clientName }},</p>
        <p style="margin:0;color:#4b5563;line-height:1.7;">Your appointment has been confirmed. We look forward to seeing you.</p>
    @endif

    <div style="margin-top:20px;font-weight:700;font-size:15px;color:#111827;">Appointment Details</div>

    @include('emails.appointments.partials.details', ['recipientType' => $recipientType ?? 'client'])

    <p style="margin:0;color:#4b5563;line-height:1.7;">If you need to make a change, please contact us using the details below.</p>
@endsection
