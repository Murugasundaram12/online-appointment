@extends('emails.appointments.layout')

@section('title', 'Appointment Confirmed')
@section('heading', 'Your appointment is confirmed')

@section('content')
    <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hello {{ $appointment->client->name ?? 'there' }},</p>
    <p style="margin:0;color:#4b5563;line-height:1.7;">Your appointment has been confirmed. We look forward to seeing you.</p>

    @include('emails.appointments.partials.details')
@endsection
