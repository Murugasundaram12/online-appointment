@php
    $client = $client ?? null;
    $idPrefix = $idPrefix ?? '';
    $fieldId = fn (string $field) => $idPrefix . str_replace('_', '-', $field);
    $value = fn (string $field, $default = null) => old($field, $client?->{$field} ?? $default);
    $dateValue = fn (string $field, $default = null) => ($value($field, $default) instanceof \DateTimeInterface)
        ? $value($field, $default)->format('Y-m-d')
        : $value($field, $default);
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="{{ $fieldId('first_name') }}" class="form-label">First Name <span class="required-mark">*</span></label>
        <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="{{ $fieldId('first_name') }}" name="first_name" value="{{ $value('first_name') }}" placeholder="Enter first name" required>
        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="{{ $fieldId('last_name') }}" class="form-label">Last Name <span class="required-mark">*</span></label>
        <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="{{ $fieldId('last_name') }}" name="last_name" value="{{ $value('last_name') }}" placeholder="Enter last name" required>
        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="{{ $fieldId('email') }}" class="form-label">Email Address <span class="required-mark">*</span></label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="{{ $fieldId('email') }}" name="email" value="{{ $value('email') }}" placeholder="Enter email address" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="{{ $fieldId('phone') }}" class="form-label">Phone <span class="required-mark">*</span></label>
        <input type="text" class="form-control @error('phone') is-invalid @enderror" id="{{ $fieldId('phone') }}" name="phone" value="{{ $value('phone') }}" placeholder="Enter phone number" required>
        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="{{ $fieldId('alternate_phone') }}" class="form-label">Alternate Phone</label>
        <input type="text" class="form-control" id="{{ $fieldId('alternate_phone') }}" name="alternate_phone" value="{{ $value('alternate_phone') }}" placeholder="Enter alternate phone number">
    </div>
    <div class="col-md-3">
        <label for="{{ $fieldId('gender') }}" class="form-label">Gender</label>
        <select class="form-select" id="{{ $fieldId('gender') }}" name="gender">
            <option value="">Select gender</option>
            @foreach(['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $option => $label)
                <option value="{{ $option }}" @selected($value('gender') === $option)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label for="{{ $fieldId('dob') }}" class="form-label">Date of Birth</label>
        <input type="date" class="form-control js-client-dob-input" id="{{ $fieldId('dob') }}" name="dob" value="{{ $dateValue('dob') }}" data-target-age="{{ $fieldId('calculated_age') }}" placeholder="Select date of birth">
    </div>
    <div class="col-md-3">
        <label for="{{ $fieldId('calculated_age') }}" class="form-label">Age (Years)</label>
        <input type="text" class="form-control bg-light" id="{{ $fieldId('calculated_age') }}" readonly placeholder="Auto-calculated" value="{{ $client?->age !== null ? $client->age . ' yrs' : '' }}">
    </div>
    <div class="col-md-3">
        <label for="{{ $fieldId('client_since') }}" class="form-label">Client Since</label>
        <input type="date" class="form-control" id="{{ $fieldId('client_since') }}" name="client_since" value="{{ $dateValue('client_since', now()->toDateString()) }}" placeholder="Select client since date">
    </div>
    <div class="col-md-6">
        <label for="{{ $fieldId('address_line1') }}" class="form-label">Address Line 1</label>
        <input type="text" class="form-control" id="{{ $fieldId('address_line1') }}" name="address_line1" value="{{ $value('address_line1') }}" placeholder="Enter address">
    </div>
    <div class="col-md-6">
        <label for="{{ $fieldId('address_line2') }}" class="form-label">Address Line 2</label>
        <input type="text" class="form-control" id="{{ $fieldId('address_line2') }}" name="address_line2" value="{{ $value('address_line2') }}" placeholder="Enter apartment, suite, etc.">
    </div>
    <div class="col-md-3">
        <label for="{{ $fieldId('city') }}" class="form-label">City</label>
        <input type="text" class="form-control" id="{{ $fieldId('city') }}" name="city" value="{{ $value('city') }}" placeholder="Enter city">
    </div>
    <div class="col-md-3">
        <label for="{{ $fieldId('state') }}" class="form-label">State</label>
        <input type="text" class="form-control" id="{{ $fieldId('state') }}" name="state" value="{{ $value('state') }}" placeholder="Enter state">
    </div>
    <div class="col-md-3">
        <label for="{{ $fieldId('country') }}" class="form-label">Country</label>
        <input type="text" class="form-control" id="{{ $fieldId('country') }}" name="country" value="{{ $value('country') }}" placeholder="Enter country">
    </div>
    <div class="col-md-3">
        <label for="{{ $fieldId('postal_code') }}" class="form-label">Postal Code</label>
        <input type="text" class="form-control" id="{{ $fieldId('postal_code') }}" name="postal_code" value="{{ $value('postal_code') }}" placeholder="Enter postal code">
    </div>
    <div class="col-md-6">
        <label for="{{ $fieldId('emergency_contact') }}" class="form-label">Emergency Contact</label>
        <input type="text" class="form-control" id="{{ $fieldId('emergency_contact') }}" name="emergency_contact" value="{{ $value('emergency_contact') }}" placeholder="Enter emergency contact name">
    </div>
    <div class="col-md-6">
        <label for="{{ $fieldId('emergency_phone') }}" class="form-label">Emergency Phone</label>
        <input type="text" class="form-control" id="{{ $fieldId('emergency_phone') }}" name="emergency_phone" value="{{ $value('emergency_phone') }}" placeholder="Enter emergency phone number">
    </div>
    <div class="col-12">
        <label for="{{ $fieldId('notes') }}" class="form-label">Notes</label>
        <textarea class="form-control" id="{{ $fieldId('notes') }}" name="notes" rows="3" placeholder="Enter notes">{{ $value('notes') }}</textarea>
    </div>
    <div class="col-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="{{ $fieldId('is_vip') }}" name="is_vip" value="1" @checked((bool) $value('is_vip'))>
            <label class="form-check-label" for="{{ $fieldId('is_vip') }}">VIP Client</label>
        </div>
    </div>
</div>

<script>
(function () {
    function calculateBirthdayAge(dobString) {
        if (!dobString) return '';
        const parts = dobString.split('-');
        if (parts.length !== 3) return '';
        const birthYear = parseInt(parts[0], 10);
        const birthMonth = parseInt(parts[1], 10) - 1;
        const birthDay = parseInt(parts[2], 10);
        const dob = new Date(birthYear, birthMonth, birthDay);
        if (isNaN(dob.getTime())) return '';

        const today = new Date();
        const now = new Date(today.getFullYear(), today.getMonth(), today.getDate());
        if (dob > now) return '';

        let age = now.getFullYear() - dob.getFullYear();
        const monthDiff = now.getMonth() - dob.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < dob.getDate())) {
            age--;
        }
        return age >= 0 ? age + ' yrs' : '';
    }

    function updateAgeForInput(dobInput) {
        if (!dobInput) return;
        const targetId = dobInput.dataset.targetAge;
        if (!targetId) return;
        const ageInput = document.getElementById(targetId);
        if (ageInput) {
            ageInput.value = calculateBirthdayAge(dobInput.value);
        }
    }

    if (!window._clientDobListenerAttached) {
        window._clientDobListenerAttached = true;

        document.addEventListener('input', function (e) {
            if (e.target && e.target.classList.contains('js-client-dob-input')) {
                updateAgeForInput(e.target);
            }
        });

        document.addEventListener('change', function (e) {
            if (e.target && e.target.classList.contains('js-client-dob-input')) {
                updateAgeForInput(e.target);
            }
        });

        document.addEventListener('shown.bs.modal', function (e) {
            if (e.target) {
                e.target.querySelectorAll('.js-client-dob-input').forEach(updateAgeForInput);
            }
        });
    }

    setTimeout(function () {
        document.querySelectorAll('.js-client-dob-input').forEach(updateAgeForInput);
    }, 0);
})();
</script>
