@extends('layouts.public')

@section('title', 'Booking Confirmation')

@section('content')
    <div class="container py-5">
        <div class="card shadow-sm border-0 rounded-4 mx-auto" style="max-width:720px">
            <div class="card-body p-5 text-center">
                <div class="kpi-icon mx-auto mb-3" style="background:var(--success-soft);color:var(--success);width:64px;height:64px;font-size:2rem">
                    <i class='bx bx-check'></i>
                </div>
                <h1 class="fw-bold mb-2">Your appointment is booked</h1>
                <p class="text-muted mb-4">Booking reference #{{ $appointment->id }}</p>
                <div class="text-start bg-light rounded-4 p-4">
                    <p class="mb-2"><strong>Client:</strong> {{ optional($appointment->client)->name }}</p>
                    <p class="mb-2"><strong>Service:</strong> {{ optional($appointment->service)->name }}</p>
                    <p class="mb-2"><strong>Staff:</strong> {{ optional($appointment->staff)->name }}</p>
                    <p class="mb-2"><strong>Location:</strong> {{ optional($appointment->location)->name ?: 'Any location' }}</p>
                    <p class="mb-2"><strong>Time:</strong> {{ $appointment->start_time->format('M j, Y g:i A') }} - {{ $appointment->end_time->format('g:i A') }}</p>
                    <p class="mb-0"><strong>Status:</strong> {{ ucfirst($appointment->status) }}</p>
                </div>
                <a href="{{ route('online-booking.index') }}" class="btn btn-primary mt-4">Book another appointment</a>
            </div>
        </div>
    </div>
@endsection
