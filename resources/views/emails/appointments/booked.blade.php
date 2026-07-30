@extends('emails.appointments.layout')

@section('title', 'Appointment Confirmation')
@section('heading', 'Your appointment is confirmed')

@section('content')
    <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hello {{ $appointment->client->name ?? 'there' }},</p>
    <p style="margin:0;color:#4b5563;line-height:1.7;">Your appointment has been booked successfully. Please arrive 10 minutes before your scheduled appointment.</p>

    @include('emails.appointments.partials.details')

    <p style="margin:0;color:#4b5563;line-height:1.7;">If you need to make a change, please contact us using the details below.</p>
@endsection
