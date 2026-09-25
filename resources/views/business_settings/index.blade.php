@extends('layouts.app')

@section('title', 'Business Settings')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <h2 class="fs-4 m-0 fw-bold">Business settings</h2>
        </div>
    </nav>

    <div class="container-fluid px-4 pt-4">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card shadow-sm border-0 rounded mb-5">
            <div class="card-body p-4">
                <form action="{{ route('business-settings.update') }}" method="POST" enctype="multipart/form-data" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Business name <span class="required-mark">*</span></label>
                            @error('business_name')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input class="form-control @error('business_name') is-invalid @enderror" name="business_name" value="{{ old('business_name', $settings['business_name'] ?? config('app.name')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Business email</label>
                            @error('business_email')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input type="email" class="form-control @error('business_email') is-invalid @enderror" name="business_email" value="{{ old('business_email', $settings['business_email'] ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Business phone</label>
                            @error('business_phone')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input class="form-control @error('business_phone') is-invalid @enderror" name="business_phone" value="{{ old('business_phone', $settings['business_phone'] ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Currency <span class="required-mark">*</span></label>
                            @error('currency')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input class="form-control @error('currency') is-invalid @enderror" name="currency" value="{{ old('currency', $settings['currency'] ?? 'USD') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            @error('business_address')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <textarea class="form-control @error('business_address') is-invalid @enderror" name="business_address" rows="3">{{ old('business_address', $settings['business_address'] ?? '') }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Timezone <span class="required-mark">*</span></label>
                            @error('timezone')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <select class="form-select @error('timezone') is-invalid @enderror" name="timezone" required>
                                @foreach(timezone_identifiers_list() as $timezone)
                                    <option value="{{ $timezone }}" {{ old('timezone', $settings['timezone'] ?? config('app.timezone')) === $timezone ? 'selected' : '' }}>{{ $timezone }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date format <span class="required-mark">*</span></label>
                            @error('date_format')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input class="form-control @error('date_format') is-invalid @enderror" name="date_format" value="{{ old('date_format', $settings['date_format'] ?? 'M j, Y') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Time format <span class="required-mark">*</span></label>
                            @error('time_format')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input class="form-control @error('time_format') is-invalid @enderror" name="time_format" value="{{ old('time_format', $settings['time_format'] ?? 'g:i A') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Appointment interval minutes <span class="required-mark">*</span></label>
                            @error('appointment_interval')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input type="number" min="5" max="240" class="form-control @error('appointment_interval') is-invalid @enderror" name="appointment_interval" value="{{ old('appointment_interval', $settings['appointment_interval'] ?? 30) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Default appointment status <span class="required-mark">*</span></label>
                            @error('default_appointment_status')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <select class="form-select @error('default_appointment_status') is-invalid @enderror" name="default_appointment_status" required>
                                @foreach(['pending', 'booked', 'completed', 'cancelled'] as $status)
                                    <option value="{{ $status }}" {{ old('default_appointment_status', $settings['default_appointment_status'] ?? 'pending') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Invoice prefix <span class="required-mark">*</span></label>
                            @error('invoice_prefix')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input class="form-control @error('invoice_prefix') is-invalid @enderror" name="invoice_prefix" value="{{ old('invoice_prefix', $settings['invoice_prefix'] ?? 'INV') }}" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Business Logo</label>
                            @error('business_logo')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input type="file" class="form-control @error('business_logo') is-invalid @enderror" name="business_logo" accept="image/*">
                            @if(!empty($settings['business_logo']))
                                <div class="mt-2 d-flex align-items-center gap-2">
                                    <span class="text-muted small">Current Logo:</span>
                                    <img src="{{ asset('storage/' . $settings['business_logo']) }}" alt="Logo" style="max-height: 40px; border-radius: 4px; border: 1px solid var(--border)">
                                </div>
                            @endif
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary px-4" data-loading-text="Saving...">Save settings</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
