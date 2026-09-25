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
        @error('first_name')<div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">{{ $message }}</div>@enderror
        <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="{{ $fieldId('first_name') }}" name="first_name" value="{{ $value('first_name') }}" placeholder="Enter first name" required>
    </div>
    <div class="col-md-6">
        <label for="{{ $fieldId('last_name') }}" class="form-label">Last Name <span class="required-mark">*</span></label>
        @error('last_name')<div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">{{ $message }}</div>@enderror
        <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="{{ $fieldId('last_name') }}" name="last_name" value="{{ $value('last_name') }}" placeholder="Enter last name" required>
    </div>
    <div class="col-md-6">
        <label for="{{ $fieldId('email') }}" class="form-label">Email Address</label>
        @error('email')<div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">{{ $message }}</div>@enderror
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="{{ $fieldId('email') }}" name="email" value="{{ $value('email') }}" placeholder="Enter email address">
    </div>
    <div class="col-md-6">
        <label for="{{ $fieldId('phone') }}" class="form-label">Phone</label>
        @error('phone')<div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">{{ $message }}</div>@enderror
        <input type="tel" inputmode="tel" autocomplete="tel" maxlength="14" pattern="(?:\+1\s?)?\(?[2-9][0-9]{2}\)?[\s.-]?[0-9]{3}[\s.-]?[0-9]{4}" class="form-control js-phone-input @error('phone') is-invalid @enderror" id="{{ $fieldId('phone') }}" name="phone" value="{{ $value('phone') }}" placeholder="(xxx) xxx-xxxx">
    </div>
    <div class="col-md-6">
        <label for="{{ $fieldId('alternate_phone') }}" class="form-label">Alternate Phone</label>
        @error('alternate_phone')<div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">{{ $message }}</div>@enderror
        <input type="tel" inputmode="tel" autocomplete="tel" maxlength="14" class="form-control js-phone-input @error('alternate_phone') is-invalid @enderror" id="{{ $fieldId('alternate_phone') }}" name="alternate_phone" value="{{ $value('alternate_phone') }}" placeholder="(xxx) xxx-xxxx">
    </div>
    <div class="col-md-3">
        <label for="{{ $fieldId('gender') }}" class="form-label">Gender</label>
        @error('gender')<div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">{{ $message }}</div>@enderror
        <select class="form-select @error('gender') is-invalid @enderror" id="{{ $fieldId('gender') }}" name="gender">
            <option value="">Select gender</option>
            @foreach(['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $option => $label)
                <option value="{{ $option }}" @selected($value('gender') === $option)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label for="{{ $fieldId('dob') }}" class="form-label">Date of Birth</label>
        @error('dob')<div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">{{ $message }}</div>@enderror
        <input type="date" class="form-control js-client-dob-input @error('dob') is-invalid @enderror" id="{{ $fieldId('dob') }}" name="dob" value="{{ $dateValue('dob') }}" data-target-age="{{ $fieldId('calculated_age') }}" placeholder="Select date of birth">
    </div>
    <div class="col-md-3">
        <label for="{{ $fieldId('calculated_age') }}" class="form-label">Age (Years)</label>
        <input type="text" class="form-control bg-light" id="{{ $fieldId('calculated_age') }}" readonly placeholder="Auto-calculated" value="{{ $client?->age !== null ? $client->age . ' yrs' : '' }}">
    </div>
    <div class="col-md-3">
        <label for="{{ $fieldId('client_since') }}" class="form-label">Client Since</label>
        @error('client_since')<div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">{{ $message }}</div>@enderror
        <input type="date" class="form-control @error('client_since') is-invalid @enderror" id="{{ $fieldId('client_since') }}" name="client_since" value="{{ $dateValue('client_since', now()->toDateString()) }}" placeholder="Select client since date">
    </div>
    <div class="col-md-6">
        <label for="{{ $fieldId('address_line1') }}" class="form-label">Address Line 1</label>
        @error('address_line1')<div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">{{ $message }}</div>@enderror
        <input type="text" class="form-control @error('address_line1') is-invalid @enderror" id="{{ $fieldId('address_line1') }}" name="address_line1" value="{{ $value('address_line1') }}" placeholder="Enter address">
    </div>
    <div class="col-md-6">
        <label for="{{ $fieldId('address_line2') }}" class="form-label">Address Line 2</label>
        @error('address_line2')<div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">{{ $message }}</div>@enderror
        <input type="text" class="form-control @error('address_line2') is-invalid @enderror" id="{{ $fieldId('address_line2') }}" name="address_line2" value="{{ $value('address_line2') }}" placeholder="Enter apartment, suite, etc.">
    </div>
    <div class="col-md-3">
        <label for="{{ $fieldId('city') }}" class="form-label">City</label>
        @error('city')<div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">{{ $message }}</div>@enderror
        <input type="text" class="form-control @error('city') is-invalid @enderror" id="{{ $fieldId('city') }}" name="city" value="{{ $value('city') }}" placeholder="Enter city">
    </div>
    <div class="col-md-3">
        <label for="{{ $fieldId('state') }}" class="form-label">Province</label>
        @error('state')<div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">{{ $message }}</div>@enderror
        <input type="text" class="form-control @error('state') is-invalid @enderror" id="{{ $fieldId('state') }}" name="state" value="{{ $value('state') }}" placeholder="Enter province">
    </div>
    <div class="col-md-3">
        <label for="{{ $fieldId('country') }}" class="form-label">Country</label>
        @error('country')<div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">{{ $message }}</div>@enderror
        <input type="text" class="form-control @error('country') is-invalid @enderror" id="{{ $fieldId('country') }}" name="country" value="{{ $value('country') }}" placeholder="Enter country">
    </div>
    <div class="col-md-3">
        <label for="{{ $fieldId('postal_code') }}" class="form-label">Postal Code</label>
        @error('postal_code')<div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">{{ $message }}</div>@enderror
        <input type="text" class="form-control @error('postal_code') is-invalid @enderror" id="{{ $fieldId('postal_code') }}" name="postal_code" value="{{ $value('postal_code') }}" placeholder="Enter postal code">
    </div>
    <div class="col-md-6">
        <label for="{{ $fieldId('emergency_contact') }}" class="form-label">Emergency Contact</label>
        @error('emergency_contact')<div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">{{ $message }}</div>@enderror
        <input type="text" class="form-control @error('emergency_contact') is-invalid @enderror" id="{{ $fieldId('emergency_contact') }}" name="emergency_contact" value="{{ $value('emergency_contact') }}" placeholder="Enter emergency contact name">
    </div>
    <div class="col-md-6">
        <label for="{{ $fieldId('emergency_phone') }}" class="form-label">Emergency Phone</label>
        @error('emergency_phone')<div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">{{ $message }}</div>@enderror
        <input type="tel" inputmode="tel" autocomplete="tel" maxlength="14" class="form-control js-phone-input @error('emergency_phone') is-invalid @enderror" id="{{ $fieldId('emergency_phone') }}" name="emergency_phone" value="{{ $value('emergency_phone') }}" placeholder="(xxx) xxx-xxxx">
    </div>
    <div class="col-12">
        <label for="{{ $fieldId('notes') }}" class="form-label">Notes</label>
        @error('notes')<div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">{{ $message }}</div>@enderror
        <textarea class="form-control @error('notes') is-invalid @enderror" id="{{ $fieldId('notes') }}" name="notes" rows="3" placeholder="Enter notes">{{ $value('notes') }}</textarea>
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
