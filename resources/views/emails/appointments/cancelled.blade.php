@extends('emails.appointments.layout')

@section('title', 'Appointment Cancelled')
@section('heading', 'Your appointment has been cancelled')

@section('content')
    <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hello {{ $appointment->client->name ?? 'there' }},</p>
    <p style="margin:0;color:#4b5563;line-height:1.7;">Your appointment has been cancelled. If you would like to rebook, please contact us and we will help you find a suitable time.</p>

    @include('emails.appointments.partials.details')
@endsection
