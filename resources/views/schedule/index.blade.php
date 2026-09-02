@extends('layouts.app')

@section('title', 'Schedule')

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
        .secondary-sidebar {
            width: 280px;
            background-color: #fff;
            border-right: 1px solid #eef0f7;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .schedule-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: #fff;
            overflow: hidden;
        }

        .provider-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f8f9fa;
            cursor: pointer;
            transition: background 0.2s;
        }

        .provider-item:hover {
            background-color: #f8f9fa;
        }

        .provider-item.active {
            background-color: #f1f6ff;
            border-left: 3px solid #3699ff;
        }

        .view-toggle .btn {
            border: 1px solid #eef0f7;
            color: #7e8299;
            font-size: 0.85rem;
            padding: 0.4rem 1rem;
        }

        .view-toggle .btn.active {
            background-color: #f1f3f9;
            color: #3f4254;
            font-weight: 500;
        }

        .calendar-grid {
            flex: 1;
            overflow: auto;
            position: relative;
        }

        .grid-header {
            display: grid;
            grid-template-columns: 80px repeat(7, 1fr);
            border-bottom: 1px solid #eef0f7;
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 10;
        }

        .grid-header-cell {
            padding: 0.75rem;
            text-align: center;
            border-right: 1px solid #eef0f7;
            font-size: 0.75rem;
            color: #7e8299;
            font-weight: 500;
        }

        .grid-header-cell.border-0 {
            background: #fff;
        }

        .grid-header-cell.current-day-col {
            background-color: #f7faff;
            color: #3699ff;
            font-weight: 600;
        }

        .grid-body {
            display: grid;
            grid-template-columns: 80px repeat(7, 1fr);
            min-height: 1200px;
        }

        .time-cell {
            padding: 1.5rem 0.5rem;
            text-align: right;
            border-right: 1px solid #eef0f7;
            border-bottom: 1px solid #f8f9fa;
            font-size: 0.7rem;
            color: #a1a5b7;
            height: 50px;
        }

        .slot-cell {
            border-right: 1px solid #f8f9fa;
            border-bottom: 1px solid #f8f9fa;
            min-height: 50px;
            position: relative;
            background-color: #fff;
            cursor: pointer;
            transition: background 0.1s;
        }

        .slot-cell:hover {
            background-color: #f8f9fa;
        }

        .current-day-col {
            background-color: #f7faff;
        }

        .avatar-box {
            width: 40px;
            height: 48px;
            background-color: #fff1f2;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
        }

        /* Schedule block styling */
        .schedule-block {
            position: absolute;
            left: 4px;
            right: 4px;
            background: linear-gradient(135deg, #3699ff 0%, #2580d0 100%);
            color: white;
            padding: 4px 6px;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 600;
            overflow: hidden;
            cursor: grab;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            white-space: nowrap;
            z-index: 5;
        }

        .schedule-block:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            transform: scale(1.02);
        }

        .schedule-block.weekend {
            background: linear-gradient(135deg, #b5b5c3 0%, #a1a1b7 100%);
        }

        .time-col {
            background: #fff;
            border-right: 1px solid #eef0f7;
        }

        .day-col {
            position: relative;
        }

        .required-mark {
            color: #f64e60;
        }

        .weekday-checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .weekday-checkbox-btn {
            position: relative;
        }

        .weekday-checkbox-btn input[type="checkbox"] {
            display: none;
        }

        .weekday-checkbox-btn label {
            display: inline-block;
            padding: 6px 14px;
            border: 1px solid #e4e6ef;
            border-radius: 8px;
            background-color: #f9fbfd;
            color: #4b5563;
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .weekday-checkbox-btn input[type="checkbox"]:checked + label {
            background-color: #3699ff;
            color: #ffffff;
            border-color: #3699ff;
            box-shadow: 0 4px 12px rgba(54, 153, 255, 0.25);
        }
    </style>
@endpush

@section('content')
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mx-4 mt-4 mb-0" role="alert">
            <i class="bx bx-error-circle me-1 fs-5 align-middle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mx-4 mt-4 mb-0" role="alert">
            <i class="bx bx-check-circle me-1 fs-5 align-middle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <div class="d-flex h-100">
        <!-- Secondary Sidebar: Provider Selection -->
        <div class="secondary-sidebar">
            <div class="p-4 border-bottom">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="fs-6 m-0 fw-bold">Schedule list</h2>
                    <button class="btn btn-link text-muted p-0"><i class='bx bx-chevron-left fs-4'></i></button>
                </div>
            <div class="provider-list">
                @foreach($staff as $member)
                    <div class="provider-item {{ isset($currentStaff) && $currentStaff->id == $member->id ? 'active' : '' }}"
                        onclick="window.location='{{ route('schedule.index', ['staff_id' => $member->id]) }}'">
                        <div class="d-flex align-items-center">
                            <div class="avatar-initials bg-light-danger text-danger me-2 rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 28px; height: 28px; font-size: 0.75rem;">
                                {{ substr($member->name, 0, 2) }}
                            </div>
                            <span class="small text-dark fw-500">{{ $member->name }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

        <!-- Main Schedule Content -->
        <div class="schedule-container">
            <!-- Top Navbar for Schedule Filters -->
            <nav class="navbar navbar-light py-3 px-4 border-bottom bg-light">
                <form action="{{ route('schedule.index') }}" method="GET" class="w-100" id="scheduleFilterForm">
                    <input type="hidden" name="staff_id" value="{{ $currentStaff->id ?? '' }}">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-sm-6 col-md-auto">
                            <label class="small text-muted fw-bold d-block mb-1">Quick Range</label>
                            <select name="range" class="form-select form-select-sm" id="filter-range" onchange="this.form.submit()">
                                <option value="today" {{ ($selectedRange ?? '') == 'today' ? 'selected' : '' }}>Today</option>
                                <option value="tomorrow" {{ ($selectedRange ?? '') == 'tomorrow' ? 'selected' : '' }}>Tomorrow</option>
                                <option value="this_week" {{ ($selectedRange ?? '') == 'this_week' ? 'selected' : '' }}>This Week</option>
                                <option value="this_month" {{ ($selectedRange ?? '') == 'this_month' ? 'selected' : '' }}>This Month</option>
                                <option value="this_year" {{ ($selectedRange ?? '') == 'this_year' ? 'selected' : '' }}>This Year</option>
                                <option value="custom" {{ ($selectedRange ?? '') == 'custom' ? 'selected' : '' }}>Custom</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-auto {{ ($selectedRange ?? '') == 'custom' ? '' : 'd-none' }}" id="custom-date-from">
                            <label class="small text-muted fw-bold d-block mb-1">From Date</label>
                            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate ?? '' }}" onchange="this.form.submit()">
                        </div>

                        <div class="col-6 col-md-auto {{ ($selectedRange ?? '') == 'custom' ? '' : 'd-none' }}" id="custom-date-to">
                            <label class="small text-muted fw-bold d-block mb-1">To Date</label>
                            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate ?? '' }}" onchange="this.form.submit()">
                        </div>

                        <div class="col-12 col-sm-6 col-md-auto">
                            <label class="small text-muted fw-bold d-block mb-1">Location</label>
                            <select name="location_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All Locations</option>
                                @foreach($locations ?? [] as $loc)
                                    <option value="{{ $loc->id }}" {{ ($locationId ?? '') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-md-auto">
                            <label class="small text-muted fw-bold d-block mb-1">Status</label>
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="working" {{ ($statusFilter ?? '') == 'working' ? 'selected' : '' }}>Working</option>
                                <option value="off" {{ ($statusFilter ?? '') == 'off' ? 'selected' : '' }}>Off / Unavailable</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-auto ms-auto text-end">
                            <a href="{{ route('schedule.create', ['staff_id' => $currentStaff->id ?? '']) }}"
                                class="btn btn-primary btn-sm px-4 fw-semibold rounded-pill">+ New Schedule</a>
                        </div>
                    </div>
                </form>
            </nav>

            <!-- Calendar Toolbar -->
            <div class="px-4 py-3 d-flex align-items-center justify-content-between border-bottom">
                <div class="view-toggle btn-group btn-group-sm">
                    <button class="btn">Day</button>
                    <button class="btn active">Week</button>
                    <button class="btn">Month</button>
                </div>

                <div class="d-flex align-items-center gap-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class='bx bx-calendar text-muted fs-5'></i>
                        <span class="fw-bold text-dark">
                            {{ ($weekStart ?? now()->startOfWeek())->format('d M Y') }} –
                            {{ ($weekEnd ?? now()->endOfWeek())->format('d M Y') }}
                        </span>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button class="btn border px-3" id="btn-today">Today</button>
                        <button class="btn border" id="btn-prev-week"><i class='bx bx-chevron-left fs-5'></i></button>
                        <button class="btn border" id="btn-next-week"><i class='bx bx-chevron-right fs-5'></i></button>
                    </div>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="calendar-grid">
                <div class="grid-header" id="schedule-grid-header">
                    <div class="grid-header-cell border-0"></div>
                    @for ($i = 0; $i < 7; $i++)
                        @php
                            $baseWeekStart = $weekStart ?? now()->startOfWeek();
                            $date = $baseWeekStart->copy()->addDays($i);
                            $isToday = $date->isToday();
                        @endphp
                        <div class="grid-header-cell {{ $isToday ? 'current-day-col' : '' }}">
                            {{ $date->format('D, F j') }}
                        </div>
                    @endfor
                </div>

                <div class="grid-body" id="schedule-grid-body">
                    <!-- Time labels -->
                    <div class="time-col" id="schedule-time-col">
                        @for ($hour = 0; $hour < 24; $hour++)
                            <div class="time-cell">{{ date('g:00 A', mktime($hour, 0)) }}</div>
                        @endfor
                    </div>

                    <!-- Day columns (populated by JavaScript) -->
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Entries List -->
    <div class="px-4 py-4 bg-light">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h5 class="fw-bold mb-1">Schedule Entries</h5>
                <p class="text-muted small mb-0">All schedules for the selected staff and date range.</p>
            </div>
        </div>
        <div class="card border-0 shadow-sm rounded">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light text-muted small">
                        <tr>
                            <th class="px-4">Staff</th>
                            <th>Type</th>
                            <th>Recurrence</th>
                            <th>Working Date</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Status</th>
                            <th class="text-end px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schedules as $entry)
                            <tr>
                                <td class="px-4">
                                    <div class="fw-semibold">{{ $entry['staff']['name'] ?? '—' }}</div>
                                    @if(!empty($entry['staff']['category']))
                                        <div class="small text-muted">{{ $entry['staff']['category'] }}</div>
                                    @endif
                                </td>
                                <td><span class="badge bg-info text-white">{{ $entry['summary']['type'] ?? ucfirst($entry['recurrence_type']) }}</span></td>
                                <td>{{ $entry['summary']['recurrence'] ?? '—' }}</td>
                                <td>{{ !empty($entry['working_date']) ? \Carbon\Carbon::parse($entry['working_date'])->format('d M Y') : '—' }}</td>
                                <td>{{ substr((string) $entry['start_time'], 0, 5) }}</td>
                                <td>{{ substr((string) $entry['end_time'], 0, 5) }}</td>
                                <td>{{ !empty($entry['start_date']) ? \Carbon\Carbon::parse($entry['start_date'])->format('d M Y') : '—' }}</td>
                                <td>{{ !empty($entry['end_date']) ? \Carbon\Carbon::parse($entry['end_date'])->format('d M Y') : '—' }}</td>
                                <td>
                                    @if($entry['is_working'])
                                        <span class="badge bg-success text-white">Working</span>
                                    @else
                                        <span class="badge bg-secondary text-white">Off</span>
                                    @endif
                                </td>
                                <td class="text-end px-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        @if(($entry['recurrence_type'] ?? 'one_time') !== 'one_time' || !empty($entry['recurrence_group_id']))
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Edit
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                    <li><a class="dropdown-item small" href="{{ route('schedule.edit', [$entry['id'], 'scope' => 'occurrence']) }}"><i class="bx bx-calendar-event me-2 text-primary"></i>Edit this occurrence only</a></li>
                                                    <li><a class="dropdown-item small" href="{{ route('schedule.edit', [$entry['id'], 'scope' => 'group']) }}"><i class="bx bx-repeat me-2 text-primary"></i>Edit entire recurring schedule</a></li>
                                                </ul>
                                            </div>
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-sm btn-outline-danger dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Delete
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                    <li>
                                                        <form action="{{ route('schedule.destroy', $entry['id']) }}" method="POST" onsubmit="return confirm('Delete this occurrence only?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="scope" value="occurrence">
                                                            <button type="submit" class="dropdown-item small text-danger"><i class="bx bx-calendar-x me-2"></i>Delete this occurrence</button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form action="{{ route('schedule.destroy', $entry['id']) }}" method="POST" onsubmit="return confirm('Skip this occurrence? Staff will be unavailable on this date.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="scope" value="skip">
                                                            <button type="submit" class="dropdown-item small text-warning"><i class="bx bx-block me-2"></i>Skip this occurrence</button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form action="{{ route('schedule.destroy', $entry['id']) }}" method="POST" onsubmit="return confirm('Delete entire recurring schedule group?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="scope" value="group">
                                                            <button type="submit" class="dropdown-item small text-danger fw-semibold"><i class="bx bx-trash me-2"></i>Delete entire schedule</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        @else
                                            <a href="{{ route('schedule.edit', $entry['id']) }}" class="btn btn-sm btn-light border">Edit</a>
                                            <form action="{{ route('schedule.destroy', $entry['id']) }}" method="POST"
                                                onsubmit="return confirm('Delete this schedule?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No schedules found for the selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Business Holidays -->
    <div class="px-4 py-4 bg-light">
        <div class="card border-0 shadow-sm rounded">
            <div class="card-body">
                <h6 class="fw-bold mb-1"><i class="bx bx-calendar-x me-1 text-danger"></i>Business Holidays (Closed Dates)</h6>
                <p class="text-muted small mb-3">On these dates the clinic is closed: no staff is available and no appointments can be booked.</p>
                <form action="{{ route('schedule.holidays.store') }}" method="POST" class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    @csrf
                    <input type="date" name="date" class="form-control form-control-sm w-auto" min="{{ date('Y-m-d') }}" required>
                    <button type="submit" class="btn btn-sm btn-primary">Add Holiday</button>
                </form>
                @forelse($holidays as $holiday)
                    <span class="badge bg-light border text-dark fs-6 fw-normal me-2 mb-2 px-3 py-2">
                        {{ \Carbon\Carbon::parse($holiday)->format('D, d M Y') }}
                        <form action="{{ route('schedule.holidays.destroy', $holiday) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this holiday?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-link text-danger p-0 ms-1"><i class="bx bx-x"></i></button>
                        </form>
                    </span>
                @empty
                    <div class="small text-muted">No holidays configured.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Create Schedule Modal -->
    <div class="modal fade app-modal" id="createScheduleModal" tabindex="-1" aria-labelledby="createScheduleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form class="modal-content" id="create-schedule-form" action="{{ route('schedule.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <div class="modal-heading">
                        <div class="modal-icon" aria-hidden="true"><i class="bx bx-time-five"></i></div>
                        <div>
                            <h5 class="modal-title" id="createScheduleModalLabel">Create Schedule</h5>
                            <p class="modal-subtitle">Set staff working hours, recurrence, and optional breaks.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cs-staff-id" name="staff_id" value="">

                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <label class="form-label small text-muted">Staff Name</label>
                            <input type="text" class="form-control" id="cs-staff-name" value="" readonly>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small text-muted">Category</label>
                            <input type="text" class="form-control" id="cs-staff-category" value="" readonly>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small text-muted">Recurrence Type <span class="required-mark">*</span></label>
                            <select class="form-select" id="cs-recurrence-type" name="recurrence_type" required>
                                <option value="one_time">One Time</option>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small text-muted">Start Time <span class="required-mark">*</span></label>
                            <input type="time" class="form-control" id="cs-start-time" name="start_time" required>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small text-muted">End Time <span class="required-mark">*</span></label>
                            <input type="time" class="form-control" id="cs-end-time" name="end_time" required>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small text-muted">Break Start</label>
                            <input type="time" class="form-control" id="cs-break-start" name="break_start">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small text-muted">Break End</label>
                            <input type="time" class="form-control" id="cs-break-end" name="break_end">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="cs-day-off" name="is_working" value="0">
                                <label class="form-check-label small" for="cs-day-off">Day Off — staff is <strong>not available</strong> on this date</label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="row g-3">
                        <!-- One Time Working Date -->
                        <div class="col-12 col-md-6 recurrence-panel" id="panel-one_time">
                            <label class="form-label small text-muted">Working Date <span class="required-mark">*</span></label>
                            <input type="date" class="form-control" name="working_date" id="cs-working-date" min="{{ date('Y-m-d') }}">
                            <div class="form-text">Schedule applies only to this single date.</div>
                        </div>

                        <!-- Date Range for Recurring -->
                        <div class="col-12 col-md-6 recurrence-panel d-none" id="panel-date-range">
                            <label class="form-label small text-muted">Start Date <span class="required-mark">*</span></label>
                            <input type="date" class="form-control" name="start_date" id="cs-start-date" min="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-12 col-md-6 recurrence-panel d-none" id="panel-date-range-end">
                            <label class="form-label small text-muted">End Date <span class="required-mark">*</span></label>
                            <input type="date" class="form-control" name="end_date" id="cs-end-date" min="{{ date('Y-m-d') }}">
                        </div>

                        <!-- Weekly Days Selection -->
                        <div class="col-12 recurrence-panel d-none" id="panel-weekly">
                            <label class="form-label small text-muted d-block mb-2">Select Weekdays <span class="required-mark">*</span></label>
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
                                @endphp
                                @foreach($weekdays as $val => $label)
                                    <div class="weekday-checkbox-btn">
                                        <input type="checkbox" class="cs-weekly-day" name="weekly_days[]" id="day-{{ $val }}" value="{{ $val }}">
                                        <label for="day-{{ $val }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Monthly Day Selection -->
                        <div class="col-12 col-md-6 recurrence-panel d-none" id="panel-monthly">
                            <label class="form-label small text-muted">Day of Month <span class="required-mark">*</span></label>
                            <select name="monthly_day" class="form-select" id="cs-monthly-day">
                                @for($d = 1; $d <= 31; $d++)
                                    <option value="{{ $d }}">Every month on the {{ $d }}{{ ordinalSuffix($d) }}</option>
                                @endfor
                            </select>
                        </div>

                        <!-- Yearly Selection -->
                        <div class="col-6 col-md-4 recurrence-panel d-none" id="panel-yearly">
                            <label class="form-label small text-muted">Month <span class="required-mark">*</span></label>
                            <select name="yearly_month" class="form-select" id="cs-yearly-month">
                                @php
                                    $months = [
                                        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                    ];
                                @endphp
                                @foreach($months as $mVal => $mName)
                                    <option value="{{ $mVal }}">{{ $mName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-3 recurrence-panel d-none" id="panel-yearly-day">
                            <label class="form-label small text-muted">Day of Month <span class="required-mark">*</span></label>
                            <select name="yearly_day" class="form-select" id="cs-yearly-day">
                                @for($d = 1; $d <= 31; $d++)
                                    <option value="{{ $d }}">{{ $d }}{{ ordinalSuffix($d) }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="mt-2 small text-muted" id="cs-date-label"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Schedule</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Staff Schedule Management
        document.addEventListener('DOMContentLoaded', function () {
            const currentStaffId = {{ isset($currentStaff) ? $currentStaff->id : 'null' }};
            const currentStaffName = @json($currentStaff->name ?? '');
            const currentStaffCategory = @json($currentStaff->category ?? '');
            const schedules = @json($schedules ?? []);
            const baseWeekStart = @json(isset($weekStart) ? $weekStart->toDateString() : null);
            const scheduleEditBaseUrl = @json(route('schedule.edit', '__ID__'));

            const createModalEl = document.getElementById('createScheduleModal');
            const createModal = createModalEl ? new bootstrap.Modal(createModalEl) : null;
            const createForm = document.getElementById('create-schedule-form');
            const csStaffId = document.getElementById('cs-staff-id');
            const csWorkingDate = document.getElementById('cs-working-date');
            const csStartDate = document.getElementById('cs-start-date');
            const csEndDate = document.getElementById('cs-end-date');
            const csStaffName = document.getElementById('cs-staff-name');
            const csStaffCategory = document.getElementById('cs-staff-category');
            const csStartTime = document.getElementById('cs-start-time');
            const csEndTime = document.getElementById('cs-end-time');
            const csBreakStart = document.getElementById('cs-break-start');
            const csBreakEnd = document.getElementById('cs-break-end');
            const csRecurrenceType = document.getElementById('cs-recurrence-type');
            const csDateLabel = document.getElementById('cs-date-label');
            const csDayOff = document.getElementById('cs-day-off');
            let hiddenRecurrenceInput = null;

            function renderScheduleGrid() {
                const gridBody = document.getElementById('schedule-grid-body');

                // Clear existing day columns
                gridBody.querySelectorAll('.day-col').forEach(col => col.remove());

                if (!currentStaffId) {
                    const msg = document.createElement('p');
                    msg.className = 'p-4 text-muted';
                    msg.textContent = 'Please select a staff member from the left sidebar';
                    gridBody.appendChild(msg);
                    return;
                }

                // Render 7 day columns
                const weekStart = getCurrentWeekStart();
                for (let dayIndex = 0; dayIndex < 7; dayIndex++) {
                    const dayCol = document.createElement('div');
                    dayCol.className = `day-col ${isToday(weekStart, dayIndex) ? 'current-day-col' : ''}`;
                    dayCol.dataset.dayIndex = dayIndex;

                    // Create 24 hour slots
                    for (let hour = 0; hour < 24; hour++) {
                        const slot = document.createElement('div');
                        slot.className = 'slot-cell';
                        slot.dataset.hour = hour;
                        slot.addEventListener('click', () => openScheduleForm(dayIndex, hour));
                        dayCol.appendChild(slot);
                    }

                    gridBody.appendChild(dayCol);
                }

                // Render schedules on the grid
                renderScheduleBlocks();
            }

            function getCurrentWeekStart() {
                if (baseWeekStart) {
                    const d = new Date(baseWeekStart + 'T00:00:00');
                    d.setHours(0, 0, 0, 0);
                    return d;
                }
                const today = new Date();
                const day = today.getDay();
                const diff = today.getDate() - day + (day === 0 ? -6 : 1);
                const weekStart = new Date(today.setDate(diff));
                weekStart.setHours(0, 0, 0, 0);
                return weekStart;
            }

            function isToday(weekStart, dayIndex) {
                const date = new Date(weekStart);
                date.setDate(weekStart.getDate() + dayIndex);
                const today = new Date();
                return date.getDate() === today.getDate() &&
                    date.getMonth() === today.getMonth() &&
                    date.getFullYear() === today.getFullYear();
            }

            function renderScheduleBlocks() {
                // Clear old blocks
                document.querySelectorAll('.schedule-block').forEach(b => b.remove());

                if (!schedules || schedules.length === 0) return;

                const weekStart = getCurrentWeekStart();

                schedules.forEach(sch => {
                    if (!sch.is_working) return;

                    let dayOfWeek = null;
                    if (sch.working_date) {
                        // Eloquent date casts may serialize as ISO with time (e.g. 2026-03-11T00:00:00.000000Z).
                        // We only need the date part for week mapping.
                        const raw = String(sch.working_date || '');
                        const dateOnlyMatch = raw.match(/^(\d{4}-\d{2}-\d{2})/);
                        const dateOnly = dateOnlyMatch ? dateOnlyMatch[1] : raw.slice(0, 10);
                        const sDate = new Date(dateOnly + 'T00:00:00');
                        const diffDays = Math.floor((sDate - weekStart) / (1000 * 60 * 60 * 24));
                        if (diffDays < 0 || diffDays > 6) return;
                        dayOfWeek = diffDays;
                    } else if (!isNaN(parseInt(sch.day_of_week, 10))) {
                        dayOfWeek = parseInt(sch.day_of_week, 10);
                    } else {
                        const dayMap = { monday: 0, tuesday: 1, wednesday: 2, thursday: 3, friday: 4, saturday: 5, sunday: 6 };
                        dayOfWeek = dayMap[String(sch.day_of_week || '').toLowerCase()];
                    }

                    if (dayOfWeek === null || dayOfWeek === undefined || isNaN(dayOfWeek)) return;

                    const [startH, startM] = sch.start_time.split(':').map(Number);
                    const [endH, endM] = sch.end_time.split(':').map(Number);

                    // Find day column (dayIndex == dayOfWeek for week view)
                    const dayCol = document.querySelector(`.day-col[data-day-index="${dayOfWeek}"]`);
                    if (!dayCol) return;

                    // Calculate position and height
                    const startMinutes = startH * 60 + startM;
                    const endMinutes = endH * 60 + endM;
                    const durationMinutes = endMinutes - startMinutes;

                    const slotHeight = 50;
                    const minutesPerSlot = 60;
                    const topOffset = (startMinutes / minutesPerSlot) * slotHeight;
                    const blockHeight = (durationMinutes / minutesPerSlot) * slotHeight;

                    // Create schedule block
                    const block = document.createElement('div');
                    block.className = `schedule-block ${isWeekend(dayOfWeek) ? 'weekend' : ''}`;
                    block.style.top = topOffset + 'px';
                    block.style.height = blockHeight + 'px';
                    block.textContent = `${sch.start_time.slice(0, 5)} – ${sch.end_time.slice(0, 5)}`;
                    block.dataset.scheduleId = sch.id;
                    block.addEventListener('click', (e) => {
                        e.stopPropagation();
                        editSchedule(sch.id);
                    });

                    dayCol.appendChild(block);
                });
            }

            function isWeekend(dayOfWeek) {
                return dayOfWeek >= 5; // Saturday and Sunday
            }

            function updateRecurrencePanels() {
                const type = csRecurrenceType ? csRecurrenceType.value : 'one_time';
                const panels = {
                    one_time: document.getElementById('panel-one_time'),
                    date_range: document.getElementById('panel-date-range'),
                    date_range_end: document.getElementById('panel-date-range-end'),
                    weekly: document.getElementById('panel-weekly'),
                    monthly: document.getElementById('panel-monthly'),
                    yearly: document.getElementById('panel-yearly'),
                    yearly_day: document.getElementById('panel-yearly-day'),
                };

                Object.values(panels).forEach(p => p && p.classList.add('d-none'));

                if (type === 'one_time') {
                    panels.one_time && panels.one_time.classList.remove('d-none');
                } else {
                    panels.date_range && panels.date_range.classList.remove('d-none');
                    panels.date_range_end && panels.date_range_end.classList.remove('d-none');
                    if (type === 'weekly') {
                        panels.weekly && panels.weekly.classList.remove('d-none');
                    } else if (type === 'monthly') {
                        panels.monthly && panels.monthly.classList.remove('d-none');
                    } else if (type === 'yearly') {
                        panels.yearly && panels.yearly.classList.remove('d-none');
                        panels.yearly_day && panels.yearly_day.classList.remove('d-none');
                    }
                }
            }

            function updateDayOffModal() {
                if (!csDayOff) return;
                const isDayOff = csDayOff.checked;
                [csStartTime, csEndTime, csBreakStart, csBreakEnd].forEach(el => {
                    if (!el) return;
                    el.disabled = isDayOff;
                    if (isDayOff) el.value = '';
                });
                if (csRecurrenceType) {
                    if (isDayOff) {
                        csRecurrenceType.value = 'one_time';
                        if (!hiddenRecurrenceInput) {
                            hiddenRecurrenceInput = document.createElement('input');
                            hiddenRecurrenceInput.type = 'hidden';
                            hiddenRecurrenceInput.name = 'recurrence_type';
                            csRecurrenceType.form.appendChild(hiddenRecurrenceInput);
                        }
                        hiddenRecurrenceInput.value = 'one_time';
                    } else if (hiddenRecurrenceInput) {
                        hiddenRecurrenceInput.remove();
                        hiddenRecurrenceInput = null;
                    }
                    csRecurrenceType.disabled = isDayOff;
                }
                updateRecurrencePanels();
            }

            function openScheduleForm(dayIndex, hour) {
                if (!currentStaffId) {
                    window.AppToast?.show({
                        type: 'warning',
                        title: 'Select staff',
                        message: 'Please select a staff member first.'
                    });
                    return;
                }

                const startTime = String(hour).padStart(2, '0') + ':00';
                const endHour = Math.min(hour + 1, 23);
                const endTime = String(endHour).padStart(2, '0') + ':00';
                const weekStart = getCurrentWeekStart();
                const selectedDate = new Date(weekStart);
                selectedDate.setDate(weekStart.getDate() + dayIndex);
                const workingDate = `${selectedDate.getFullYear()}-${String(selectedDate.getMonth() + 1).padStart(2, '0')}-${String(selectedDate.getDate()).padStart(2, '0')}`;

                if (!createModal) {
                    window.AppConfirm.open({
                        title: 'Create schedule?',
                        subtitle: 'Confirm staff working hours.',
                        message: `Create a schedule from ${startTime} to ${endTime}?`,
                        confirmText: 'Create',
                        confirmClass: 'btn-primary',
                        type: 'info',
                        onConfirm: () => {
                            if (csStaffId) csStaffId.value = String(currentStaffId);
                            if (csWorkingDate) csWorkingDate.value = workingDate;
                            if (csStartTime) csStartTime.value = startTime;
                            if (csEndTime) csEndTime.value = endTime;
                            createSchedule();
                        }
                    });
                    return;
                }

                if (csStaffId) csStaffId.value = String(currentStaffId);
                if (csWorkingDate) csWorkingDate.value = workingDate;
                if (csStartDate) csStartDate.value = workingDate;
                if (csEndDate) csEndDate.value = '';
                if (csStaffName) csStaffName.value = currentStaffName || '';
                if (csStaffCategory) csStaffCategory.value = currentStaffCategory || '';
                if (csStartTime) csStartTime.value = startTime;
                if (csEndTime) csEndTime.value = endTime;
                if (csBreakStart) csBreakStart.value = '';
                if (csBreakEnd) csBreakEnd.value = '';
                if (csRecurrenceType) csRecurrenceType.value = 'one_time';
                if (csDateLabel) csDateLabel.textContent = `Date: ${workingDate}`;
                if (csDayOff) {
                    csDayOff.checked = false;
                    updateDayOffModal();
                }

                document.querySelectorAll('.cs-weekly-day').forEach(cb => { cb.checked = false; });

                updateRecurrencePanels();
                createModal.show();
            }

            function collectScheduleFormData() {
                const formData = new FormData();

                formData.append('staff_id', csStaffId?.value || '');
                formData.append('recurrence_type', csRecurrenceType?.value || 'one_time');
                formData.append('start_time', csStartTime?.value || '');
                formData.append('end_time', csEndTime?.value || '');
                formData.append('working_date', csWorkingDate?.value || '');
                formData.append('start_date', csStartDate?.value || '');
                formData.append('end_date', csEndDate?.value || '');
                formData.append('break_start', csBreakStart?.value || '');
                formData.append('break_end', csBreakEnd?.value || '');
                if (csDayOff && csDayOff.checked) formData.append('is_working', '0');

                document.querySelectorAll('.cs-weekly-day').forEach(cb => {
                    if (cb.checked) formData.append('weekly_days[]', cb.value);
                });
                const monthlyDay = document.getElementById('cs-monthly-day')?.value;
                if (monthlyDay) formData.append('monthly_day', monthlyDay);
                const yearlyMonth = document.getElementById('cs-yearly-month')?.value;
                if (yearlyMonth) formData.append('yearly_month', yearlyMonth);
                const yearlyDay = document.getElementById('cs-yearly-day')?.value;
                if (yearlyDay) formData.append('yearly_day', yearlyDay);

                return formData;
            }

            function createSchedule() {
                const btn = createForm.querySelector('button[type="submit"]');
                const originalText = btn ? btn.textContent : '';
                if (btn) { btn.disabled = true; btn.textContent = 'Creating...'; }

                fetch('{{ route("schedule.store") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: collectScheduleFormData(),
                    redirect: 'manual'
                })
                    .then(res => {
                        if (res.type === 'opaqueredirect' || res.status === 302 || res.status === 301) {
                            location.reload();
                            return;
                        }
                        return res.json().catch(() => ({}));
                    })
                    .then(data => {
                        if (!data) return;
                        if (btn) { btn.disabled = false; btn.textContent = originalText; }
                        const message = data.message
                            || (data.errors ? Object.values(data.errors).flat().join(' ') : '')
                            || 'Could not create schedule';
                        window.AppToast?.show({ type: 'danger', title: 'Schedule error', message });
                    })
                    .catch(err => {
                        if (btn) { btn.disabled = false; btn.textContent = originalText; }
                        window.AppToast?.show({ type: 'danger', title: 'Schedule error', message: err.message });
                    });
            }

            function editSchedule(scheduleId) {
                window.location.href = scheduleEditBaseUrl.replace('__ID__', scheduleId);
            }

            // Initial render
            renderScheduleGrid();

            if (createForm) {
                createForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const staffId = csStaffId?.value;
                    const startTime = csStartTime?.value;
                    const endTime = csEndTime?.value;
                    const isDayOff = csDayOff ? csDayOff.checked : false;

                    if (!staffId || (!isDayOff && !startTime) || (!isDayOff && !endTime)) return;

                    if (!isDayOff && endTime <= startTime) {
                        window.AppToast?.show({ type: 'danger', title: 'Schedule error', message: 'End time must be after start time.' });
                        return;
                    }

                    createSchedule();
                });
            }

            if (csDayOff) {
                csDayOff.addEventListener('change', updateDayOffModal);
            }

            if (csRecurrenceType) {
                csRecurrenceType.addEventListener('change', function () {
                    const isOneTime = csRecurrenceType.value === 'one_time';
                    // Working date only applies to one-time; clear it for recurring so
                    // the backend validation (required_if) doesn't reject the submission.
                    if (csWorkingDate && !isOneTime) csWorkingDate.value = '';
                    if (isOneTime && csWorkingDate && !csWorkingDate.value && csStartDate) {
                        // Restore the clicked date when switching back to one-time.
                        csWorkingDate.value = csStartDate.value;
                    }
                    if (!isOneTime && csEndDate && !csEndDate.value) {
                        const d = new Date();
                        d.setMonth(d.getMonth() + 3);
                        csEndDate.value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
                    }
                    updateRecurrencePanels();
                });
            }

            // Navigation button listeners
            document.getElementById('btn-today').addEventListener('click', function () {
                const qs = currentStaffId ? `?staff_id=${currentStaffId}` : '';
                window.location.href = '{{ route("schedule.index") }}' + qs;
            });

            document.getElementById('btn-prev-week').addEventListener('click', function () {
                const ws = getCurrentWeekStart();
                const prev = new Date(ws);
                prev.setDate(prev.getDate() - 7);
                const weekDate = `${prev.getFullYear()}-${String(prev.getMonth() + 1).padStart(2, '0')}-${String(prev.getDate()).padStart(2, '0')}`;
                const qs = new URLSearchParams();
                if (currentStaffId) qs.set('staff_id', String(currentStaffId));
                qs.set('week_date', weekDate);
                window.location.href = '{{ route("schedule.index") }}' + `?${qs.toString()}`;
            });

            document.getElementById('btn-next-week').addEventListener('click', function () {
                const ws = getCurrentWeekStart();
                const next = new Date(ws);
                next.setDate(next.getDate() + 7);
                const weekDate = `${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, '0')}-${String(next.getDate()).padStart(2, '0')}`;
                const qs = new URLSearchParams();
                if (currentStaffId) qs.set('staff_id', String(currentStaffId));
                qs.set('week_date', weekDate);
                window.location.href = '{{ route("schedule.index") }}' + `?${qs.toString()}`;
            });
        });
    </script>
@endpush
