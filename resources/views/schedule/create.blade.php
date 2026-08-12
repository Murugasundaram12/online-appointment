@extends('layouts.app')

@section('title', $editing ?? null ? 'Edit Staff Schedule' : 'Add Staff Schedule')

@php
if (!function_exists('ordinalSuffix')) {
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
}
@endphp

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
    @php
        $editing = $editing ?? null;
        $editStartTime = $editing ? substr((string) $editing->start_time, 0, 5) : '10:00';
        $editEndTime = $editing ? substr((string) $editing->end_time, 0, 5) : '17:00';
        $editRecurrenceDays = $editing ? $editing->recurrence_days : null;
        $editHasAssocDays = is_array($editRecurrenceDays) && array_key_exists('weekly_days', $editRecurrenceDays);
        $editWeeklyDays = [];
        if ($editing) {
            if ($editHasAssocDays) {
                $editWeeklyDays = (array) ($editRecurrenceDays['weekly_days'] ?? []);
            } elseif (is_array($editRecurrenceDays)) {
                $editWeeklyDays = array_values($editRecurrenceDays);
            }
        }
        $editMonthlyDay = $editing ? ($editHasAssocDays ? ($editRecurrenceDays['monthly_day'] ?? null) : ($editing->working_date ? $editing->working_date->day : null)) : null;
        $editYearlyMonth = $editing ? ($editHasAssocDays ? ($editRecurrenceDays['yearly_month'] ?? null) : ($editing->working_date ? $editing->working_date->month : null)) : null;
        $editYearlyDay = $editing ? ($editHasAssocDays ? ($editRecurrenceDays['yearly_day'] ?? null) : ($editing->working_date ? $editing->working_date->day : null)) : null;
        $editBreaks = $editing && is_array($editing->breaks) ? array_values($editing->breaks) : [];
        $editBreakStart = $editBreaks[0]['start'] ?? '';
        $editBreakEnd = $editBreaks[0]['end'] ?? '';
    @endphp

    <nav class="navbar navbar-expand-lg navbar-light bg-white py-3 px-4 border-bottom shadow-sm">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <div>
                <h2 class="fs-4 m-0 fw-bold text-dark">{{ $editing ? 'Edit Staff Schedule' : 'Add Staff Schedule' }}</h2>
                <p class="text-muted small mb-0">{{ $editing ? 'Update the working hours and recurrence for this schedule.' : 'Create one-time or recurring working schedules for staff.' }}</p>
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
                    <input type="hidden" name="schedule_id" value="{{ old('schedule_id', $schedule?->id) }}">

                    @if($editing && ($schedule?->recurrence_group_id || ($editing->recurrence_type && $editing->recurrence_type !== 'one_time')))
                        <div class="card bg-light border-0 mb-4">
                            <div class="card-body p-3">
                                <label class="form-label small fw-bold text-dark d-block mb-2"><i class="bx bx-edit me-1 text-primary"></i>Edit Recurring Schedule Options</label>
                                <div class="d-flex flex-wrap gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="edit_scope" id="scope_group" value="group" {{ old('edit_scope', $editScope ?? 'group') === 'group' ? 'checked' : '' }}>
                                        <label class="form-check-label small fw-semibold" for="scope_group">
                                            Edit entire recurring schedule
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="edit_scope" id="scope_occurrence" value="occurrence" {{ old('edit_scope', $editScope ?? '') === 'occurrence' ? 'checked' : '' }}>
                                        <label class="form-check-label small fw-semibold" for="scope_occurrence">
                                            Edit this occurrence only ({{ $schedule?->working_date ? $schedule->working_date->format('d M Y') : 'Selected Date' }})
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <h5 class="mb-3 schedule-section-title"><i class="bx bx-user me-2 text-primary"></i>Staff & Time Details</h5>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold text-muted">Staff Member <span class="required-mark">*</span></label>
                            <select name="staff_id" id="staff_id" class="form-select" required>
                                @foreach($staff as $st)
                                    <option value="{{ $st->id }}" {{ old('staff_id', $editing?->staff_id ?? $selectedStaff->id ?? '') == $st->id ? 'selected' : '' }}>
                                        {{ $st->name }} ({{ $st->category ?? 'General' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold text-muted">Recurrence Type <span class="required-mark">*</span></label>
                            <select name="recurrence_type" id="recurrence_type" class="form-select" required>
                                <option value="one_time" {{ old('recurrence_type', $editing?->recurrence_type ?? 'weekly') == 'one_time' ? 'selected' : '' }}>One Time</option>
                                <option value="daily" {{ old('recurrence_type', $editing?->recurrence_type ?? 'weekly') == 'daily' ? 'selected' : '' }}>Daily</option>
                                <option value="weekly" {{ old('recurrence_type', $editing?->recurrence_type ?? 'weekly') == 'weekly' ? 'selected' : '' }}>Weekly (e.g. Every Sunday)</option>
                                <option value="monthly" {{ old('recurrence_type', $editing?->recurrence_type ?? 'weekly') == 'monthly' ? 'selected' : '' }}>Monthly (e.g. Every 15th)</option>
                                <option value="yearly" {{ old('recurrence_type', $editing?->recurrence_type ?? 'weekly') == 'yearly' ? 'selected' : '' }}>Yearly (e.g. Every Jan 10)</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label small fw-semibold text-muted">Start Time <span class="required-mark">*</span></label>
                            <input type="time" class="form-control" name="start_time" value="{{ old('start_time', $editStartTime) }}" required>
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label small fw-semibold text-muted">End Time <span class="required-mark">*</span></label>
                            <input type="time" class="form-control" name="end_time" value="{{ old('end_time', $editEndTime) }}" required>
                        </div>
                        <div class="col-12 col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="day_off" name="is_working" value="0"
                                    {{ old('is_working', $editing ? (!$editing->is_working ? '0' : null) : null) === '0' ? 'checked' : '' }}>
                                <label class="form-check-label small" for="day_off">Day Off — staff is <strong>not available</strong> on this date</label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-2">
                            <label class="form-label small fw-semibold text-muted">Break Start</label>
                            <input type="time" class="form-control" name="break_start" value="{{ old('break_start', $editBreakStart) }}">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small fw-semibold text-muted">Break End</label>
                            <input type="time" class="form-control" name="break_end" value="{{ old('break_end', $editBreakEnd) }}">
                        </div>
                        <div class="col-12 col-md-8 d-flex align-items-end">
                            <span class="form-text mb-2">Optional: staff is unavailable during this break (e.g. 12:00 – 13:00).</span>
                        </div>
                    </div>

                    <hr class="my-4 text-muted">

                    <h5 class="mb-3 schedule-section-title"><i class="bx bx-calendar-event me-2 text-primary"></i>Schedule Period & Recurrence Rules</h5>

                    <!-- One Time Working Date -->
                    <div class="row g-3 mb-4 recurrence-panel" id="panel-one_time">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-muted">Working Date <span class="required-mark">*</span></label>
                            <input type="date" class="form-control" name="working_date" id="working_date" value="{{ old('working_date', $editing && $editing->working_date ? $editing->working_date->format('Y-m-d') : date('Y-m-d')) }}">
                            <div class="form-text">Schedule applies only to this single date.</div>
                        </div>
                    </div>

                    <!-- Date Range for Recurring -->
                    <div class="row g-3 mb-3 recurrence-panel d-none" id="panel-date-range">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-muted">Start Date <span class="required-mark">*</span></label>
                            <input type="date" class="form-control" name="start_date" id="start_date" value="{{ old('start_date', $editing && $editing->start_date ? $editing->start_date->format('Y-m-d') : date('Y-m-d')) }}">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-muted">End Date <span class="required-mark">*</span></label>
                            <input type="date" class="form-control" name="end_date" id="end_date" value="{{ old('end_date', $editing && $editing->end_date ? $editing->end_date->format('Y-m-d') : date('Y-m-d', strtotime('+3 months'))) }}">
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
                                    $oldWeeklyDays = old('weekly_days', $editWeeklyDays ?: [0]); // Default Sunday
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
                                    <option value="{{ $d }}" {{ old('monthly_day', $editMonthlyDay ?? 15) == $d ? 'selected' : '' }}>
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
                                    <option value="{{ $mVal }}" {{ old('yearly_month', $editYearlyMonth ?? 1) == $mVal ? 'selected' : '' }}>{{ $mName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold text-muted">Day of Month <span class="required-mark">*</span></label>
                            <select name="yearly_day" class="form-select">
                                @for($d = 1; $d <= 31; $d++)
                                    <option value="{{ $d }}" {{ old('yearly_day', $editYearlyDay ?? 10) == $d ? 'selected' : '' }}>
                                        {{ $d }}{{ ordinalSuffix($d) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold">
                            <i class="bx bx-check-circle me-1"></i> {{ $editing ? 'Update Schedule' : 'Generate & Save Schedule' }}
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
            const dayOffToggle = document.getElementById('day_off');
            let hiddenRecurrenceInput = null;

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

            function updateDayOff() {
                const isDayOff = dayOffToggle.checked;
                ['start_time', 'end_time', 'break_start', 'break_end'].forEach(name => {
                    const el = document.querySelector(`#scheduleForm [name="${name}"]`);
                    if (!el) return;
                    el.disabled = isDayOff;
                    if (isDayOff) el.value = '';
                });
                if (isDayOff) {
                    recurrenceSelect.value = 'one_time';
                    if (!hiddenRecurrenceInput) {
                        hiddenRecurrenceInput = document.createElement('input');
                        hiddenRecurrenceInput.type = 'hidden';
                        hiddenRecurrenceInput.name = 'recurrence_type';
                        recurrenceSelect.form.appendChild(hiddenRecurrenceInput);
                    }
                    hiddenRecurrenceInput.value = 'one_time';
                } else if (hiddenRecurrenceInput) {
                    hiddenRecurrenceInput.remove();
                    hiddenRecurrenceInput = null;
                }
                recurrenceSelect.disabled = isDayOff;
                updatePanels();
            }

            recurrenceSelect.addEventListener('change', updatePanels);
            if (dayOffToggle) {
                dayOffToggle.addEventListener('change', updateDayOff);
                updateDayOff();
            }
            updatePanels();
        });
    </script>
@endpush
