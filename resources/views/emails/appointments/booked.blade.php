@extends('emails.appointments.layout')

@php
    $isStaff = ($recipientType ?? 'client') === 'staff';
    $clientName = $appointment->client->name ?? 'Client';
    $staffName = $appointment->staff->name ?? 'Staff';
@endphp

@section('title', $isStaff ? 'New Appointment Assigned' : 'Appointment Confirmation')
@section('heading', $isStaff ? 'New Appointment Assigned' : 'Your Appointment is Confirmed')

@section('content')
    @if($isStaff)
        <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hello {{ $staffName }},</p>
        <p style="margin:0;color:#4b5563;line-height:1.7;">A new appointment has been assigned to you.</p>
    @else
        <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hello {{ $clientName }},</p>
        <p style="margin:0;color:#4b5563;line-height:1.7;">Your appointment has been booked successfully.</p>
    @endif

    <div style="margin-top:20px;font-weight:700;font-size:15px;color:#111827;">Appointment Details</div>

    @include('emails.appointments.partials.details', ['recipientType' => $recipientType ?? 'client'])

    <p style="margin:0;color:#4b5563;line-height:1.7;">If you need to make a change, please contact us using the details below.</p>
@endsection
