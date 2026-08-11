@extends('layouts.app')

@section('title', 'Add Staff Schedule')

@push('styles')
    <style>
        .schedule-card {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .schedule-section-title {
            font-weight: 700;
            font-size: 1.05rem;
            color: #181c32;
        }

        .weekday-checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .weekday-checkbox-btn {
            position: relative;
        }

        .weekday-checkbox-btn input[type="checkbox"] {
            display: none;
        }

        .weekday-checkbox-btn label {
            display: inline-block;
            padding: 8px 16px;
            border: 1px solid #e4e6ef;
            border-radius: 8px;
            background-color: #f9fbfd;
            color: #4b5563;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .weekday-checkbox-btn input[type="checkbox"]:checked + label {
            background-color: #3699ff;
            color: #ffffff;
            border-color: #3699ff;
            box-shadow: 0 4px 12px rgba(54, 153, 255, 0.25);
        }

        .required-mark {
            color: #f64e60;
        }
    </style>
@endpush

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-white py-3 px-4 border-bottom shadow-sm">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <div>
                <h2 class="fs-4 m-0 fw-bold text-dark">Add Staff Schedule</h2>
                <p class="text-muted small mb-0">Create one-time or recurring working schedules for staff.</p>
            </div>
            <a href="{{ route('schedule.index', ['staff_id' => $selectedStaff->id ?? '']) }}"
                class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bx bx-arrow-back me-1"></i> Back to Schedule
            </a>
        </div>
    </nav>

    <div class="container-fluid px-4 pt-4">
        <div class="card schedule-card">
            <div class="card-body p-4">
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bx bx-error-circle me-1 fs-5 align-middle"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong class="d-block mb-1"><i class="bx bx-error me-1"></i> Please correct the following errors:</strong>
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form action="{{ route('schedule.store') }}" method="POST" id="scheduleForm">
                    @csrf

                    <h5 class="mb-3 schedule-section-title"><i class="bx bx-user me-2 text-primary"></i>Staff & Time Details</h5>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold text-muted">Staff Member <span class="required-mark">*</span></label>
                            <select name="staff_id" id="staff_id" class="form-select" required>
                                @foreach($staff as $st)
                                    <option value="{{ $st->id }}" {{ old('staff_id', $selectedStaff->id ?? '') == $st->id ? 'selected' : '' }}>
                                        {{ $st->name }} ({{ $st->category ?? 'General' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold text-muted">Recurrence Type <span class="required-mark">*</span></label>
                            <select name="recurrence_type" id="recurrence_type" class="form-select" required>
                                <option value="one_time" {{ old('recurrence_type') == 'one_time' ? 'selected' : '' }}>One Time</option>
                                <option value="daily" {{ old('recurrence_type') == 'daily' ? 'selected' : '' }}>Daily</option>
                                <option value="weekly" {{ old('recurrence_type', 'weekly') == 'weekly' ? 'selected' : '' }}>Weekly (e.g. Every Sunday)</option>
                                <option value="monthly" {{ old('recurrence_type') == 'monthly' ? 'selected' : '' }}>Monthly (e.g. Every 15th)</option>
                                <option value="yearly" {{ old('recurrence_type') == 'yearly' ? 'selected' : '' }}>Yearly (e.g. Every Jan 10)</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label small fw-semibold text-muted">Start Time <span class="required-mark">*</span></label>
                            <input type="time" class="form-control" name="start_time" value="{{ old('start_time', '10:00') }}" required>
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label small fw-semibold text-muted">End Time <span class="required-mark">*</span></label>
                            <input type="time" class="form-control" name="end_time" value="{{ old('end_time', '17:00') }}" required>
                        </div>
                    </div>

                    <hr class="my-4 text-muted">

                    <h5 class="mb-3 schedule-section-title"><i class="bx bx-calendar-event me-2 text-primary"></i>Schedule Period & Recurrence Rules</h5>

                    <!-- One Time Working Date -->
                    <div class="row g-3 mb-4 recurrence-panel" id="panel-one_time">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-muted">Working Date <span class="required-mark">*</span></label>
                            <input type="date" class="form-control" name="working_date" id="working_date" value="{{ old('working_date', date('Y-m-d')) }}">
                            <div class="form-text">Schedule applies only to this single date.</div>
                        </div>
                    </div>

                    <!-- Date Range for Recurring -->
                    <div class="row g-3 mb-3 recurrence-panel d-none" id="panel-date-range">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-muted">Start Date <span class="required-mark">*</span></label>
                            <input type="date" class="form-control" name="start_date" id="start_date" value="{{ old('start_date', date('Y-m-d')) }}">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-muted">End Date <span class="required-mark">*</span></label>
                            <input type="date" class="form-control" name="end_date" id="end_date" value="{{ old('end_date', date('Y-m-d', strtotime('+3 months'))) }}">
                        </div>
                    </div>

                    <!-- Weekly Days Selection -->
                    <div class="row g-3 mb-4 recurrence-panel d-none" id="panel-weekly">
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted d-block mb-2">Select Weekdays <span class="required-mark">*</span></label>
                            <div class="weekday-checkbox-group">
                                @php
                                    $weekdays = [
                                        0 => 'Sunday',
                                        1 => 'Monday',
                                        2 => 'Tuesday',
                                        3 => 'Wednesday',
                                        4 => 'Thursday',
                                        5 => 'Friday',
                                        6 => 'Saturday',
                                    ];
                                    $oldWeeklyDays = old('weekly_days', [0]); // Default Sunday
                                @endphp
                                @foreach($weekdays as $val => $label)
                                    <div class="weekday-checkbox-btn">
                                        <input type="checkbox" name="weekly_days[]" id="day-{{ $val }}" value="{{ $val }}"
                                            {{ in_array($val, (array) $oldWeeklyDays) ? 'checked' : '' }}>
                                        <label for="day-{{ $val }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="form-text mt-2">Check all days when staff visits/works (e.g. Every Sunday or Every Monday + Wednesday).</div>
                        </div>
                    </div>

                    <!-- Monthly Day Selection -->
                    <div class="row g-3 mb-4 recurrence-panel d-none" id="panel-monthly">
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold text-muted">Day of Month <span class="required-mark">*</span></label>
                            <select name="monthly_day" class="form-select">
                                @for($d = 1; $d <= 31; $d++)
                                    <option value="{{ $d }}" {{ old('monthly_day', 15) == $d ? 'selected' : '' }}>
                                        Every month on the {{ $d }}{{ ordinalSuffix($d) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <!-- Yearly Selection -->
                    <div class="row g-3 mb-4 recurrence-panel d-none" id="panel-yearly">
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold text-muted">Month <span class="required-mark">*</span></label>
                            <select name="yearly_month" class="form-select">
                                @php
                                    $months = [
                                        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                    ];
                                @endphp
                                @foreach($months as $mVal => $mName)
                                    <option value="{{ $mVal }}" {{ old('yearly_month', 1) == $mVal ? 'selected' : '' }}>{{ $mName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold text-muted">Day of Month <span class="required-mark">*</span></label>
                            <select name="yearly_day" class="form-select">
                                @for($d = 1; $d <= 31; $d++)
                                    <option value="{{ $d }}" {{ old('yearly_day', 10) == $d ? 'selected' : '' }}>
                                        {{ $d }}{{ ordinalSuffix($d) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold">
                            <i class="bx bx-check-circle me-1"></i> Generate & Save Schedule
                        </button>
                        <a href="{{ route('schedule.index') }}" class="btn btn-light border px-4 py-2 rounded-pill ms-2 text-muted">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const recurrenceSelect = document.getElementById('recurrence_type');
            const panelOneTime = document.getElementById('panel-one_time');
            const panelDateRange = document.getElementById('panel-date-range');
            const panelWeekly = document.getElementById('panel-weekly');
            const panelMonthly = document.getElementById('panel-monthly');
            const panelYearly = document.getElementById('panel-yearly');

            function updatePanels() {
                const type = recurrenceSelect.value;
                panelOneTime.classList.add('d-none');
                panelDateRange.classList.add('d-none');
                panelWeekly.classList.add('d-none');
                panelMonthly.classList.add('d-none');
                panelYearly.classList.add('d-none');

                if (type === 'one_time') {
                    panelOneTime.classList.remove('d-none');
                } else {
                    panelDateRange.classList.remove('d-none');
                    if (type === 'weekly') {
                        panelWeekly.classList.remove('d-none');
                    } else if (type === 'monthly') {
                        panelMonthly.classList.remove('d-none');
                    } else if (type === 'yearly') {
                        panelYearly.classList.remove('d-none');
                    }
                }
            }

            recurrenceSelect.addEventListener('change', updatePanels);
            updatePanels();
        });
    </script>
@endpush

@php
function ordinalSuffix($num) {
    if (!in_array(($num % 100), [11, 12, 13])) {
        switch ($num % 10) {
            case 1:  return 'st';
            case 2:  return 'nd';
            case 3:  return 'rd';
        }
    }
    return 'th';
}
@endphp
