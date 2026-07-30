@extends('emails.appointments.layout')

@section('title', 'Appointment Completed')
@section('heading', 'Thank you for your visit')

@section('content')
    <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hello {{ $appointment->client->name ?? 'there' }},</p>
    <p style="margin:0;color:#4b5563;line-height:1.7;">Thank you for visiting us. We hope your appointment was helpful and comfortable.</p>

    @include('emails.appointments.partials.details')

    <div style="padding:18px;border-radius:10px;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;line-height:1.6;">
        We appreciate your feedback. Please contact us anytime if you have questions after your visit or would like to book a future appointment.
    </div>
@endsection
