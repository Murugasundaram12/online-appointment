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
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="card shadow-sm border-0 rounded mb-5">
            <div class="card-body p-4">
                <form action="{{ route('business-settings.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Business name <span class="required-mark">*</span></label>
                            <input class="form-control" name="business_name" value="{{ old('business_name', $settings['business_name'] ?? config('app.name')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Business email</label>
                            <input type="email" class="form-control" name="business_email" value="{{ old('business_email', $settings['business_email'] ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Business phone</label>
                            <input class="form-control" name="business_phone" value="{{ old('business_phone', $settings['business_phone'] ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Currency <span class="required-mark">*</span></label>
                            <input class="form-control" name="currency" value="{{ old('currency', $settings['currency'] ?? 'USD') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="business_address" rows="3">{{ old('business_address', $settings['business_address'] ?? '') }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Timezone <span class="required-mark">*</span></label>
                            <select class="form-select" name="timezone" required>
                                @foreach(timezone_identifiers_list() as $timezone)
                                    <option value="{{ $timezone }}" {{ old('timezone', $settings['timezone'] ?? config('app.timezone')) === $timezone ? 'selected' : '' }}>{{ $timezone }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date format <span class="required-mark">*</span></label>
                            <input class="form-control" name="date_format" value="{{ old('date_format', $settings['date_format'] ?? 'M j, Y') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Time format <span class="required-mark">*</span></label>
                            <input class="form-control" name="time_format" value="{{ old('time_format', $settings['time_format'] ?? 'g:i A') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Appointment interval minutes <span class="required-mark">*</span></label>
                            <input type="number" min="5" max="240" class="form-control" name="appointment_interval" value="{{ old('appointment_interval', $settings['appointment_interval'] ?? 30) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Default appointment status <span class="required-mark">*</span></label>
                            <select class="form-select" name="default_appointment_status" required>
                                @foreach(['pending', 'booked', 'completed', 'cancelled'] as $status)
                                    <option value="{{ $status }}" {{ old('default_appointment_status', $settings['default_appointment_status'] ?? 'pending') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Invoice prefix <span class="required-mark">*</span></label>
                            <input class="form-control" name="invoice_prefix" value="{{ old('invoice_prefix', $settings['invoice_prefix'] ?? 'INV') }}" required>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary px-4">Save settings</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
