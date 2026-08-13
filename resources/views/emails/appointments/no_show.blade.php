@extends('emails.appointments.layout')

@section('title', 'Appointment No Show')
@section('heading', 'Your appointment was marked as no show')

@section('content')
    <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hello {{ $appointment->client->name ?? 'there' }},</p>
    <p style="margin:0;color:#4b5563;line-height:1.7;">Our records show that this appointment was not attended. The details below are for your records.</p>

    @include('emails.appointments.partials.details')
@endsection
