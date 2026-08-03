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
                                <span class="badge bg-light text-muted px-3 py-2" id="step-badge-{{ $loop->index }}">{{ $loop->iteration }}. {{ $step }}</span>
                            @endforeach
                        </div>

                        <form method="POST" action="{{ route('online-booking.store') }}" id="bookingForm">
                            @csrf
                            <div id="bookingError" class="alert alert-warning d-none"></div>
                            <div class="row g-3" id="bookingFields">
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
                                            <option value="{{ $service->id }}" data-category="{{ $service->category?->name ?? '' }}" data-duration="{{ $service->duration_minutes }}" data-price="{{ $service->price }}">{{ $service->name }} - ${{ number_format($service->price, 2) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Staff <span class="required-mark">*</span></label>
                                    <select class="form-select" name="staff_id" id="staff_id">
                                        <option value="">Any available staff</option>
                                        @foreach($staff as $member)
                                            <option value="{{ $member->id }}" data-category="{{ $member->category ?? '' }}" data-location="{{ $member->location_id ?? '' }}">{{ $member->name }}</option>
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
                                    <button type="button" class="btn btn-primary btn-lg px-4" id="reviewBtn">Continue to review</button>
                                </div>
                            </div>

                            <div id="reviewPanel" class="d-none">
                                <div class="rounded-3 border bg-light p-3 p-lg-4 mb-3">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <i class='bx bx-check-circle text-success fs-3'></i>
                                        <h3 class="fw-bold fs-5 mb-0">Review your booking</h3>
                                    </div>
                                    <div class="row g-3" id="reviewSummary"></div>
                                </div>
                                <div class="d-flex flex-wrap justify-content-end gap-2">
                                    <button type="button" class="btn btn-light btn-lg px-4" id="backToEditBtn">Back to edit</button>
                                    <button type="submit" class="btn btn-primary btn-lg px-4" id="confirmBookingBtn">Confirm booking</button>
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
        const slotButtons = document.getElementById('slotButtons');
        const serviceSelect = document.getElementById('service_id');
        const staffSelect = document.getElementById('staff_id');
        const locationSelect = document.getElementById('location_id');
        const fields = ['location_id', 'service_id', 'staff_id', 'booking_date'];
        let selectedSlotStaffId = null;

        function normalizeCategory(value) {
            return String(value || '').trim().toLowerCase();
        }

        function servicesMatch(staffCategory, serviceCategory) {
            if (!staffCategory) return true;
            if (!serviceCategory) return true;
            return staffCategory === serviceCategory
                || staffCategory.includes(serviceCategory)
                || serviceCategory.includes(staffCategory);
        }

        function staffMatchesSelectedFilters(opt) {
            const staffCat = normalizeCategory(opt.dataset.category);
            const svcCat = normalizeCategory(serviceSelect.selectedOptions[0] ? serviceSelect.selectedOptions[0].dataset.category : '');
            const locVal = locationSelect.value;
            const staffLoc = String(opt.dataset.location || '');
            if (locVal && staffLoc && staffLoc !== String(locVal)) return false;
            return servicesMatch(staffCat, svcCat);
        }

        function filterStaffByService() {
            Array.from(staffSelect.options).forEach(opt => {
                if (opt.value === '') { opt.disabled = false; return; }
                opt.disabled = !staffMatchesSelectedFilters(opt);
                if (opt.disabled && opt.selected) opt.selected = false;
            });
        }

        function filterServicesByStaff() {
            const staffCat = normalizeCategory(staffSelect.selectedOptions[0] ? staffSelect.selectedOptions[0].dataset.category : '');
            Array.from(serviceSelect.options).forEach(opt => {
                if (opt.value === '') { opt.disabled = false; return; }
                const svcCat = normalizeCategory(opt.dataset.category);
                opt.disabled = !servicesMatch(staffCat, svcCat);
                if (opt.disabled && opt.selected) opt.selected = false;
            });
        }

        serviceSelect.addEventListener('change', () => { filterStaffByService(); loadSlots(); });
        staffSelect.addEventListener('change', () => { filterServicesByStaff(); loadSlots(); });
        locationSelect.addEventListener('change', () => { filterStaffByService(); loadSlots(); });
        fields.forEach((id) => {
            if (id !== 'service_id' && id !== 'staff_id' && id !== 'location_id') {
                document.getElementById(id).addEventListener('change', loadSlots);
            }
        });

        async function loadSlots() {
            const serviceId = document.getElementById('service_id').value;
            const date = document.getElementById('booking_date').value;
            document.getElementById('start_time').value = '';
            document.getElementById('end_time').value = '';
            selectedSlotStaffId = null;
            updateStepBadges();
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
                    selectedSlotStaffId = String(slot.staff_id);
                    const staffOpt = staffSelect.querySelector(`option[value="${slot.staff_id}"]`);
                    if (staffOpt) {
                        staffOpt.disabled = false;
                        staffSelect.value = String(slot.staff_id);
                    }
                    updateStepBadges();
                });
                slotButtons.appendChild(button);
            });
        }

        const bookingForm = document.getElementById('bookingForm');
        const reviewBtn = document.getElementById('reviewBtn');
        const reviewPanel = document.getElementById('reviewPanel');
        const bookingFields = document.getElementById('bookingFields');
        const backToEditBtn = document.getElementById('backToEditBtn');
        const reviewSummary = document.getElementById('reviewSummary');
        const bookingError = document.getElementById('bookingError');
        const bookingDate = document.getElementById('booking_date');
        const clientName = document.querySelector('input[name="client_name"]');
        const clientEmail = document.querySelector('input[name="client_email"]');
        const clientPhone = document.querySelector('input[name="client_phone"]');
        const notesInput = document.querySelector('textarea[name="notes"]');

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, function (ch) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
            });
        }

        function showBookingError(message) {
            bookingError.textContent = message;
            bookingError.classList.remove('d-none');
            bookingError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function clearBookingError() {
            bookingError.textContent = '';
            bookingError.classList.add('d-none');
        }

        function updateStepBadges() {
            const steps = [
                locationSelect.value !== '',
                serviceSelect.value !== '',
                staffSelect.value !== '',
                document.getElementById('start_time').value !== '',
                clientName.value.trim() !== ''
            ];
            steps.forEach((done, i) => {
                const badge = document.getElementById('step-badge-' + i);
                if (!badge) return;
                badge.classList.remove('bg-light', 'text-muted', 'bg-success-subtle', 'text-success');
                badge.classList.add(done ? 'bg-success-subtle' : 'bg-light', done ? 'text-success' : 'text-muted');
            });
        }

        function reviewItem(label, value) {
            const item = document.createElement('div');
            item.className = 'col-6 col-lg-4';
            item.innerHTML = `<div class="text-muted small text-uppercase">${escapeHtml(label)}</div><div class="fw-semibold">${escapeHtml(value || '—')}</div>`;
            return item;
        }

        function buildReview() {
            const serviceOpt = serviceSelect.selectedOptions[0];
            const staffOpt = staffSelect.selectedOptions[0];
            const locationOpt = locationSelect.selectedOptions[0];
            const startValue = document.getElementById('start_time').value;

            if (startValue && selectedSlotStaffId && String(staffSelect.value) !== String(selectedSlotStaffId)) {
                document.getElementById('start_time').value = '';
                document.getElementById('end_time').value = '';
                selectedSlotStaffId = null;
                updateStepBadges();
                showBookingError('The chosen time slot is for a different provider. Please re-select an available time slot.');
                return false;
            }

            if (!serviceOpt.value) {
                showBookingError('Please select a service.');
                serviceSelect.focus();
                return false;
            }
            if (!startValue) {
                showBookingError('Please select an available time slot.');
                document.getElementById('slotButtons').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
            if (!clientName.value.trim()) {
                showBookingError('Please enter your name.');
                clientName.focus();
                return false;
            }

            const serviceName = serviceOpt.textContent.split('-')[0].trim();
            const staffName = staffOpt.value ? staffOpt.textContent.trim() : 'Any available staff';
            const locationName = locationOpt.textContent.trim();
            const dateValue = bookingDate.value;
            const formattedDate = dateValue ? new Date(dateValue + 'T00:00:00').toLocaleDateString([], { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' }) : '';
            const timeStart = startValue ? new Date(startValue).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) : '';
            const duration = serviceOpt.dataset.duration ? `${serviceOpt.dataset.duration} min` : '';
            const price = serviceOpt.dataset.price ? `$${Number(serviceOpt.dataset.price).toFixed(2)}` : '';

            reviewSummary.innerHTML = '';
            reviewSummary.appendChild(reviewItem('Location', locationName));
            reviewSummary.appendChild(reviewItem('Service', serviceName));
            reviewSummary.appendChild(reviewItem('Duration', duration));
            reviewSummary.appendChild(reviewItem('Price', price));
            reviewSummary.appendChild(reviewItem('Provider', staffName));
            reviewSummary.appendChild(reviewItem('Date', formattedDate));
            reviewSummary.appendChild(reviewItem('Time', timeStart));
            reviewSummary.appendChild(reviewItem('Name', clientName.value.trim()));
            reviewSummary.appendChild(reviewItem('Email', clientEmail.value.trim()));
            reviewSummary.appendChild(reviewItem('Phone', clientPhone.value.trim()));
            reviewSummary.appendChild(reviewItem('Notes', notesInput.value.trim()));

            return true;
        }

        reviewBtn.addEventListener('click', function () {
            clearBookingError();
            if (!bookingForm.reportValidity()) return;
            if (!buildReview()) return;
            bookingFields.classList.add('d-none');
            reviewPanel.classList.remove('d-none');
            updateStepBadges();
            reviewPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        backToEditBtn.addEventListener('click', function () {
            reviewPanel.classList.add('d-none');
            bookingFields.classList.remove('d-none');
            clearBookingError();
            updateStepBadges();
            bookingFields.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        [bookingDate, clientName, clientEmail, clientPhone, notesInput].forEach((el) => {
            if (!el) return;
            el.addEventListener('change', () => { clearBookingError(); updateStepBadges(); });
            el.addEventListener('input', () => { clearBookingError(); updateStepBadges(); });
        });

        bookingForm.addEventListener('submit', function (e) {
            if (reviewPanel.classList.contains('d-none')) {
                e.preventDefault();
                showBookingError('Please review your booking details before confirming.');
                return;
            }
            const confirmBtn = document.getElementById('confirmBookingBtn');
            if (confirmBtn && !confirmBtn.disabled) {
                window.AppButtonLoading.set(confirmBtn, 'Confirming...');
            }
        });
    </script>
@endpush
