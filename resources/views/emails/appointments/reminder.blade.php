@extends('emails.appointments.layout')

@section('title', 'Appointment Reminder')
@section('heading', 'Appointment reminder')

@section('content')
    <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hello {{ $appointment->client->name ?? 'there' }},</p>
    <p style="margin:0;color:#4b5563;line-height:1.7;">This is a friendly reminder that your appointment is scheduled for <strong>tomorrow</strong>. Please arrive 10 minutes before your scheduled appointment.</p>

    @include('emails.appointments.partials.details')

    <p style="margin:16px 0 0;color:#4b5563;line-height:1.7;">If you need to make a change, please contact us using the details below.</p>
@endsection