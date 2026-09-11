@extends('emails.appointments.layout')

@php
    $isStaff = ($recipientType ?? 'client') === 'staff';
    $clientName = $appointment->client->name ?? 'Client';
    $staffName = $appointment->staff->name ?? 'Staff';
@endphp

@section('title', $isStaff ? 'Client No-Show' : 'Appointment Marked as No Show')
@section('heading', $isStaff ? 'Client No-Show' : 'Appointment Marked as No Show')

@section('content')
    @if($isStaff)
        <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hello {{ $staffName }},</p>
        <p style="margin:0;color:#4b5563;line-height:1.7;">The following appointment has been marked as No Show.</p>
    @else
        <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hello {{ $clientName }},</p>
        <p style="margin:0;color:#4b5563;line-height:1.7;">Your appointment has been marked as No Show.</p>
    @endif

    <div style="margin-top:20px;font-weight:700;font-size:15px;color:#111827;">Appointment Details</div>

    @include('emails.appointments.partials.details', [
        'recipientType' => $recipientType ?? 'client',
        'hideDuration' => true,
        'hideCost' => true
    ])

    @if(!$isStaff)
        <p style="margin:16px 0 0;color:#4b5563;line-height:1.7;">For any questions or to schedule another appointment, please contact the clinic using the details below.</p>
    @endif
@endsection
