@extends('layouts.public')

@section('title', 'Book Appointment')

@section('content')
    @php
        $businessName = \App\Models\BusinessSetting::where('key', 'business_name')->value('value') ?: config('app.name');
        $businessPhone = \App\Models\BusinessSetting::where('key', 'business_phone')->value('value');
        $businessEmail = \App\Models\BusinessSetting::where('key', 'business_email')->value('value');
    @endphp
    <section class="container py-4 py-lg-5">
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-5">
                <div class="login-panel rounded-4 h-100">
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-5">
                            <div class="brand-mark"><i class='bx bx-calendar-star'></i></div>
                            <div>
                                <div class="fw-bold fs-5">{{ $businessName }}</div>
                                <div class="text-white-50 small">Book your visit online</div>
                            </div>
                        </div>
                        <span class="trust-pill"><i class='bx bx-check-shield'></i> Secure booking request</span>
                        <h1 class="display-6 fw-bold mt-4 mb-3">Choose a time that works for you.</h1>
                        <p class="text-white-50">Select a service, provider, date, and available slot. We will reserve your appointment instantly.</p>
                    </div>
                    <div class="small text-white-50">
                        @if($businessPhone)<div><i class='bx bx-phone me-1'></i>{{ $businessPhone }}</div>@endif
                        @if($businessEmail)<div><i class='bx bx-envelope me-1'></i>{{ $businessEmail }}</div>@endif
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-lg-5">
                        @if($errors->any())
                            <div class="alert alert-danger">{{ $errors->first() }}</div>
                        @endif
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2 class="fw-bold mb-1">Book appointment</h2>
                                <p class="text-muted mb-0">Complete the steps below to confirm your booking.</p>
                            </div>
                            <span class="badge bg-light text-primary">Public booking</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            @foreach(['Location', 'Service', 'Staff', 'Time', 'Details'] as $step)
                                <span class="badge bg-light text-muted px-3 py-2">{{ $loop->iteration }}. {{ $step }}</span>
                            @endforeach
                        </div>

                        <form method="POST" action="{{ route('online-booking.store') }}" id="bookingForm">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Location</label>
                                    <select class="form-select" name="location_id" id="location_id">
                                        <option value="">Any location</option>
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Service <span class="required-mark">*</span></label>
                                    <select class="form-select" name="service_id" id="service_id" required>
                                        <option value="">Select service</option>
                                        @foreach($services as $service)
                                            <option value="{{ $service->id }}">{{ $service->name }} - ${{ number_format($service->price, 2) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Staff <span class="required-mark">*</span></label>
                                    <select class="form-select" name="staff_id" id="staff_id">
                                        <option value="">Any available staff</option>
                                        @foreach($staff as $member)
                                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date <span class="required-mark">*</span></label>
                                    <input type="date" class="form-control" id="booking_date" min="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Available time <span class="required-mark">*</span></label>
                                    <div id="slotButtons" class="d-flex flex-wrap gap-2 p-3 bg-light rounded-3">
                                        <span class="text-muted small">Select a service and date to view available slots.</span>
                                    </div>
                                    <select class="d-none" id="slot" required></select>
                                    <input type="hidden" name="start_time" id="start_time">
                                    <input type="hidden" name="end_time" id="end_time">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Name <span class="required-mark">*</span></label>
                                    <input class="form-control" name="client_name" value="{{ old('client_name') }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="client_email" value="{{ old('client_email') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Phone</label>
                                    <input class="form-control" name="client_phone" value="{{ old('client_phone') }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea class="form-control" name="notes" rows="3">{{ old('notes') }}</textarea>
                                </div>
                                <div class="col-12 d-flex justify-content-end">
                                    <button class="btn btn-primary btn-lg px-4">Confirm booking</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        const slotSelect = document.getElementById('slot');
        const slotButtons = document.getElementById('slotButtons');
        const fields = ['location_id', 'service_id', 'staff_id', 'booking_date'];
        fields.forEach((id) => document.getElementById(id).addEventListener('change', loadSlots));

        async function loadSlots() {
            const serviceId = document.getElementById('service_id').value;
            const date = document.getElementById('booking_date').value;
            document.getElementById('start_time').value = '';
            document.getElementById('end_time').value = '';
            if (!serviceId || !date) return;
            const params = new URLSearchParams({
                service_id: serviceId,
                date,
                location_id: document.getElementById('location_id').value,
                staff_id: document.getElementById('staff_id').value,
            });
            slotButtons.innerHTML = '<span class="text-muted small">Loading available slots...</span>';
            const response = await fetch(`{{ route('online-booking.slots') }}?${params.toString()}`);
            const slots = await response.json();
            slotButtons.innerHTML = slots.length ? '' : '<span class="text-muted small">No slots available for this selection.</span>';
            slots.forEach((slot) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-white';
                button.textContent = slot.label;
                button.addEventListener('click', () => {
                    slotButtons.querySelectorAll('button').forEach((item) => item.classList.remove('btn-primary'));
                    button.classList.add('btn-primary');
                    document.getElementById('start_time').value = slot.start;
                    document.getElementById('end_time').value = slot.end;
                    document.getElementById('staff_id').value = slot.staff_id;
                });
                slotButtons.appendChild(button);
            });
        }
    </script>
@endpush
