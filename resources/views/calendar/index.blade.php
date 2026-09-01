@extends('layouts.app')

@section('title', 'Calendar')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
    <style>
        :root {
            --calendar-border: #eef2f7;
            --calendar-border-hourly: #c8d0e0;
            --calendar-border-strong: #b8c3d8;
            --calendar-header-bg: #fff;
            --calendar-active-text: #3699ff;
            --calendar-inactive-text: #a1a5b7;
            --time-text: #7e8299;
            --day-active-bg: #f64e60;
        }

        .calendar-wrapper {
            background: #fff;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Top Navigation Bar */
        .calendar-nav {
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--calendar-border-hourly);
            height: 60px;
        }

        .nav-left,
        .nav-center,
        .nav-right {
            display: flex;
            align-items: center;
        }

        .nav-center {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            background: #ffffff;
            border: 1px solid #eef2f7;
            border-radius: 10px;
            padding: 0.35rem 0.75rem;
            gap: 0.75rem;
            box-shadow: 0 4px 14px rgba(24, 28, 50, 0.06);
        }

        .view-dropdown {
            background-color: #f1f4f9;
            border: none;
            padding: 0.4rem 1rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            color: #3f4254;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .date-display {
            font-weight: 600;
            font-size: 1rem;
            color: #181c32;
            margin: 0 1.5rem;
        }

        .nav-arrow {
            color: #7e8299;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 0.25rem;
        }

        .nav-arrow:hover {
            color: var(--calendar-active-text);
        }

        .btn-today {
            background: transparent;
            border: none;
            color: #3f4254;
            font-weight: 600;
            font-size: 0.9rem;
            margin-left: 1rem;
        }

        .nav-right {
            gap: 1.25rem;
        }

        .icon-btn {
            font-size: 1.25rem;
            color: #7e8299;
            cursor: pointer;
        }

        .icon-btn:hover {
            color: #3f4254;
        }

        /* Calendar Grid */
        .grid-container {
            flex: 1;
            overflow: auto;
            position: relative;
        }

        .grid-header {
            display: grid;
            grid-template-columns: 80px repeat(7, 1fr);
            border-bottom: 2px solid var(--calendar-border-strong);
            position: sticky;
            top: 0;
            z-index: 100;
            background: #fff;
        }

        .header-cell {
            padding: 0.75rem 0.5rem;
            text-align: center;
            border-right: 1px solid var(--calendar-border-strong);
            border-bottom: 1px solid var(--calendar-border-strong);
        }

        .header-cell:last-child {
            border-right: none;
        }

        .day-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 500;
            color: #7e8299;
            margin-bottom: 0.15rem;
        }

        .day-number {
            display: inline-block;
            font-size: 1.75rem;
            font-weight: 400;
            color: #181c32;
        }

        .active-day .day-label {
            color: var(--day-active-bg);
        }

        .active-day .day-number {
            color: var(--day-active-bg);
        }

        .header-cell.active-day {
            position: relative;
        }

        .timezone-cell {
            font-size: 0.65rem;
            color: #a1a5b7;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-right: 1px solid var(--calendar-border-hourly);
            border-bottom: 1.5px solid var(--calendar-border-strong);
            background: #fff;
        }

        /* Grid Body */
        .grid-body {
            display: grid;
            grid-template-columns: 80px repeat(7, 1fr);
            min-height: 1000px;
            position: relative;
        }

        .time-col {
            border-right: 1px solid var(--calendar-border-strong);
            background: #fff;
        }

        .hour-highlight-line {
            position: absolute;
            left: 80px;
            right: 0;
            height: 2px;
            background: #f64e60;
            top: 0;
            pointer-events: none;
            z-index: 50;
            box-shadow: 0 0 0 1px rgba(246, 78, 96, 0.25);
            display: none;
        }

        .hour-highlight-row {
            position: absolute;
            left: 80px;
            right: 0;
            background: rgba(246, 78, 96, 0.10);
            pointer-events: none;
            z-index: 40;
            display: none;
        }

        .time-slot {
            height: 120px;
            border-bottom: 1px solid var(--calendar-border-strong);
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            padding-right: 0.5rem;
        }

        .time-label {
            font-size: 0.75rem;
            color: #3f4254;
            font-weight: 600;
            position: absolute;
            right: 0.5rem;
        }

        .label-hour {
            top: 60px;
            transform: translateY(-50%);
        }

        .sub-tick {
            font-size: 0.65rem;
            color: #b5b5c3;
            height: 20px;
            display: flex;
            align-items: center;
        }

        .grid-cell {
            height: 120px;
            border-right: 1px solid var(--calendar-border-strong);
            border-bottom: 1px solid var(--calendar-border-strong);
            position: relative;
            background-image: repeating-linear-gradient(to bottom,
                    transparent 0,
                    transparent 119px,
                    var(--calendar-border-strong) 119px,
                    var(--calendar-border-strong) 120px);
            background-size: 100% 120px;
        }

        .grid-cell:last-child {
            border-right: none;
        }

        /* Staff Schedule Display */
        .staff-schedule-container {
            padding: 8px 4px;
            border-top: 1px solid var(--calendar-border-hourly);
            background: #f8f9fa;
            font-size: 0.7rem;
            max-height: none;
            overflow: visible;
        }

        .staff-schedule-item {
            padding: 6px 8px;
            margin: 4px 0;
            color: #ffffff;
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
            border-radius: 6px;
            background-color: rgb(11, 128, 67);
            box-shadow: 0 3px 10px rgba(54, 153, 255, 0.25);
            /* border-left: 3px solid #187de4; */
        }

        .staff-schedule-item strong {
            font-weight: 600;
            color: #ffffff;
            font-size: 0.72rem;
        }

        .staff-schedule-item .hours {
            color: rgba(255, 255, 255, 0.92);
            font-size: 0.68rem;
        }

        .calendar-appointment-time {
            font-size: 15px;
            font-weight: 600;
            line-height: 1.2;
        }

        .calendar-appointment-meta {
            font-size: 14px;
            font-weight: 600;
            line-height: 1.25;
            margin-top: 2px;
        }

        /* Appointment modal */
        #appointmentModal .modal-dialog {
            max-width: 760px;
        }

        #appointmentModal .modal-content {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 14px 36px rgba(24, 28, 50, 0.18);
        }

        #appointmentModal .modal-header {
            border-bottom: 1px solid #ebedf3;
            padding: 1.2rem 1.5rem;
        }

        #appointmentModal .modal-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #181c32;
        }

        #appointmentModal .modal-body {
            padding: 1.5rem;
            background: linear-gradient(180deg, #fbfcff 0%, #ffffff 100%);
        }

        #appointmentModal .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #3f4254;
            margin-bottom: 0.35rem;
        }

        #appointmentModal .form-control,
        #appointmentModal .form-select {
            border: 1px solid #e4e6ef;
            border-radius: 8px;
            color: #3f4254;
            font-size: 0.9rem;
            padding: 0.62rem 0.8rem;
            background-color: #fff;
            box-shadow: none;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        #appointmentModal textarea.form-control {
            min-height: 84px;
            resize: vertical;
        }

        #appointmentModal .form-control:focus,
        #appointmentModal .form-select:focus {
            border-color: #3699ff;
            box-shadow: 0 0 0 3px rgba(54, 153, 255, 0.12);
        }

        #appointmentModal hr {
            border-top: 1px solid #edf1f7;
            margin: 1rem 0;
            opacity: 1;
        }

        #appointmentModal .appointment-subtitle {
            font-size: 0.82rem;
            letter-spacing: 0.02em;
            color: #7e8299;
            text-transform: uppercase;
        }

        #appointmentModal .modal-footer {
            border-top: 1px solid #ebedf3;
            padding: 1rem 1.5rem;
            gap: 0.6rem;
        }

        #appointmentModal .btn-appointment-cancel {
            border: 1px solid #e4e6ef;
            background: #f8f9fc;
            color: #7e8299;
            font-weight: 600;
        }

        #appointmentModal .btn-appointment-cancel:hover {
            color: #3f4254;
            background: #eef2f7;
        }

        #appointmentModal .btn-appointment-save {
            background: #3699ff;
            border-color: #3699ff;
            color: #fff;
            font-weight: 600;
            min-width: 96px;
        }

        #appointmentModal .btn-appointment-save:hover {
            background: #1f86ee;
            border-color: #1f86ee;
        }

        #appointmentModal .btn-new-client {
            border: 1px solid #d8deea;
            background: #f8f9fc;
            color: #3f4254;
            font-weight: 600;
            white-space: nowrap;
        }

        #appointmentModal .btn-new-client:hover {
            background: #eef2f7;
            color: #1f2937;
        }

        #newClientModal .modal-content {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 14px 32px rgba(24, 28, 50, 0.16);
        }

        #newClientModal .modal-header,
        #newClientModal .modal-footer {
            border-color: #ebedf3;
        }

        #newClientModal .modal-header {
            padding: 1.1rem 1.35rem;
        }

        #newClientModal .modal-title {
            font-size: 1rem;
            font-weight: 700;
            color: #181c32;
        }

        #newClientModal .modal-body {
            padding: 1.35rem;
            background: linear-gradient(180deg, #fbfcff 0%, #ffffff 100%);
        }

        #newClientModal .form-label {
            font-size: 0.84rem;
            font-weight: 600;
            color: #3f4254;
            margin-bottom: 0.35rem;
        }

        #newClientModal .form-control {
            border: 1px solid #e4e6ef;
            border-radius: 8px;
            color: #3f4254;
            font-size: 0.9rem;
            padding: 0.62rem 0.8rem;
            background: #fff;
            box-shadow: none;
        }

        #newClientModal .form-control:focus {
            border-color: #3699ff;
            box-shadow: 0 0 0 3px rgba(54, 153, 255, 0.12);
        }

        #newClientModal .modal-footer {
            padding: 0.95rem 1.35rem;
            gap: 0.6rem;
        }

        #newClientModal .btn-new-client-cancel {
            border: 1px solid #e4e6ef;
            background: #f8f9fc;
            color: #7e8299;
            font-weight: 600;
        }

        #newClientModal .btn-new-client-cancel:hover {
            background: #eef2f7;
            color: #3f4254;
        }

        #newClientModal .btn-new-client-save {
            background: #3699ff;
            border-color: #3699ff;
            color: #fff;
            font-weight: 600;
            min-width: 108px;
        }

        #newClientModal .btn-new-client-save:hover {
            background: #1f86ee;
            border-color: #1f86ee;
        }

        #appointment-readonly-details,
        #appointment-readonly-details-card {
            background: linear-gradient(180deg, #fbfcff 0%, #ffffff 100%);
            border: 1px solid #e9edf5;
            border-radius: 12px;
            padding: 0.8rem 0.9rem;
            max-width: 420px;
            margin: 0 auto;
            box-shadow: 0 4px 14px rgba(24, 28, 50, 0.08);
        }

        #appointment-readonly-details .detail-row,
        #appointment-readonly-details-card .detail-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.55rem;
            padding: 0.34rem 0;
            border-bottom: 1px dashed #eef2f7;
        }

        #appointment-readonly-details .detail-row:last-child,
        #appointment-readonly-details-card .detail-row:last-child {
            border-bottom: 0;
        }

        #appointment-readonly-details .detail-label,
        #appointment-readonly-details-card .detail-label {
            color: #7e8299;
            font-size: 0.74rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            min-width: 84px;
            flex: 0 0 84px;
        }

        #appointment-readonly-details .detail-value,
        #appointment-readonly-details-card .detail-value {
            color: #181c32;
            font-size: 0.84rem;
            font-weight: 400;
            line-height: 1.3;
            word-break: break-word;
            flex: 1 1 auto;
            text-align: left;
        }

        #appointment-details-card {
            position: fixed;
            top: 110px;
            right: 24px;
            z-index: 1200;
            width: 360px;
            max-width: calc(100vw - 24px);
            background: #ffffff;
            border: 1px solid #e7ebf3;
            border-radius: 16px;
            box-shadow: 0 12px 28px rgba(60, 64, 67, 0.24);
            overflow: hidden;
        }

        #appointment-details-card .card-head {
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 0.85rem 1rem 0.8rem;
            border-bottom: 1px solid #edf2f7;
            background: #ffffff;
        }

        #appointment-details-card .card-head::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: #1a73e8;
        }

        #appointment-details-card .card-heading {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        #appointment-details-card .card-title {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
            color: #202124;
            letter-spacing: 0.01em;
        }

        #appointment-details-card .card-status-chip {
            display: inline-flex;
            align-items: center;
            align-self: flex-start;
            border-radius: 999px;
            padding: 0.14rem 0.58rem;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        #appointment-details-card .card-status-chip.status-tentative {
            color: #995700;
            background: #fff3e0;
            border: 1px solid #ffdeaa;
        }

        #appointment-details-card .card-status-chip.status-pending {
            color: #92400e;
            background: #fef3c7;
            border: 1px solid #fde68a;
        }

        #appointment-details-card .card-status-chip.status-booked {
            color: #1e40af;
            background: #dbeafe;
            border: 1px solid #bfdbfe;
        }

        #appointment-details-card .card-status-chip.status-confirmed {
            color: #3730a3;
            background: #e0e7ff;
            border: 1px solid #c7d2fe;
        }

        #appointment-details-card .card-status-chip.status-completed {
            color: #065f46;
            background: #d1fae5;
            border: 1px solid #a7f3d0;
        }

        #appointment-details-card .card-status-chip.status-cancelled {
            color: #991b1b;
            background: #fee2e2;
            border: 1px solid #fca5a5;
        }

        #appointment-details-card .card-status-chip.status-no_show {
            color: #5b21b6;
            background: #ede9fe;
            border: 1px solid #ddd6fe;
        }

        #appointment-details-card .card-close {
            border: 0;
            background: transparent;
            color: #7e8299;
            font-size: 1.1rem;
            line-height: 1;
            cursor: pointer;
        }

        #appointment-details-card .card-body {
            padding: 0.82rem 1rem 0.9rem;
            display: flex;
            flex-direction: column;
        }

        #appointment-details-card,
        #appointment-details-card .card-title,
        #appointment-details-card .detail-label,
        #appointment-details-card .detail-value,
        #appointment-readonly-details .detail-label,
        #appointment-readonly-details .detail-value {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        /* Month view (FullCalendar) - keep consistent with existing UI */
        #calendar-month-container {
            padding: 0.75rem;
            background: #fff;
        }

        #calendar-month-container .fc {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        #calendar-month-container .fc .fc-daygrid-day-number {
            font-size: 0.85rem;
            font-weight: 600;
            color: #181c32;
        }

        #calendar-month-container .fc .fc-daygrid-day.fc-day-today {
            background: rgba(54, 153, 255, 0.06);
        }

        #calendar-month-container .fc .fc-daygrid-event {
            border-radius: 8px;
            padding: 2px 6px;
            box-shadow: 0 2px 10px rgba(24, 28, 50, 0.04);
        }

        #calendar-month-container .fc-staff-appointment-inner {
            display: flex;
            gap: 0.35rem;
            align-items: center;
            min-width: 0;
        }

        #calendar-month-container .fc-staff-appointment-time {
            font-weight: 700;
            font-size: 0.72rem;
            color: #3f4254;
            white-space: nowrap;
        }

        #calendar-month-container .fc-staff-appointment-staff {
            font-weight: 600;
            font-size: 0.72rem;
            color: #3f4254;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            min-width: 0;
        }
    </style>
@endpush

@section('content')

    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->


        <!-- Page Content -->
        <div id="page-content-wrapper">
            <div class="calendar-wrapper">
                <!-- Calendar Navigation -->
                <div class="calendar-nav">
                    <div class="nav-left">
                        <i class='bx bx-slider-alt icon-btn'></i>
                    </div>
                    <div class="nav-center">
                        <select class="view-dropdown" id="calendar-view-select">
                            <option value="day" {{ ($view ?? 'week') === 'day' ? 'selected' : '' }}>Day View</option>
                            <option value="week" {{ ($view ?? 'week') === 'week' ? 'selected' : '' }}>Week View</option>
                            <option value="month" {{ ($view ?? 'week') === 'month' ? 'selected' : '' }}>Month View</option>
                            {{-- <option value="year">Year View</option> --}}
                        </select>
                        <span class="date-display" id="current-date-header"></span>
                        <div class="nav-arrows d-flex gap-2">
                            <i class='bx bx-chevron-left nav-arrow' id="prev-week"></i>
                            <i class='bx bx-chevron-right nav-arrow' id="next-week"></i>
                        </div>
                        <button class="btn-today" id="btn-today">Today</button>
                    </div>
                    <div class="nav-right d-flex">
                        <i class='bx bx-plus icon-btn'></i>
                        <i class='bx bx-time-five icon-btn'></i>
                    </div>
                </div>

                <div class="calendar-filters d-flex flex-wrap gap-2 mb-3">
                    <select id="calendar-filter-location" class="form-select form-select-sm" style="max-width: 210px;">
                        <option value="">All locations</option>
                        @foreach($locations ?? [] as $location)
                            <option value="{{ $location->id }}" {{ (string) ($filters['location_id'] ?? '') === (string) $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                        @endforeach
                    </select>
                    <select id="calendar-filter-staff" class="form-select form-select-sm" style="max-width: 210px;">
                        <option value="">All staff</option>
                        @foreach($staffs ?? [] as $staff)
                            <option value="{{ $staff->id }}" {{ (string) ($filters['staff_id'] ?? '') === (string) $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                        @endforeach
                    </select>
                    <select id="calendar-filter-service" class="form-select form-select-sm" style="max-width: 210px;">
                        <option value="">All services</option>
                        @foreach($services ?? [] as $service)
                            <option value="{{ $service->id }}" {{ (string) ($filters['service_id'] ?? '') === (string) $service->id ? 'selected' : '' }}>{{ $service->name }}</option>
                        @endforeach
                    </select>
                    <select id="calendar-filter-status" class="form-select form-select-sm" style="max-width: 180px;">
                        <option value="">All statuses</option>
                        <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending
                        </option>
                        <option value="booked" {{ ($filters['status'] ?? '') === 'booked' ? 'selected' : '' }}>Booked</option>
                        <option value="confirmed" {{ ($filters['status'] ?? '') === 'confirmed' ? 'selected' : '' }}>Confirmed
                        </option>
                        <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Completed
                        </option>
                        <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled
                        </option>
                        <option value="no_show" {{ ($filters['status'] ?? '') === 'no_show' ? 'selected' : '' }}>No Show
                        </option>
                    </select>
                    <button type="button" class="btn btn-light btn-sm" id="calendar-reset-filters">Reset filters</button>
                </div>

                <!-- Calendar Content -->
                <div class="grid-container">
                    <div class="grid-header" id="calendar-grid-header">
                        <div class="timezone-cell">
                            <i class='bx bx-chevron-down'></i>
                            <span>GMT-04:00</span>
                        </div>
                        <!-- Header cells will be generated by JS -->
                    </div>

                    <div class="grid-body" id="calendar-grid-body">
                        <!-- Time Column 24 hours (will be generated by JS) -->
                        <div class="time-col" id="calendar-time-col"></div>

                        <!-- Day Columns (7 columns - will be generated by JS) -->
                    </div>

                    <!-- Month Grid (FullCalendar) -->
                    <div id="calendar-month-container" class="d-none">
                        <div id="staff-month-calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="appointment-details-card" class="d-none">
        <div class="card-head">
            <div class="card-heading">
                <h6 class="card-title">Appointment Details</h6>
                <span id="readonly-card-status-chip" class="card-status-chip d-none"></span>
            </div>
            <button type="button" class="card-close" id="close-appointment-details-card">&times;</button>
        </div>
        <div class="card-body" id="appointment-readonly-details-card">
            <div class="detail-row">
                <div class="detail-label">Staff</div>
                <div class="detail-value" id="readonly-card-staff"></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Service</div>
                <div class="detail-value" id="readonly-card-service"></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Client</div>
                <div class="detail-value" id="readonly-card-client"></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Start</div>
                <div class="detail-value" id="readonly-card-start"></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">End</div>
                <div class="detail-value" id="readonly-card-end"></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status</div>
                <div class="detail-value" id="readonly-card-status"></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Notes</div>
                <div class="detail-value" id="readonly-card-notes"></div>
            </div>
        </div>
        <div id="card-quick-links" class="px-3 py-2 border-top bg-light d-flex gap-2 flex-wrap align-items-center"
            style="font-size: 0.8rem;">
            <a id="card-link-client" href="#" target="_blank" class="text-primary text-decoration-none fw-semibold"><i
                    class="bx bx-user me-1"></i>View Client</a>
            <a id="card-link-invoice" href="#" target="_blank"
                class="text-success text-decoration-none fw-semibold d-none"><i class="bx bx-receipt me-1"></i>View Invoice
                (<span id="card-link-invoice-num"></span>)</a>
            <a id="card-link-forms" href="#" target="_blank" class="text-info text-decoration-none fw-semibold d-none"><i
                    class="bx bx-file me-1"></i>View Forms</a>
        </div>
        <div id="card-quick-actions" class="p-2 border-top d-flex gap-1 flex-wrap align-items-center">
            <button type="button" class="btn btn-sm btn-outline-primary btn-quick-status d-none" data-status="confirmed"><i
                    class='bx bx-check-double me-1'></i>Confirm</button>
            <button type="button" class="btn btn-sm btn-outline-success btn-quick-status d-none" data-status="completed"><i
                    class='bx bx-check-circle me-1'></i>Complete</button>
            <button type="button" class="btn btn-sm btn-outline-danger btn-quick-status d-none" data-status="cancelled"><i
                    class='bx bx-x-circle me-1'></i>Cancel</button>
            <button type="button" class="btn btn-sm btn-outline-secondary btn-quick-status d-none" data-status="no_show"><i
                    class='bx bx-user-x me-1'></i>No-Show</button>
        </div>
    </div>

    {{-- ============================================================
    COMPLETED APPOINTMENT READ-ONLY MODAL (Workflow 2)
    Opens when a user clicks a COMPLETED appointment in the calendar.
    ============================================================ --}}
    <div class="modal fade" id="completedAppointmentModal" tabindex="-1" aria-labelledby="completed-modal-title"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <span class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center"
                            style="width:36px;height:36px;flex-shrink:0;">
                            <i class="bx bx-check" style="font-size:1.2rem;"></i>
                        </span>
                        <div>
                            <h5 class="modal-title mb-0" id="completed-modal-title">Completed Appointment</h5>
                            <p class="text-muted mb-0" style="font-size:0.8rem;">Read-only — this appointment is complete.
                            </p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    <div id="completed-modal-loading" class="text-center py-5">
                        <div class="spinner-border text-success" role="status"><span
                                class="visually-hidden">Loading...</span></div>
                    </div>
                    <div id="completed-modal-content" class="d-none">
                        {{-- Client --}}
                        <div class="card border-0 bg-light rounded-3 p-3 mb-3">
                            <div class="text-uppercase fw-semibold text-muted"
                                style="font-size:0.72rem;letter-spacing:.06em;">Client</div>
                            <div class="fw-bold" id="cmod-client-name"></div>
                            <div class="small text-muted" id="cmod-client-phone"></div>
                            <div class="small text-muted" id="cmod-client-email"></div>
                        </div>
                        <div class="row g-3 mb-3">
                            {{-- Appointment --}}
                            <div class="col-md-6">
                                <div class="card border-0 bg-light rounded-3 p-3 h-100">
                                    <div class="text-uppercase fw-semibold text-muted mb-2"
                                        style="font-size:0.72rem;letter-spacing:.06em;">Appointment</div>
                                    <div class="small mb-1"><span class="text-muted">Date:</span> <strong
                                            id="cmod-appt-date"></strong></div>
                                    <div class="small mb-1"><span class="text-muted">Start:</span> <strong
                                            id="cmod-appt-start"></strong></div>
                                    <div class="small mb-1"><span class="text-muted">End:</span> <strong
                                            id="cmod-appt-end"></strong></div>
                                    <div class="small mb-1"><span class="text-muted">Duration:</span> <strong
                                            id="cmod-appt-duration"></strong></div>
                                    <div class="small mb-1"><span class="text-muted">Status:</span>
                                        <span class="badge bg-success" id="cmod-appt-status">Completed</span>
                                    </div>
                                    <div class="small mt-2" id="cmod-notes-row">
                                        <span class="text-muted">Notes:</span> <span id="cmod-appt-notes"
                                            class="fst-italic"></span>
                                    </div>
                                </div>
                            </div>
                            {{-- Practitioner & Service --}}
                            <div class="col-md-6">
                                <div class="card border-0 bg-light rounded-3 p-3 mb-2">
                                    <div class="text-uppercase fw-semibold text-muted"
                                        style="font-size:0.72rem;letter-spacing:.06em;">Practitioner</div>
                                    <div class="fw-bold" id="cmod-staff-name"></div>
                                </div>
                                <div class="card border-0 bg-light rounded-3 p-3">
                                    <div class="text-uppercase fw-semibold text-muted"
                                        style="font-size:0.72rem;letter-spacing:.06em;">Service</div>
                                    <div class="fw-bold" id="cmod-service-name"></div>
                                    <div class="small text-muted">Price: <strong id="cmod-service-price"></strong></div>
                                </div>
                            </div>
                        </div>
                        {{-- Invoice --}}
                        <div id="cmod-invoice-section" class="card border-0 bg-light rounded-3 p-3 mb-3 d-none">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="text-uppercase fw-semibold text-muted"
                                    style="font-size:0.72rem;letter-spacing:.06em;">Invoice</div>
                                <a id="cmod-invoice-link" href="#" target="_blank"
                                    class="btn btn-sm btn-outline-success py-0">
                                    <i class="bx bx-receipt me-1"></i>View Invoice
                                </a>
                            </div>
                            <div class="row g-2">
                                <div class="col-6 small"><span class="text-muted">Invoice #:</span> <strong
                                        id="cmod-inv-number"></strong></div>
                                <div class="col-6 small"><span class="text-muted">Status:</span> <strong
                                        id="cmod-inv-status"></strong></div>
                                <div class="col-4 small"><span class="text-muted">Total:</span> <strong
                                        id="cmod-inv-total"></strong></div>
                                <div class="col-4 small"><span class="text-muted">Paid:</span> <strong
                                        id="cmod-inv-paid"></strong></div>
                                <div class="col-4 small"><span class="text-muted">Balance:</span> <strong
                                        id="cmod-inv-balance"></strong></div>
                            </div>
                        </div>
                        {{-- Payments --}}
                        <div id="cmod-payments-section" class="card border-0 bg-light rounded-3 p-3 d-none">
                            <div class="text-uppercase fw-semibold text-muted mb-2"
                                style="font-size:0.72rem;letter-spacing:.06em;">Payment History</div>
                            <table class="table table-sm table-borderless mb-0">
                                <thead class="text-muted" style="font-size:0.78rem;">
                                    <tr>
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th>Transaction ID</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="cmod-payments-tbody"></tbody>
                            </table>
                        </div>
                        <div id="cmod-no-invoice" class="text-muted small fst-italic d-none">No invoice linked to this
                            appointment.</div>
                    </div>
                    <div id="completed-modal-error" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer border-top-0">
                    <a id="cmod-go-to-invoice" href="#" class="btn btn-success d-none">
                        <i class="bx bx-receipt me-1"></i>Open Invoice
                    </a>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade app-modal" id="appointmentModal" tabindex="-1" aria-labelledby="appointment-modal-title"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <form id="appointment-form" data-app-managed="true">
                    <div class="modal-header">
                        <div class="modal-heading">
                            <div class="modal-icon" aria-hidden="true"><i class="bx bx-calendar-plus"></i></div>
                            <div>
                                <h5 class="modal-title" id="appointment-modal-title">New Appointment</h5>
                                <p class="modal-subtitle">Manage patient, staff, service, schedule, and status details.</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="appointment-id" />
                        <div id="appointment-form-fields" class="appointment-form-grid">
                            <div class="mb-2">
                                <label class="form-label">Location</label>
                                <select id="appt-location" class="form-select"></select>
                                <div id="appt-location-help" class="form-text text-muted"></div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Staff <span class="required-mark">*</span></label>
                                <select id="appt-staff" class="form-select" required></select>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Service <span class="required-mark">*</span></label>
                                <select id="appt-service" class="form-select" required></select>
                                <div id="service-cost-duration-info" class="mt-1 small text-muted d-none">
                                    Cost: <strong id="selected-service-cost" class="text-dark">$0.00</strong> &bull;
                                    Duration: <strong id="selected-service-duration" class="text-dark">0 min</strong>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label" for="appt-client-search">Client <span class="required-mark">*</span></label>
                                <!-- <div class="input-group mb-2">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="search" id="appt-client-search" class="form-control" placeholder="Search existing clients by name, phone, or email" autocomplete="off" aria-label="Search existing clients" />
                                </div> -->
                                <div class="d-flex gap-2">
                                    <select id="appt-client" class="form-select"></select>
                                    <button type="button" class="btn btn-new-client" id="open-new-client-modal">+
                                        New</button>
                                </div>
                                <div id="appt-client-snapshot" class="alert alert-light border p-3 mt-2 d-none"
                                    style="font-size: 0.82rem; border-radius: 8px;">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong id="snapshot-client-name" class="fs-6 text-dark"></strong>
                                            <span id="snapshot-vip-badge" class="badge bg-warning text-dark ms-1 d-none"><i
                                                    class="bx bx-star"></i> VIP</span>
                                        </div>
                                        <a id="snapshot-full-profile-link" href="#" target="_blank"
                                            class="btn btn-xs btn-outline-primary fw-semibold"><i
                                                class="bx bx-external-link me-1"></i>View Full Client</a>
                                    </div>
                                    <div class="row g-2 text-muted mb-2">
                                        <div class="col-6 col-sm-3">Last Visit: <strong id="snapshot-last-visit"
                                                class="text-dark">-</strong></div>
                                        <div class="col-6 col-sm-3">Next Appt: <strong id="snapshot-next-appt"
                                                class="text-dark">-</strong></div>
                                        <div class="col-6 col-sm-3">Total Visits: <strong id="snapshot-total-appts"
                                                class="text-dark">0</strong></div>
                                        <div class="col-6 col-sm-3">No Shows: <strong id="snapshot-no-show"
                                                class="text-danger">0</strong></div>
                                    </div>
                                    <div
                                        class="d-flex flex-wrap justify-content-between align-items-center pt-2 border-top">
                                        <div>Outstanding: <strong id="snapshot-outstanding" class="text-dark">$0.00</strong>
                                        </div>
                                        <div id="snapshot-notes-container" class="text-muted text-truncate d-none"
                                            style="max-width: 250px;">Note: <span id="snapshot-notes"></span></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label">Start <span class="required-mark">*</span></label>
                                    <input type="datetime-local" id="appt-start" class="form-control" required />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">End <span class="required-mark">*</span></label>
                                    <input type="datetime-local" id="appt-end" class="form-control" required />
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Status <span class="required-mark">*</span></label>
                                <select id="appt-status" class="form-select" required>
                                    <option value="pending">Pending</option>
                                    <option value="booked">Booked</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="no_show">No Show</option>
                                </select>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Notes</label>
                                <textarea id="appt-notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <div id="appointment-readonly-details" class="d-none">
                            <div class="card border-0 bg-light p-3 mb-2" style="border-radius: 10px;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <span class="badge bg-success px-3 py-2 fs-6 fw-bold"><i class="bx bx-check-circle me-1"></i> COMPLETED</span>
                                        <span id="readonly-payment-badge" class="badge px-3 py-2 fs-6 fw-bold ms-2"></span>
                                    </div>
                                    <a id="readonly-invoice-link" href="#" class="btn btn-sm btn-outline-primary fw-semibold d-none" target="_blank">
                                        <i class="bx bx-receipt me-1"></i> View Invoice <span id="readonly-invoice-num"></span>
                                    </a>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="text-muted small">Client Name</div>
                                        <div class="fw-bold text-dark fs-6" id="readonly-client"></div>
                                        <div class="small text-muted" id="readonly-client-contact"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="text-muted small">Practitioner / Staff</div>
                                        <div class="fw-bold text-dark fs-6" id="readonly-staff"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="text-muted small">Service</div>
                                        <div class="fw-bold text-dark fs-6" id="readonly-service"></div>
                                        <div class="small text-muted">Duration: <span id="readonly-duration" class="fw-medium text-dark"></span></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="text-muted small">Date & Time</div>
                                        <div class="fw-semibold text-dark" id="readonly-start-end"></div>
                                    </div>
                                </div>
                                <hr class="my-3 text-muted opacity-25" />
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="text-muted small">Invoice Amount</div>
                                        <div class="fw-bold text-dark fs-6" id="readonly-invoice-total">$0.00</div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-muted small">Paid Amount</div>
                                        <div class="fw-bold text-success fs-6" id="readonly-invoice-paid">$0.00</div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-muted small">Remaining Balance</div>
                                        <div class="fw-bold text-danger fs-6" id="readonly-invoice-balance">$0.00</div>
                                    </div>
                                </div>

                                <div class="mt-2 text-muted small" id="readonly-payment-method-row">
                                    Payment Method: <strong id="readonly-payment-method" class="text-dark">N/A</strong>
                                </div>

                                <div class="mt-3 pt-2 border-top">
                                    <div class="text-muted small">Notes</div>
                                    <div class="text-dark small" id="readonly-notes">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-appointment-cancel" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-appointment-save" id="appt-save-btn">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Step 1 Client Search Modal -->
    <div class="modal fade app-modal" id="clientSearchModal" tabindex="-1" aria-labelledby="clientSearchModalTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-heading">
                        <div class="modal-icon modal-icon-info" aria-hidden="true"><i class="bx bx-user-search"></i></div>
                        <div>
                            <h5 class="modal-title" id="clientSearchModalTitle">Step 1: Select Client</h5>
                            <p class="modal-subtitle">Search existing patient or create a new client profile.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Search Client</label>
                        <input type="search" id="client-search-step1-input" class="form-control"
                            placeholder="Search by phone, name, or email..." autocomplete="off" />
                    </div>
                    <div id="client-search-step1-results" class="list-group mb-3 d-none"
                        style="max-height: 220px; overflow-y: auto;"></div>
                    <div id="client-search-step1-empty" class="alert alert-light text-muted small d-none">
                        No matching client found.
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-new-client" id="step1-open-new-client-modal">+ Create New
                        Client</button>
                    <button type="button" class="btn btn-new-client-cancel" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 1b Add New Client Modal -->
    <div class="modal fade app-modal" id="newClientModal" tabindex="-1" aria-labelledby="newClientModalTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="new-client-form" data-app-managed="true">
                    <div class="modal-header">
                        <div class="modal-heading">
                            <div class="modal-icon modal-icon-info" aria-hidden="true"><i class="bx bx-user-plus"></i></div>
                            <div>
                                <h5 class="modal-title" id="newClientModalTitle">Add New Client</h5>
                                <p class="modal-subtitle">Create a patient record without leaving the calendar.</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        @include('clients.partials.form-fields', ['idPrefix' => 'new-client-', 'client' => null])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-new-client-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-new-client-save">Add Client</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script>
        // Expose server-side data for JS (staffs and clients)
        window.CALENDAR_DATA = {
            staffs: @json($staffs ?? []),
            clients: @json($clients ?? []),
            services: @json($services ?? []),
            locations: @json($locations ?? []),
            calendarMonth: @json(isset($calendarMonth) ? $calendarMonth->format('Y-m') : now()->format('Y-m')),
            monthEvents: @json($monthEvents ?? [])
        };

        document.addEventListener('DOMContentLoaded', function () {
            function pad2(n) { return String(n).padStart(2, '0'); }
            function escapeHtml(str) {
                return String(str ?? '').replace(/[&<>"']/g, s => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[s]));
            }
            const calendarBaseUrl = @json(url('/calendar'));
            function calendarUrl(path = '') {
                const suffix = String(path || '').replace(/^\/+/, '');
                return suffix ? `${calendarBaseUrl}/${suffix}` : calendarBaseUrl;
            }

            // Initialize week start (Monday)
            let currentWeekStart = new Date();
            const day = currentWeekStart.getDay();
            const diff = currentWeekStart.getDate() - day + (day === 0 ? -6 : 1);
            currentWeekStart.setDate(diff);
            currentWeekStart.setHours(0, 0, 0, 0);
            let currentView = 'week';
            let visibleDays = 7;

            const header = document.getElementById('calendar-grid-header');
            const dateDisplay = document.getElementById('current-date-header');
            const gridBody = document.getElementById('calendar-grid-body');
            const timeCol = document.getElementById('calendar-time-col');
            const viewSelect = document.getElementById('calendar-view-select');
            const appointmentDetailsCard = document.getElementById('appointment-details-card');
            const closeDetailsCardBtn = document.getElementById('close-appointment-details-card');
            const monthContainer = document.getElementById('calendar-month-container');
            const monthCalendarRoot = document.getElementById('staff-month-calendar');
            let monthCalendar = null;
            if (viewSelect && viewSelect.value) currentView = viewSelect.value;

            function showPageNotice(message, type = 'danger', timeout = 4200) {
                window.AppToast?.show({
                    type: type === 'success' ? 'success' : 'danger',
                    title: type === 'success' ? 'Success' : 'Calendar notice',
                    message,
                    delay: timeout
                });
            }

            function monthKeyFromDate(d) {
                const date = new Date(d);
                return `${date.getFullYear()}-${pad2(date.getMonth() + 1)}`;
            }

            function updateMonthHeader(d) {
                if (!dateDisplay) return;
                const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                const date = new Date(d);
                dateDisplay.textContent = `${monthNames[date.getMonth()]} ${date.getFullYear()}`;
            }

            function ensureMonthCalendar() {
                if (monthCalendar || !monthCalendarRoot) return monthCalendar;
                if (typeof FullCalendar === 'undefined' || !FullCalendar.Calendar) {
                    showPageNotice('Month view could not be loaded. Please refresh the page.', 'danger', 5200);
                    return null;
                }

                const initialMonth = (window.CALENDAR_DATA && window.CALENDAR_DATA.calendarMonth) ? `${window.CALENDAR_DATA.calendarMonth}-01` : undefined;
                const events = (window.CALENDAR_DATA && Array.isArray(window.CALENDAR_DATA.monthEvents)) ? window.CALENDAR_DATA.monthEvents : [];

                monthCalendar = new FullCalendar.Calendar(monthCalendarRoot, {
                    initialView: 'dayGridMonth',
                    initialDate: initialMonth,
                    timeZone: 'local',
                    height: 'auto',
                    headerToolbar: false,
                    fixedWeekCount: false,
                    showNonCurrentDates: true,
                    dayMaxEventRows: 4,
                    events: events.map(e => ({
                        id: e.id,
                        title: e.title,
                        start: e.start,
                        end: e.end,
                        color: e.color,
                        extendedProps: { staff: e.staff, status: e.status }
                    })),
                    eventClassNames: () => ['fc-staff-appointment'],
                    eventDidMount: function (info) {
                        const statusColor = info.event.backgroundColor || '#3699ff';
                        info.el.style.backgroundColor = '#f1f4f9';
                        info.el.style.borderColor = '#eef2f7';
                        info.el.style.borderLeft = `3px solid ${statusColor}`;
                        info.el.style.color = '#3f4254';
                    },
                    eventContent: function (arg) {
                        const staff = arg.event.extendedProps.staff || arg.event.title || '';
                        const start = arg.event.start;
                        const time = start ? start.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) : '';

                        const wrap = document.createElement('div');
                        wrap.className = 'fc-staff-appointment-inner';
                        wrap.innerHTML = `<span class="fc-staff-appointment-time">${escapeHtml(time)}</span><span class="fc-staff-appointment-staff">${escapeHtml(staff)}</span>`;
                        return { domNodes: [wrap] };
                    },
                    eventClick: function (info) {
                        info.jsEvent.preventDefault();
                        const status = (info.event.extendedProps && info.event.extendedProps.status)
                            ? info.event.extendedProps.status
                            : (info.event._def && info.event._def.extendedProps ? info.event._def.extendedProps.status : '');
                        if (String(status).toLowerCase() === 'completed') {
                            openCompletedAppointmentModal(info.event.id, info.jsEvent);
                        } else if (typeof openAppointmentModalForEdit === 'function') {
                            openAppointmentModalForEdit(info.event.id, info.jsEvent);
                        }
                    }
                });

                monthCalendar.render();
                updateMonthHeader(monthCalendar.getDate());
                return monthCalendar;
            }

            function showMonthView() {
                const cal = ensureMonthCalendar();
                if (!cal) return;
                if (monthContainer) monthContainer.classList.remove('d-none');
                if (header) header.classList.add('d-none');
                if (gridBody) gridBody.classList.add('d-none');
                updateMonthHeader(cal.getDate());
            }

            function showTimeGridView() {
                if (monthContainer) monthContainer.classList.add('d-none');
                if (header) header.classList.remove('d-none');
                if (gridBody) gridBody.classList.remove('d-none');
            }

            function navigateMonth(deltaMonths) {
                const base = monthCalendar ? monthCalendar.getDate() : new Date();
                const target = new Date(base.getFullYear(), base.getMonth() + deltaMonths, 1);
                const url = copyFiltersToUrl(new URL(window.location.href));
                url.searchParams.set('view', 'month');
                url.searchParams.set('month', monthKeyFromDate(target));
                window.location.href = url.toString();
            }

            const openNewClientModalBtn = document.getElementById('open-new-client-modal');
            const newClientModalEl = document.getElementById('newClientModal');
            const newClientModal = new bootstrap.Modal(newClientModalEl);
            const newClientForm = document.getElementById('new-client-form');
            // Generate 24-hour time column
            function generateTimeColumn() {
                timeCol.innerHTML = '';
                for (let h = 0; h < 24; h++) {
                    const slot = document.createElement('div');
                    slot.className = 'time-slot';
                    const period = h >= 12 ? 'PM' : 'AM';
                    const hour12 = h % 12 === 0 ? 12 : h % 12;
                    slot.innerHTML = `<span class="time-label label-hour">${String(hour12).padStart(2, '0')}:00 ${period}</span>`;
                    timeCol.appendChild(slot);
                }
            }

            // Set grid body height for 24 hours (24 * 120px per slot)
            function setGridHeight() {
                gridBody.style.minHeight = (24 * 120) + 'px';
                gridBody.style.height = (24 * 120) + 'px';
            }

            function clearHourHighlights() {
                document.querySelectorAll('.hour-highlight-line, .hour-highlight-row').forEach(el => el.remove());
            }

            function render10amHourHighlight() {
                clearHourHighlights();

                // Only for time grid view (week/day)
                if (!gridBody) return;

                const hourToHighlight = 10; // 10:00 AM
                const colHeight = gridBody.clientHeight || (24 * 120);
                const top = (hourToHighlight * 60) / (24 * 60) * colHeight;

                const row = document.createElement('div');
                row.className = 'hour-highlight-row';
                row.style.top = `${top}px`;
                row.style.height = `120px`;
                gridBody.appendChild(row);

                const line = document.createElement('div');
                line.className = 'hour-highlight-line';
                line.style.top = `${top}px`;
                gridBody.appendChild(line);
            }

            const modalEl = document.getElementById('appointmentModal');
            const appointmentModal = new bootstrap.Modal(modalEl);
            const appointmentForm = document.getElementById('appointment-form');
            const modalTitle = document.getElementById('appointment-modal-title');
            const apptIdField = document.getElementById('appointment-id');
            const appointmentFormFields = document.getElementById('appointment-form-fields');
            const appointmentReadonlyDetails = document.getElementById('appointment-readonly-details');
            const staffField = document.getElementById('appt-staff');
            const locationField = document.getElementById('appt-location');
            const locationHelp = document.getElementById('appt-location-help');
            const serviceField = document.getElementById('appt-service');
            const clientField = document.getElementById('appt-client');
            const clientSearchField = document.getElementById('appt-client-search');
            const startField = document.getElementById('appt-start');
            const endField = document.getElementById('appt-end');
            const statusField = document.getElementById('appt-status');
            const notesField = document.getElementById('appt-notes');
            const apptSaveBtn = document.getElementById('appt-save-btn');
            const readonlyStaff = document.getElementById('readonly-staff');
            const readonlyService = document.getElementById('readonly-service');
            const readonlyClient = document.getElementById('readonly-client');
            const readonlyStart = document.getElementById('readonly-start');
            const readonlyEnd = document.getElementById('readonly-end');
            const readonlyStatus = document.getElementById('readonly-status');
            const readonlyNotes = document.getElementById('readonly-notes');
            const readonlyCardStaff = document.getElementById('readonly-card-staff');
            const readonlyCardService = document.getElementById('readonly-card-service');
            const readonlyCardClient = document.getElementById('readonly-card-client');
            const readonlyCardStart = document.getElementById('readonly-card-start');
            const readonlyCardEnd = document.getElementById('readonly-card-end');
            const readonlyCardStatus = document.getElementById('readonly-card-status');
            const readonlyCardStatusChip = document.getElementById('readonly-card-status-chip');
            const readonlyCardNotes = document.getElementById('readonly-card-notes');

            function setAppointmentReadOnlyMode(isReadOnly) {
                [locationField, staffField, serviceField, clientField, startField, endField, statusField, notesField].forEach(el => {
                    if (el) el.disabled = !!isReadOnly;
                });
                if (openNewClientModalBtn) openNewClientModalBtn.disabled = !!isReadOnly;
                if (apptSaveBtn) apptSaveBtn.classList.toggle('d-none', !!isReadOnly);
                if (appointmentFormFields) appointmentFormFields.classList.toggle('d-none', !!isReadOnly);
                if (appointmentReadonlyDetails) appointmentReadonlyDetails.classList.toggle('d-none', !isReadOnly);
            }

            function formatDateTimeLong(dateStr) {
                const d = parseCalendarDate(dateStr);
                return d.toLocaleString([], { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: true });
            }

            function fillReadonlyAppointmentDetails(appt) {
                if (readonlyStaff) readonlyStaff.textContent = appt.staffName || appt.staff || 'N/A';
                if (readonlyService) readonlyService.textContent = appt.serviceName || appt.service || 'N/A';
                if (readonlyClient) readonlyClient.textContent = appt.clientName || appt.title || 'Unassigned';

                const contactEl = document.getElementById('readonly-client-contact');
                if (contactEl) {
                    const parts = [];
                    if (appt.clientPhone && appt.clientPhone !== 'N/A') parts.push(appt.clientPhone);
                    if (appt.clientEmail && appt.clientEmail !== 'N/A') parts.push(appt.clientEmail);
                    contactEl.textContent = parts.length ? parts.join(' • ') : 'No contact info';
                }

                const durationEl = document.getElementById('readonly-duration');
                if (durationEl) durationEl.textContent = appt.duration || 'N/A';

                const startEndEl = document.getElementById('readonly-start-end');
                if (startEndEl) {
                    const startFormatted = formatDateTimeLong(appt.start);
                    const endDate = parseCalendarDate(appt.end);
                    const endTimeStr = !Number.isNaN(endDate.getTime()) ? endDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true }) : '';
                    startEndEl.textContent = `${startFormatted} - ${endTimeStr}`;
                }

                if (readonlyNotes) readonlyNotes.textContent = appt.notes || 'No notes provided.';

                const invTotalEl = document.getElementById('readonly-invoice-total');
                const invPaidEl = document.getElementById('readonly-invoice-paid');
                const invBalanceEl = document.getElementById('readonly-invoice-balance');
                const pmtBadgeEl = document.getElementById('readonly-payment-badge');
                const pmtMethodEl = document.getElementById('readonly-payment-method');
                const invLinkEl = document.getElementById('readonly-invoice-link');
                const invNumEl = document.getElementById('readonly-invoice-num');

                const total = appt.invoiceTotal ? `$${Number(appt.invoiceTotal).toFixed(2)}` : '$0.00';
                const paid = appt.invoicePaid ? `$${Number(appt.invoicePaid).toFixed(2)}` : '$0.00';
                const balance = appt.invoiceBalance ? `$${Number(appt.invoiceBalance).toFixed(2)}` : '$0.00';
                const pmtStatus = String(appt.paymentStatus || 'unpaid').toLowerCase();

                if (invTotalEl) invTotalEl.textContent = total;
                if (invPaidEl) invPaidEl.textContent = paid;
                if (invBalanceEl) invBalanceEl.textContent = balance;
                if (pmtMethodEl) pmtMethodEl.textContent = appt.paymentMethod || 'N/A';

                if (pmtBadgeEl) {
                    pmtBadgeEl.className = 'badge px-3 py-2 fs-6 fw-bold ms-2 ';
                    if (pmtStatus === 'paid') {
                        pmtBadgeEl.classList.add('bg-success');
                        pmtBadgeEl.textContent = 'PAID';
                    } else if (pmtStatus === 'partially_paid') {
                        pmtBadgeEl.classList.add('bg-warning', 'text-dark');
                        pmtBadgeEl.textContent = 'PARTIALLY PAID';
                    } else {
                        pmtBadgeEl.classList.add('bg-danger');
                        pmtBadgeEl.textContent = 'UNPAID';
                    }
                }

                if (invLinkEl) {
                    if (appt.invoiceId) {
                        invLinkEl.href = `/invoices/${appt.invoiceId}`;
                        if (invNumEl) invNumEl.textContent = appt.invoiceNumber ? `#${appt.invoiceNumber}` : '';
                        invLinkEl.classList.remove('d-none');
                    } else {
                        invLinkEl.classList.add('d-none');
                    }
                }
            }

            function fillReadonlyAppointmentCard(appt) {
                if (appointmentDetailsCard) {
                    appointmentDetailsCard.dataset.appointmentId = appt.id || '';
                }
                if (readonlyCardStaff) readonlyCardStaff.textContent = appt.staff || 'N/A';
                if (readonlyCardService) readonlyCardService.textContent = appt.service || 'N/A';
                if (readonlyCardClient) readonlyCardClient.textContent = appt.title || 'Unassigned';
                if (readonlyCardStart) readonlyCardStart.textContent = formatDateTimeLong(appt.start);
                if (readonlyCardEnd) readonlyCardEnd.textContent = formatDateTimeLong(appt.end);

                const status = String(appt.status || '').toLowerCase();
                const labelMap = {
                    pending: 'Pending',
                    booked: 'Booked',
                    confirmed: 'Confirmed',
                    completed: 'Completed',
                    cancelled: 'Cancelled',
                    no_show: 'No Show'
                };
                if (readonlyCardStatus) readonlyCardStatus.textContent = labelMap[status] || appt.status || '-';
                if (readonlyCardNotes) readonlyCardNotes.textContent = appt.notes || '-';
                if (readonlyCardStatusChip) {
                    readonlyCardStatusChip.className = 'card-status-chip d-none';
                    readonlyCardStatusChip.textContent = '';
                    const chipMap = {
                        pending: { class: 'status-pending', text: 'Pending' },
                        booked: { class: 'status-booked', text: 'Booked' },
                        confirmed: { class: 'status-confirmed', text: 'Confirmed' },
                        completed: { class: 'status-completed', text: 'Completed' },
                        cancelled: { class: 'status-cancelled', text: 'Cancelled' },
                        no_show: { class: 'status-no_show', text: 'No Show' },
                        tentative: { class: 'status-tentative', text: 'Tentative' }
                    };
                    if (chipMap[status]) {
                        readonlyCardStatusChip.classList.remove('d-none');
                        readonlyCardStatusChip.classList.add(chipMap[status].class);
                        readonlyCardStatusChip.textContent = chipMap[status].text;
                    }
                }

                // Update Quick Action Buttons Visibility
                const allowedMap = {
                    pending: ['confirmed', 'cancelled', 'no_show'],
                    booked: ['confirmed', 'cancelled', 'no_show'],
                    confirmed: ['completed', 'cancelled', 'no_show'],
                    completed: [],
                    cancelled: [],
                    no_show: []
                };
                const validNext = allowedMap[status] || [];
                const quickActionBtns = document.querySelectorAll('#card-quick-actions .btn-quick-status');
                quickActionBtns.forEach(btn => {
                    const targetStatus = btn.dataset.status;
                    if (validNext.includes(targetStatus)) {
                        btn.classList.remove('d-none');
                    } else {
                        btn.classList.add('d-none');
                    }
                });

                // Quick links update
                const cardLinkClient = document.getElementById('card-link-client');
                const cardLinkInvoice = document.getElementById('card-link-invoice');
                const cardLinkInvoiceNum = document.getElementById('card-link-invoice-num');
                const cardLinkForms = document.getElementById('card-link-forms');

                const clientId = appt.clientId || appt.client_id;
                if (cardLinkClient) {
                    if (clientId) {
                        cardLinkClient.href = `/clients/${clientId}`;
                        cardLinkClient.classList.remove('d-none');
                    } else {
                        cardLinkClient.classList.add('d-none');
                    }
                }
                if (cardLinkInvoice) {
                    if (appt.invoiceId) {
                        cardLinkInvoice.href = `/invoices/${appt.invoiceId}`;
                        if (cardLinkInvoiceNum) cardLinkInvoiceNum.textContent = appt.invoiceNumber || appt.invoiceId;
                        cardLinkInvoice.classList.remove('d-none');
                    } else {
                        cardLinkInvoice.classList.add('d-none');
                    }
                }
                if (cardLinkForms) {
                    if (appt.hasForms && clientId) {
                        cardLinkForms.href = `/clients/${clientId}#tab-forms`;
                        cardLinkForms.classList.remove('d-none');
                    } else {
                        cardLinkForms.classList.add('d-none');
                    }
                }
            }

            function positionAppointmentDetailsCard(clickEvent) {
                if (!appointmentDetailsCard) return;

                const anchor = clickEvent?.currentTarget || clickEvent?.target?.closest('.calendar-appointment');
                if (!anchor) {
                    appointmentDetailsCard.style.left = 'auto';
                    appointmentDetailsCard.style.right = '24px';
                    appointmentDetailsCard.style.top = '110px';
                    return;
                }

                const margin = 10;
                const anchorRect = anchor.getBoundingClientRect();
                const cardRect = appointmentDetailsCard.getBoundingClientRect();
                let left = anchorRect.right + margin;
                let top = anchorRect.top;

                if (left + cardRect.width > window.innerWidth - 8) {
                    left = anchorRect.left - cardRect.width - margin;
                }
                if (left < 8) {
                    left = Math.max(8, window.innerWidth - cardRect.width - 8);
                }
                if (top + cardRect.height > window.innerHeight - 8) {
                    top = Math.max(8, window.innerHeight - cardRect.height - 8);
                }
                if (top < 8) top = 8;

                appointmentDetailsCard.style.right = 'auto';
                appointmentDetailsCard.style.left = `${left}px`;
                appointmentDetailsCard.style.top = `${top}px`;
            }

            function showAppointmentDetailsCard(appt, clickEvent = null) {
                fillReadonlyAppointmentCard(appt);
                if (appointmentDetailsCard) {
                    appointmentDetailsCard.classList.remove('d-none');
                    positionAppointmentDetailsCard(clickEvent);
                }
            }

            function hideAppointmentDetailsCard() {
                if (appointmentDetailsCard) appointmentDetailsCard.classList.add('d-none');
            }

            // Utility helpers (local time, no UTC conversion)
            function toLocalDate(d) {
                return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
            }
            function toLocalDateTime(d) {
                return `${toLocalDate(d)}T${pad2(d.getHours())}:${pad2(d.getMinutes())}:${pad2(d.getSeconds())}`;
            }
            function toApiDateTime(d) {
                // Keep wall-clock time unchanged between UI and server.
                return toLocalDateTime(d);
            }

            async function getErrorMessage(response, fallback = 'Request failed') {
                const raw = await response.text();
                if (!raw) return `${fallback} (${response.status})`;

                try {
                    const err = JSON.parse(raw);
                    if (err.message) return err.message;
                    if (err.errors && typeof err.errors === 'object') {
                        const firstField = Object.keys(err.errors)[0];
                        if (firstField && Array.isArray(err.errors[firstField]) && err.errors[firstField][0]) {
                            return err.errors[firstField][0];
                        }
                    }
                } catch (_) {
                    // ignore JSON parse errors and fall back to a safe message
                }

                if (/^\s*</.test(raw) || raw.length > 500) {
                    return `${fallback}. Server returned an unexpected error (${response.status}).`;
                }

                return raw || `${fallback} (${response.status})`;
            }

            function getWeekRange(startDate) {
                const s = new Date(startDate);
                s.setHours(0, 0, 0, 0);
                const e = new Date(s);
                e.setDate(s.getDate() + 6);
                e.setHours(23, 59, 59, 999);
                return { start: s, end: e };
            }

            function getRangeForCurrentView(startDate) {
                if (currentView === 'month') {
                    const base = monthCalendar ? monthCalendar.getDate() : new Date(startDate);
                    const s = new Date(base.getFullYear(), base.getMonth(), 1, 0, 0, 0, 0);
                    const e = new Date(base.getFullYear(), base.getMonth() + 1, 0, 23, 59, 59, 999);
                    return { start: s, end: e };
                }
                const s = new Date(startDate);
                s.setHours(0, 0, 0, 0);
                const e = new Date(s);
                const spanMap = { day: 0, week: 6, month: 6, year: 6 };
                e.setDate(s.getDate() + (spanMap[currentView] ?? 6));
                e.setHours(23, 59, 59, 999);
                return { start: s, end: e };
            }

            function getNavigationStepDays() {
                const stepMap = { day: 1, week: 7, month: 30, year: 365 };
                return stepMap[currentView] ?? 7;
            }

            function formatTimeShort(dateStr) {
                const d = parseCalendarDate(dateStr);
                return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
            }

            function updateTimezoneLabel() {
                const timezoneText = document.querySelector('.timezone-cell span');
                if (!timezoneText) return;

                const offset = -new Date().getTimezoneOffset();
                const sign = offset >= 0 ? '+' : '-';
                const hh = pad2(Math.floor(Math.abs(offset) / 60));
                const mm = pad2(Math.abs(offset) % 60);
                timezoneText.textContent = `GMT${sign}${hh}:${mm}`;
            }

            // Format time to 12-hour format with AM/PM
            function format12Hour(timeStr) {
                const [hours, minutes] = timeStr.split(':');
                const h = parseInt(hours);
                const m = minutes || '00';
                const ampm = h >= 12 ? 'PM' : 'AM';
                const displayHour = h % 12 || 12;
                return `${String(displayHour).padStart(2, '0')}:${m} ${ampm}`;
            }

            function toInputDateTime(d) {
                return `${toLocalDate(d)}T${pad2(d.getHours())}:${pad2(d.getMinutes())}`;
            }

            function parseCalendarDate(value) {
                if (value instanceof Date) return new Date(value.getTime());
                if (typeof value !== 'string') return new Date(value);

                const m = value.match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})(?::(\d{2}))?/);
                if (m) {
                    return new Date(
                        Number(m[1]),
                        Number(m[2]) - 1,
                        Number(m[3]),
                        Number(m[4]),
                        Number(m[5]),
                        Number(m[6] || 0),
                        0
                    );
                }

                return new Date(value);
            }

            function fromInputDateTime(v) {
                return parseCalendarDate(v);
            }

            function isPastDate(dateObj) {
                if (!(dateObj instanceof Date) || Number.isNaN(dateObj.getTime())) return false;
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const checkDate = new Date(dateObj);
                checkDate.setHours(0, 0, 0, 0);
                return checkDate < today;
            }

            function parseTimeToMinutes(timeStr) {
                if (!timeStr) return null;
                const [h, m] = String(timeStr).split(':');
                const hh = Number.parseInt(h, 10);
                const mm = Number.parseInt(m, 10);
                if (Number.isNaN(hh) || Number.isNaN(mm)) return null;
                return (hh * 60) + mm;
            }

            function getEffectiveSegments(staff, dateObj) {
                if (!staff) return [];
                const dateKey = toLocalDate(dateObj);
                const dayOfWeek = (dateObj.getDay() + 6) % 7; // 0=Mon ... 6=Sun
                const byDate = staff.schedules_by_date && staff.schedules_by_date[dateKey];
                if (Array.isArray(byDate)) return byDate;
                const byWeek = staff.schedules && staff.schedules[dayOfWeek];
                return byWeek ? [byWeek] : [];
            }

            function getStaffScheduleForDate(staffId, dateObj) {
                const schedules = window._calendarSchedules || [];
                const numericStaffId = Number.parseInt(staffId, 10);
                const staff = schedules.find(s => Number.parseInt(s.id, 10) === numericStaffId);
                if (!staff) return null;
                return getEffectiveSegments(staff, dateObj);
            }

            function validateAppointmentWithinStaffHours(staffId, startDate, endDate) {
                if (!staffId || !(startDate instanceof Date) || !(endDate instanceof Date)) return null;
                if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) return null;

                if (toLocalDate(startDate) !== toLocalDate(endDate)) {
                    return 'Appointment must be within one day and inside staff working hours.';
                }

                const segments = getStaffScheduleForDate(staffId, startDate);
                if (!Array.isArray(segments) || segments.length === 0) {
                    return 'Staff is not available at the selected time.';
                }

                const apptStart = (startDate.getHours() * 60) + startDate.getMinutes();
                const apptEnd = (endDate.getHours() * 60) + endDate.getMinutes();

                for (const schedule of segments) {
                    if (!schedule || !schedule.is_working) continue;
                    const workingStart = parseTimeToMinutes(schedule.start_time);
                    const workingEnd = parseTimeToMinutes(schedule.end_time);
                    if (workingStart === null || workingEnd === null) continue;
                    if (apptStart >= workingStart && apptEnd <= workingEnd) return null;
                }

                return 'Staff is not available at the selected time.';
            }

            function clearNewClientFields() {
                newClientForm?.reset();
            }

            function fillSelect(selectEl, items, placeholder, valueKey = 'id', labelKey = 'name') {
                selectEl.innerHTML = '';
                const first = document.createElement('option');
                first.value = '';
                first.textContent = placeholder;
                selectEl.appendChild(first);

                items.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item[valueKey];
                    let label = item[labelKey];
                    if (selectEl === serviceField) {
                        const costStr = (item.price !== undefined && item.price !== null) ? `$${parseFloat(item.price).toFixed(2)}` : '';
                        const durStr = item.duration_minutes ? `${item.duration_minutes} min` : '';
                        const extra = [costStr, durStr].filter(Boolean).join(' - ');
                        if (extra) label += ` (${extra})`;
                    }
                    option.textContent = label;
                    selectEl.appendChild(option);
                });
            }

            function updateSelectedServiceInfo() {
                const serviceId = serviceField ? serviceField.value : '';
                const infoContainer = document.getElementById('service-cost-duration-info');
                const costEl = document.getElementById('selected-service-cost');
                const durEl = document.getElementById('selected-service-duration');
                if (!infoContainer || !costEl || !durEl) return;

                if (!serviceId) {
                    infoContainer.classList.add('d-none');
                    return;
                }
                const services = window.CALENDAR_DATA.services || [];
                const svc = services.find(s => String(s.id) === String(serviceId));
                if (svc) {
                    const priceVal = (svc.price !== undefined && svc.price !== null) ? `$${parseFloat(svc.price).toFixed(2)}` : '$0.00';
                    const durVal = (svc.duration_minutes ?? svc.duration ?? 0) + ' minutes';
                    costEl.textContent = priceVal;
                    durEl.textContent = durVal;
                    infoContainer.classList.remove('d-none');
                } else {
                    infoContainer.classList.add('d-none');
                }
            }
            if (serviceField) {
                serviceField.addEventListener('change', updateSelectedServiceInfo);
            }

            function hasSelectOptionValue(selectEl, value) {
                if (!selectEl) return false;
                const v = String(value);
                return Array.from(selectEl.options || []).some(o => String(o.value) === v);
            }

            function ensureSelectOption(selectEl, value, label, suffix = '') {
                if (!selectEl || value === null || value === undefined || value === '') return;
                if (hasSelectOptionValue(selectEl, value)) return;

                const option = document.createElement('option');
                option.value = String(value);
                option.textContent = `${label || 'Historical record'}${suffix}`;
                option.dataset.historical = '1';
                selectEl.appendChild(option);
            }

            function hydrateFormOptions() {
                hydrateLocationOptions();
                hydrateStaffOptionsForSelectedLocation();
                hydrateServiceOptionsForSelectedStaff();
                hydrateClientOptions();
            }

            function hydrateLocationOptions(extraLocation = null) {
                const locations = window.CALENDAR_DATA.locations || [];
                locationField.innerHTML = '';
                const none = document.createElement('option');
                none.value = '';
                none.textContent = locations.length ? 'No location / flexible' : 'No active locations available';
                locationField.appendChild(none);

                locations.forEach(location => {
                    const option = document.createElement('option');
                    option.value = location.id;
                    option.textContent = location.name;
                    locationField.appendChild(option);
                });

                if (extraLocation && extraLocation.id && !hasSelectOptionValue(locationField, extraLocation.id)) {
                    const option = document.createElement('option');
                    option.value = extraLocation.id;
                    option.textContent = `${extraLocation.name || 'Historical location'} (inactive)`;
                    option.dataset.historical = '1';
                    locationField.appendChild(option);
                }

                if (locationHelp) {
                    locationHelp.textContent = locations.length ? 'Choose a location to show matching staff only.' : 'Appointments can still be saved without a location.';
                }
            }

            function staffMatchesLocation(staff, locationId) {
                if (!locationId) return true;
                return staff.location_id && String(staff.location_id) === String(locationId);
            }

            function staffMatchesServiceCategory(staff, serviceId) {
                if (!serviceId) return true;
                const services = window.CALENDAR_DATA.services || [];
                const svc = services.find(s => String(s.id) === String(serviceId));
                if (!svc) return true;

                const staffCat = normalizeCategory(staff.category || '');
                if (!staffCat) return true;

                const svcCat = normalizeCategory(svc.category ? svc.category.name : '');
                if (!svcCat) return true;
                return svcCat === staffCat || svcCat.includes(staffCat) || staffCat.includes(svcCat);
            }

            function hydrateStaffOptionsForSelectedLocation(respectService = false) {
                const locationId = locationField ? locationField.value : '';
                const currentStaffId = staffField ? staffField.value : '';
                const currentStaffObj = (window.CALENDAR_DATA.staffs || []).find(s => String(s.id) === String(currentStaffId));

                let staffs = (window.CALENDAR_DATA.staffs || []).filter(staff => staffMatchesLocation(staff, locationId));
                if (respectService) {
                    const serviceId = serviceField ? serviceField.value : '';
                    staffs = staffs.filter(staff => staffMatchesServiceCategory(staff, serviceId));
                }
                fillSelect(staffField, staffs, staffs.length ? 'Select staff' : 'No staff assigned to this location');

                if (currentStaffId) {
                    if (currentStaffObj) {
                        ensureSelectOption(staffField, currentStaffId, currentStaffObj.name);
                    }
                    staffField.value = String(currentStaffId);
                }
                return staffs;
            }

            function normalizeCategory(value) {
                return String(value ?? '').trim().toLowerCase();
            }

            function servicesForStaff(staffId) {
                if (!staffId) return [];
                const staff = (window.CALENDAR_DATA.staffs || []).find(s => String(s.id) === String(staffId));
                const services = window.CALENDAR_DATA.services || [];
                if (!staff) return [];

                const staffCategory = normalizeCategory(staff.category || '');
                if (!staffCategory) return services;

                return services.filter(s => {
                    const category = normalizeCategory(s.category ? s.category.name : '');
                    if (!category) return false;
                    return category === staffCategory || category.includes(staffCategory) || staffCategory.includes(category);
                });
            }

            function hydrateServiceOptionsForSelectedStaff(preserveValue = null) {
                const staffId = staffField.value;
                const matching = servicesForStaff(staffId);
                const placeholder = staffId
                    ? (matching.length ? 'Select service' : 'No services available for this staff category')
                    : 'Select staff first';
                fillSelect(serviceField, matching, placeholder);

                if (preserveValue && hasSelectOptionValue(serviceField, preserveValue)) {
                    serviceField.value = String(preserveValue);
                } else if (preserveValue) {
                    const svc = (window.CALENDAR_DATA.services || []).find(s => String(s.id) === String(preserveValue));
                    if (svc) {
                        ensureSelectOption(serviceField, svc.id, svc.name);
                        serviceField.value = String(svc.id);
                    } else {
                        serviceField.value = '';
                    }
                } else {
                    serviceField.value = '';
                }

                serviceField.disabled = !staffId || matching.length === 0;
                return matching;
            }

            function syncLocationFromStaff() {
                const staff = (window.CALENDAR_DATA.staffs || []).find(item => String(item.id) === String(staffField.value));
                if (staff && staff.location_id && locationField && hasSelectOptionValue(locationField, staff.location_id)) {
                    locationField.value = String(staff.location_id);
                    hydrateStaffOptionsForSelectedLocation();
                    staffField.value = String(staff.id);
                }
            }

            function isScheduleCoveringSlot(schedules, startDate, endDate) {
                if (!(startDate instanceof Date) || !(endDate instanceof Date)) return false;
                if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) return false;
                if (toLocalDate(startDate) !== toLocalDate(endDate)) return false;
                if (!Array.isArray(schedules)) return false;

                const apptStart = (startDate.getHours() * 60) + startDate.getMinutes();
                const apptEnd = (endDate.getHours() * 60) + endDate.getMinutes();

                return schedules.some(schedule => {
                    if (!schedule || !schedule.is_working) return false;
                    const workingStart = parseTimeToMinutes(schedule.start_time);
                    const workingEnd = parseTimeToMinutes(schedule.end_time);
                    if (workingStart === null || workingEnd === null) return false;
                    return apptStart >= workingStart && apptEnd <= workingEnd;
                });
            }

            function hydrateStaffOptionsForSlot(startDate, endDate) {
                const locationId = locationField ? locationField.value : '';
                const serviceId = serviceField ? serviceField.value : '';
                const currentStaffId = staffField ? staffField.value : '';
                const currentStaffObj = (window.CALENDAR_DATA.staffs || []).find(s => String(s.id) === String(currentStaffId));

                let allStaffs = (window.CALENDAR_DATA.staffs || []).filter(staff => staffMatchesLocation(staff, locationId));
                if (serviceId) {
                    allStaffs = allStaffs.filter(staff => staffMatchesServiceCategory(staff, serviceId));
                }
                const schedules = window._calendarSchedules || [];

                if (!(startDate instanceof Date) || Number.isNaN(startDate.getTime())) {
                    fillSelect(staffField, allStaffs, 'Select staff');
                    if (currentStaffId) {
                        if (currentStaffObj) ensureSelectOption(staffField, currentStaffId, currentStaffObj.name);
                        staffField.value = String(currentStaffId);
                    }
                    return { count: allStaffs.length, hasScheduleData: false };
                }

                if (!Array.isArray(schedules) || schedules.length === 0) {
                    fillSelect(staffField, [], 'No staff schedule data');
                    if (currentStaffId) {
                        if (currentStaffObj) ensureSelectOption(staffField, currentStaffId, currentStaffObj.name);
                        staffField.value = String(currentStaffId);
                    }
                    return { count: 0, hasScheduleData: false };
                }

                const dateKey = toLocalDate(startDate);
                const dayOfWeek = (startDate.getDay() + 6) % 7; // 0=Mon ... 6=Sun
                const workingStaffIds = new Set(
                    schedules
                        .filter(s => {
                            const byDate = s.schedules_by_date && s.schedules_by_date[dateKey];
                            const segments = Array.isArray(byDate)
                                ? byDate
                                : ((s.schedules && s.schedules[dayOfWeek]) ? [s.schedules[dayOfWeek]] : []);
                            return isScheduleCoveringSlot(segments, startDate, endDate);
                        })
                        .map(s => String(s.id)),
                );

                const dateStaffs = allStaffs.filter(s => workingStaffIds.has(String(s.id)));
                fillSelect(staffField, dateStaffs, dateStaffs.length ? 'Select staff' : 'No staff scheduled for selected time');

                if (currentStaffId) {
                    if (currentStaffObj) ensureSelectOption(staffField, currentStaffId, currentStaffObj.name);
                    staffField.value = String(currentStaffId);
                }

                return { count: dateStaffs.length, hasScheduleData: true };
            }

            let apptClientTomSelect = null;

            function initApptClientTomSelect() {
                if (window.TomSelect && clientField && !apptClientTomSelect) {
                    try {
                        apptClientTomSelect = new TomSelect(clientField, {
                            create: false,
                            placeholder: 'Select client',
                            allowEmptyOption: true,
                            maxItems: 1,
                            valueField: 'id',
                            labelField: 'text',
                            searchField: ['text', 'name', 'first_name', 'last_name', 'email', 'phone', 'alternate_phone'],
                            load: function (query, callback) {
                                if (!query.length || query.length < 2) return callback();
                                fetch(calendarUrl(`clients/search?q=${encodeURIComponent(query)}`), {
                                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                                })
                                    .then(res => res.json())
                                    .then(json => {
                                        const items = (json || []).map(c => ({
                                            id: String(c.id),
                                            text: `${c.name}${c.phone ? ` (${c.phone})` : ''}${c.email ? ` - ${c.email}` : ''}`,
                                            name: c.name || '',
                                            first_name: c.first_name || '',
                                            last_name: c.last_name || '',
                                            email: c.email || '',
                                            phone: c.phone || ''
                                        }));
                                        callback(items);
                                    })
                                    .catch(() => callback());
                            },
                            onChange: function () {
                                clientField.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        });
                    } catch (e) {
                        console.warn('TomSelect init fallback', e);
                    }
                }
            }

            function hydrateClientOptions() {
                initApptClientTomSelect();
                const clients = window.CALENDAR_DATA.clients || [];

                if (apptClientTomSelect) {
                    const currentVal = apptClientTomSelect.getValue();
                    apptClientTomSelect.clearOptions();
                    apptClientTomSelect.addOption({ id: '', text: 'Select client' });
                    clients.forEach(client => {
                        apptClientTomSelect.addOption({
                            id: String(client.id),
                            text: `${client.name}${client.phone ? ` (${client.phone})` : ''}`,
                            name: client.name || '',
                            first_name: client.first_name || '',
                            last_name: client.last_name || '',
                            email: client.email || '',
                            phone: client.phone || ''
                        });
                    });
                    if (currentVal) {
                        apptClientTomSelect.setValue(currentVal, true);
                    }
                } else {
                    clientField.innerHTML = '';
                    const selectClient = document.createElement('option');
                    selectClient.value = '';
                    selectClient.textContent = 'Select client';
                    clientField.appendChild(selectClient);

                    clients.forEach(client => {
                        const option = document.createElement('option');
                        option.value = client.id;
                        option.textContent = `${client.name}${client.phone ? ` (${client.phone})` : ''}`;
                        clientField.appendChild(option);
                    });
                }
            }

            let clientSearchTimer = null;
            if (clientSearchField) {
                clientSearchField.addEventListener('input', function () {
                    const term = clientSearchField.value.trim();
                    if (clientSearchTimer) clearTimeout(clientSearchTimer);
                    clientSearchTimer = setTimeout(async () => {
                        if (term.length < 2) {
                            hydrateClientOptions();
                            return;
                        }
                        try {
                            const res = await fetch(calendarUrl(`clients/search?q=${encodeURIComponent(term)}`), {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            if (!res.ok) throw new Error(await getErrorMessage(res, 'Client search failed'));
                            window.CALENDAR_DATA.clients = await res.json();
                            hydrateClientOptions();
                            if (apptClientTomSelect) {
                                apptClientTomSelect.open();
                            }
                        } catch (err) {
                            showPageNotice(err.message || 'Unable to search clients.');
                        }
                    }, 280);
                });
            }

            function getSelectedServiceDurationMinutes() {
                const selectedServiceId = serviceField.value;
                if (!selectedServiceId) return null;

                const services = window.CALENDAR_DATA.services || [];
                const selectedService = services.find(s => String(s.id) === String(selectedServiceId));
                if (!selectedService) return null;

                const duration = Number.parseInt(selectedService.duration_minutes ?? selectedService.duration ?? 0, 10);
                return Number.isFinite(duration) && duration > 0 ? duration : null;
            }

            function applyServiceDurationToEndTime() {
                if (!startField.value) return;
                const duration = getSelectedServiceDurationMinutes();
                if (!duration) return;

                const start = fromInputDateTime(startField.value);
                if (Number.isNaN(start.getTime())) return;
                const autoEnd = new Date(start.getTime() + (duration * 60000));
                endField.value = toInputDateTime(autoEnd);

                if (!apptIdField.value) {
                    const current = staffField.value;
                    const info = hydrateStaffOptionsForSlot(start, autoEnd);
                    if (current && hasSelectOptionValue(staffField, current)) {
                        staffField.value = String(current);
                    }
                    if (info && info.hasScheduleData && info.count === 0) {
                        const msg = 'No staff scheduled for the selected time.';
                        showPageNotice(msg);
                    }
                }
            }

            function openAppointmentModalForCreate(startDate, endDate, staffId = '') {
                hideAppointmentDetailsCard();
                modalTitle.textContent = 'New Appointment';
                setAppointmentReadOnlyMode(false);
                apptIdField.value = '';
                hydrateFormOptions();
                const staffInfo = hydrateStaffOptionsForSlot(startDate, endDate);
                if (staffInfo && staffInfo.hasScheduleData && staffInfo.count === 0) {
                    const msg = 'No staff scheduled for the selected time. Please create staff schedule first.';
                    showPageNotice(msg);
                    return;
                }
                if (staffId) {
                    const desired = String(staffId);
                    staffField.value = hasSelectOptionValue(staffField, desired) ? desired : '';
                    syncLocationFromStaff();
                } else {
                    staffField.value = '';
                    if (locationField) locationField.value = '';
                }
                hydrateServiceOptionsForSelectedStaff();
                serviceField.value = '';
                clientField.value = '';
                statusField.value = 'pending';
                notesField.value = '';
                startField.value = toInputDateTime(startDate);
                endField.value = toInputDateTime(endDate);
                clearNewClientFields();
                appointmentModal.show();
            }

            async function openAppointmentModalForEdit(appointmentId, clickEvent = null) {
                try {
                    hideAppointmentDetailsCard();
                    setAppointmentReadOnlyMode(false);
                    if (apptSaveBtn) {
                        apptSaveBtn.disabled = true;
                        apptSaveBtn.dataset.originalText = apptSaveBtn.textContent;
                        apptSaveBtn.textContent = 'Loading appointment...';
                    }

                    const res = await fetch(calendarUrl(`appointments/${appointmentId}`));
                    if (!res.ok) {
                        const msg = await getErrorMessage(res, 'Unable to load appointment');
                        throw new Error(msg);
                    }
                    const appt = await res.json();

                    if (String(appt.status || '').toLowerCase() === 'completed') {
                        fillReadonlyAppointmentDetails(appt);
                        fillReadonlyAppointmentCard(appt);
                        setAppointmentReadOnlyMode(true);
                        modalTitle.textContent = 'Completed Appointment Details';
                        appointmentModal.show();
                        return;
                    }

                    modalTitle.textContent = 'Edit Appointment';
                    apptIdField.value = appt.id;

                    hydrateLocationOptions(appt.locationId ? { id: appt.locationId, name: appt.locationName || appt.location } : null);
                    if (locationField) locationField.value = appt.locationId ? String(appt.locationId) : '';

                    hydrateStaffOptionsForSelectedLocation();
                    ensureSelectOption(staffField, appt.staffId, appt.staffName || appt.staff, ' (historical)');
                    staffField.value = appt.staffId ? String(appt.staffId) : '';

                    hydrateServiceOptionsForSelectedStaff(appt.serviceId);
                    ensureSelectOption(serviceField, appt.serviceId, appt.serviceName || appt.service, ' (historical)');
                    serviceField.value = appt.serviceId ? String(appt.serviceId) : '';
                    if (serviceField.value) serviceField.disabled = false;

                    hydrateClientOptions();
                    ensureSelectOption(clientField, appt.clientId, appt.clientName || appt.title);
                    clientField.value = appt.clientId ? String(appt.clientId) : '';

                    statusField.value = appt.status || 'pending';
                    notesField.value = appt.notes || '';
                    startField.value = toInputDateTime(parseCalendarDate(appt.start));
                    endField.value = toInputDateTime(parseCalendarDate(appt.end));
                    clearNewClientFields();
                    appointmentModal.show();
                } catch (err) {
                    showPageNotice(err.message || 'Failed to load appointment');
                } finally {
                    if (apptSaveBtn) {
                        apptSaveBtn.disabled = false;
                        apptSaveBtn.textContent = apptSaveBtn.dataset.originalText || 'Save';
                    }
                }
            }

            /**
             * Workflow 2 — Open the read-only completed appointment modal.
             * Fetches full appointment+invoice+payment details from the server
             * and populates the #completedAppointmentModal.
             */
            async function openCompletedAppointmentModal(appointmentId) {
                const modalEl = document.getElementById('completedAppointmentModal');
                if (!modalEl) return;

                const loadingEl = document.getElementById('completed-modal-loading');
                const contentEl = document.getElementById('completed-modal-content');
                const errorEl = document.getElementById('completed-modal-error');

                // Reset state
                if (loadingEl) { loadingEl.classList.remove('d-none'); }
                if (contentEl) { contentEl.classList.add('d-none'); }
                if (errorEl) { errorEl.classList.add('d-none'); errorEl.textContent = ''; }

                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();

                try {
                    const res = await fetch(calendarUrl(`appointments/${appointmentId}/completed-details`));
                    const data = await res.json();

                    if (!res.ok || !data.success) {
                        throw new Error(data.message || 'Failed to load appointment details.');
                    }

                    const appt = data.appointment || {};
                    const client = data.client || {};
                    const staff = data.staff || {};
                    const service = data.service || {};
                    const invoice = data.invoice || null;
                    const payments = data.payments || [];

                    // Helper — safely set text
                    const setText = (id, val) => {
                        const el = document.getElementById(id);
                        if (el) el.textContent = val || '-';
                    };
                    const hide = id => { const el = document.getElementById(id); if (el) el.classList.add('d-none'); };
                    const show = id => { const el = document.getElementById(id); if (el) el.classList.remove('d-none'); };
                    const fmt = n => '$' + parseFloat(n || 0).toFixed(2);

                    // Client
                    setText('cmod-client-name', client.name);
                    setText('cmod-client-phone', client.phone);
                    setText('cmod-client-email', client.email);

                    // Appointment
                    const start = appt.start ? new Date(appt.start) : null;
                    const end = appt.end ? new Date(appt.end) : null;
                    setText('cmod-appt-date', start ? start.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) : '-');
                    setText('cmod-appt-start', start ? start.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) : '-');
                    setText('cmod-appt-end', end ? end.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) : '-');
                    setText('cmod-appt-duration', appt.duration ? appt.duration + ' minutes' : '-');
                    setText('cmod-appt-notes', appt.notes || 'No notes');

                    // Practitioner & Service
                    setText('cmod-staff-name', staff.name);
                    setText('cmod-service-name', service.name);
                    setText('cmod-service-price', service.price !== undefined ? fmt(service.price) : '-');

                    // Invoice
                    if (invoice) {
                        const statusLabel = (invoice.status || 'outstanding')
                            .replace('_', ' ')
                            .replace(/\b\w/g, c => c.toUpperCase());
                        setText('cmod-inv-number', invoice.invoice_number);
                        setText('cmod-inv-status', statusLabel);
                        setText('cmod-inv-total', fmt(invoice.total_amount));
                        setText('cmod-inv-paid', fmt(invoice.paid_amount));
                        setText('cmod-inv-balance', fmt(invoice.balance));

                        const invLink = document.getElementById('cmod-invoice-link');
                        if (invLink) invLink.href = invoice.url || '#';
                        const goLink = document.getElementById('cmod-go-to-invoice');
                        if (goLink) { goLink.href = invoice.url || '#'; show('cmod-go-to-invoice'); }

                        show('cmod-invoice-section');
                        hide('cmod-no-invoice');

                        // Payments
                        const tbody = document.getElementById('cmod-payments-tbody');
                        if (tbody) {
                            tbody.innerHTML = '';
                            if (payments.length > 0) {
                                payments.forEach(p => {
                                    const tr = document.createElement('tr');
                                    const method = (p.payment_method || '').replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
                                    tr.innerHTML = `<td>${p.payment_date || '-'}</td><td>${method}</td><td>${p.transaction_id || '-'}</td><td class="text-end">${fmt(p.amount)}</td>`;
                                    tbody.appendChild(tr);
                                });
                                show('cmod-payments-section');
                            } else {
                                hide('cmod-payments-section');
                            }
                        }
                    } else {
                        hide('cmod-invoice-section');
                        hide('cmod-payments-section');
                        hide('cmod-go-to-invoice');
                        show('cmod-no-invoice');
                    }

                    if (loadingEl) loadingEl.classList.add('d-none');
                    if (contentEl) contentEl.classList.remove('d-none');

                } catch (err) {
                    if (loadingEl) loadingEl.classList.add('d-none');
                    if (errorEl) {
                        errorEl.textContent = err.message || 'Could not load appointment details.';
                        errorEl.classList.remove('d-none');
                    }
                }
            }

            // Build header and day columns
            function getScheduledStaffForDate(date) {
                const schedules = window._calendarSchedules || [];
                const dateKey = toLocalDate(date);
                const dayOfWeek = (date.getDay() + 6) % 7;
                return schedules.filter(staff => {
                    const byDate = staff.schedules_by_date && staff.schedules_by_date[dateKey];
                    const segments = Array.isArray(byDate)
                        ? byDate
                        : ((staff.schedules && staff.schedules[dayOfWeek]) ? [staff.schedules[dayOfWeek]] : []);
                    return segments.some(seg => seg && seg.is_working);
                });
            }

            // Build header and day columns
            function buildWeekView() {
                // Generate time column
                generateTimeColumn();
                setGridHeight();

                const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

                // preserve timezone cell
                const timezoneCell = header.firstElementChild;
                header.innerHTML = '';
                header.appendChild(timezoneCell);

                // remove all day columns after the time column
                while (gridBody.children.length > 1) gridBody.removeChild(gridBody.lastChild);

                if (currentView === 'day') {
                    const scheduledStaff = getScheduledStaffForDate(currentWeekStart);
                    const colCount = Math.max(scheduledStaff.length, 1);

                    header.style.gridTemplateColumns = `80px repeat(${colCount}, 1fr)`;
                    gridBody.style.gridTemplateColumns = `80px repeat(${colCount}, 1fr)`;

                    if (scheduledStaff.length === 0) {
                        const headerCell = document.createElement('div');
                        headerCell.className = 'header-cell active-day';
                        headerCell.innerHTML = `<span class="day-label">${currentWeekStart.toLocaleDateString([], { weekday: 'short' })}</span><span class="day-number">${currentWeekStart.getDate()}</span><div class="small text-muted mt-1">No Staff Scheduled</div>`;
                        header.appendChild(headerCell);

                        const col = document.createElement('div');
                        col.className = 'grid-cell day-column';
                        col.style.position = 'relative';
                        col.dataset.dayIndex = 0;
                        col.addEventListener('dragover', ev => ev.preventDefault());
                        col.addEventListener('drop', handleDropOnColumn);
                        col.addEventListener('click', handleColumnClick);
                        gridBody.appendChild(col);
                    } else {
                        scheduledStaff.forEach(staff => {
                            const headerCell = document.createElement('div');
                            headerCell.className = 'header-cell';
                            if (isSameDay(currentWeekStart, new Date())) headerCell.classList.add('active-day');
                            headerCell.innerHTML = `<span class="day-label">${escapeHtml(staff.category || 'Staff')}</span><span class="day-number" style="font-size: 1.1rem; font-weight: 600;">${escapeHtml(staff.name)}</span>`;
                            const segments = getEffectiveSegments(staff, currentWeekStart);
                            const hoursHtml = (segments || [])
                                .filter(s => s && s.is_working)
                                .map(s => `<div class="staff-schedule-item small"><div class="hours">${format12Hour(s.start_time)} – ${format12Hour(s.end_time)}</div></div>`)
                                .join('');
                            if (hoursHtml) {
                                const segContainer = document.createElement('div');
                                segContainer.className = 'staff-schedule-container';
                                segContainer.innerHTML = hoursHtml;
                                headerCell.appendChild(segContainer);
                            }
                            header.appendChild(headerCell);

                            const col = document.createElement('div');
                            col.className = 'grid-cell day-column';
                            col.style.position = 'relative';
                            col.dataset.dayIndex = 0;
                            col.dataset.staffId = staff.id;
                            col.addEventListener('dragover', ev => ev.preventDefault());
                            col.addEventListener('drop', handleDropOnColumn);
                            col.addEventListener('click', handleColumnClick);
                            gridBody.appendChild(col);
                        });
                    }

                    dateDisplay.textContent = `${currentWeekStart.getDate()} ${monthNames[currentWeekStart.getMonth()]} ${currentWeekStart.getFullYear()}`;
                } else {
                    visibleDays = 7;
                    header.style.gridTemplateColumns = `80px repeat(${visibleDays}, 1fr)`;
                    gridBody.style.gridTemplateColumns = `80px repeat(${visibleDays}, 1fr)`;

                    for (let i = 0; i < visibleDays; i++) {
                        const date = new Date(currentWeekStart);
                        date.setDate(currentWeekStart.getDate() + i);

                        const headerCell = document.createElement('div');
                        headerCell.className = 'header-cell';
                        if (isSameDay(date, new Date())) headerCell.classList.add('active-day');

                        const dayLabel = days[i];
                        headerCell.innerHTML = `<span class="day-label">${dayLabel}</span><span class="day-number">${date.getDate()}</span>`;

                        // Add staff schedule display under the date
                        const scheduleContainer = document.createElement('div');
                        scheduleContainer.className = 'staff-schedule-container';
                        scheduleContainer.id = `staff-schedules-${i}`;
                        scheduleContainer.style.borderTop = '1px solid var(--calendar-border-hourly)';

                        headerCell.appendChild(scheduleContainer);
                        header.appendChild(headerCell);

                        const col = document.createElement('div');
                        col.className = 'grid-cell day-column';
                        col.style.position = 'relative';
                        col.dataset.dayIndex = i;
                        col.addEventListener('dragover', ev => ev.preventDefault());
                        col.addEventListener('drop', handleDropOnColumn);
                        col.addEventListener('click', handleColumnClick);
                        gridBody.appendChild(col);
                    }

                    const activeCell = header.querySelector('.header-cell.active-day');
                    if (activeCell) {
                        const dayCells = Array.from(header.querySelectorAll('.header-cell'));
                        const idx = dayCells.indexOf(activeCell);
                        if (idx >= 0) {
                            const activeDate = new Date(currentWeekStart);
                            activeDate.setDate(currentWeekStart.getDate() + idx);
                            dateDisplay.textContent = `${activeDate.getDate()} ${monthNames[activeDate.getMonth()]} ${activeDate.getFullYear()}`;
                        } else {
                            dateDisplay.textContent = `${currentWeekStart.getDate()} ${monthNames[currentWeekStart.getMonth()]} ${currentWeekStart.getFullYear()}`;
                        }
                    } else {
                        dateDisplay.textContent = `${currentWeekStart.getDate()} ${monthNames[currentWeekStart.getMonth()]} ${currentWeekStart.getFullYear()}`;
                    }
                }
            }

            function isSameDay(a, b) {
                const da = new Date(a);
                const db = new Date(b);
                return da.getFullYear() === db.getFullYear() &&
                    da.getMonth() === db.getMonth() &&
                    da.getDate() === db.getDate();
            }

            // Fetch events and schedules for the current week
            async function loadDataAndRender() {
                const range = getRangeForCurrentView(currentWeekStart);

                const params = new URLSearchParams({
                    start: toLocalDate(range.start),
                    end: toLocalDate(range.end),
                });
                const filterLocation = document.getElementById('calendar-filter-location')?.value;
                const filterStaff = document.getElementById('calendar-filter-staff')?.value;
                const filterService = document.getElementById('calendar-filter-service')?.value;
                const filterStatus = document.getElementById('calendar-filter-status')?.value;
                if (filterLocation) params.set('location_id', filterLocation);
                if (filterStaff) params.set('staff_id', filterStaff);
                if (filterService) params.set('service_id', filterService);
                if (filterStatus) params.set('status', filterStatus);
                const qs = `?${params.toString()}`;

                try {
                    const [eventsRes, schedRes] = await Promise.all([
                        fetch(`${calendarUrl('events')}${qs}`),
                        fetch(`${calendarUrl('staff-schedules')}${qs}`)
                    ]);

                    if (!eventsRes.ok) throw new Error(await getErrorMessage(eventsRes, 'Unable to load appointments'));
                    if (!schedRes.ok) throw new Error(await getErrorMessage(schedRes, 'Unable to load staff schedules'));
                    const schedJson = await schedRes.json();
                    const eventsJson = await eventsRes.json();

                    window._calendarSchedules = schedJson && schedJson.staff ? schedJson.staff : [];
                    window._calendarEvents = Array.isArray(eventsJson) ? eventsJson : eventsJson.data || [];

                    // Day view columns are built from schedule data; rebuild once the
                    // fresh data arrives so staff columns/hours always match the date.
                    if (currentView === 'day') buildWeekView();

                    if (currentView === 'month' && monthCalendar) {
                        monthCalendar.removeAllEvents();
                        window._calendarEvents.forEach(ev => {
                            monthCalendar.addEvent({
                                id: ev.id,
                                title: ev.title,
                                start: ev.start,
                                end: ev.end,
                                color: ev.color,
                                extendedProps: { staff: ev.staff, status: ev.status }
                            });
                        });
                    }

                    renderStaffSchedules(); // keep header info if you want
                    renderAppointments();
                    render10amHourHighlight();
                } catch (err) {
                    console.error('Error loading calendar data', err);
                    showPageNotice(err.message || 'Unable to load calendar data.');
                }
            }
            function clearAppointments() {
                // remove existing appointments before redraw
                document.querySelectorAll('.day-column .calendar-appointment').forEach(el => el.remove());
            }

            // Render staff schedules under each date header
            function renderStaffSchedules() {
                const schedules = window._calendarSchedules || [];

                for (let dayIndex = 0; dayIndex < visibleDays; dayIndex++) {
                    const container = document.getElementById(`staff-schedules-${dayIndex}`);
                    if (!container) continue;

                    container.innerHTML = '';
                    const date = new Date(currentWeekStart);
                    date.setDate(currentWeekStart.getDate() + dayIndex);

                    // Use effective date-based schedule so staff working status is consistent with server payload.
                    const dateKey = toLocalDate(date);
                    const dayOfWeek = (date.getDay() + 6) % 7;

                    schedules.forEach(staff => {
                        const byDate = staff.schedules_by_date && staff.schedules_by_date[dateKey];
                        const segments = Array.isArray(byDate)
                            ? byDate
                            : ((staff.schedules && staff.schedules[dayOfWeek]) ? [staff.schedules[dayOfWeek]] : []);

                        segments.forEach(sch => {
                            if (!sch || !sch.is_working) return;
                            const item = document.createElement('div');
                            item.className = 'staff-schedule-item';
                            const startTime = format12Hour(sch.start_time);
                            const endTime = format12Hour(sch.end_time);
                            item.innerHTML = `<strong>${escapeHtml(staff.name)}</strong><div class="hours">${startTime} – ${endTime}</div>`;
                            container.appendChild(item);
                        });
                    });
                }
            }

            // Render appointments into day columns
            function renderAppointments() {
                clearAppointments();
                const cols = Array.from(document.querySelectorAll('.day-column'));
                cols.forEach(c => c.style.minHeight = gridBody.clientHeight + 'px');

                const events = window._calendarEvents || [];
                events.forEach(ev => {
                    try {
                        // Parse with the same helper used across the view to keep
                        // wall-clock rendering consistent with form/input times.
                        const startIso = parseCalendarDate(ev.start);
                        const endIso = parseCalendarDate(ev.end);

                        if (Number.isNaN(startIso.getTime()) || Number.isNaN(endIso.getTime())) return;

                        const eventDayKey = toLocalDate(startIso);

                        let col = null;
                        if (currentView === 'day') {
                            const evStaffId = String(ev.staffId || ev.staff_id || '');
                            col = cols.find(c => String(c.dataset.staffId || '') === evStaffId) || cols[0];
                        } else {
                            let dayIndex = -1;
                            for (let i = 0; i < visibleDays; i++) {
                                const dayDate = new Date(currentWeekStart);
                                dayDate.setDate(currentWeekStart.getDate() + i);
                                if (toLocalDate(dayDate) === eventDayKey) {
                                    dayIndex = i;
                                    break;
                                }
                            }
                            if (dayIndex >= 0 && dayIndex < visibleDays) {
                                col = cols[dayIndex];
                            }
                        }
                        if (!col) return;
                        const colHeight = col.clientHeight || 1200;

                        const minutesFromMidnight = startIso.getHours() * 60 + startIso.getMinutes();
                        const durationMinutes = (endIso.getTime() - startIso.getTime()) / 60000;

                        const topPerc = minutesFromMidnight / (24 * 60);
                        const heightPerc = Math.max(durationMinutes / (24 * 60), 0.02);

                        const el = document.createElement('div');
                        el.className = 'calendar-appointment';
                        const isCompletedEvent = (ev.status || '').toLowerCase() === 'completed';
                        el.draggable = !isCompletedEvent;
                        el.dataset.appointmentId = ev.id;
                        el.style.position = 'absolute';
                        el.style.left = '6px';
                        el.style.right = '6px';
                        el.style.top = (topPerc * colHeight) + 'px';
                        el.style.height = (heightPerc * colHeight) + 'px';
                        el.style.background = ev.color || '#3699ff';
                        el.style.color = '#fff';
                        el.style.padding = '6px 8px';
                        el.style.borderRadius = '6px';
                        el.style.cursor = isCompletedEvent ? 'default' : 'grab';
                        el.style.overflow = 'hidden';
                        el.style.fontSize = '12px';
                        el.style.boxSizing = 'border-box';

                        el.innerHTML = `<div class="calendar-appointment-time">${formatTimeShort(ev.start)} - ${formatTimeShort(ev.end)}</div><div class="calendar-appointment-meta">${ev.staff || ''} - ${ev.title || 'Unassigned'}</div>`;

                        el.addEventListener('dragstart', handleDragStart);
                        el.addEventListener('click', function (e) {
                            e.stopPropagation();
                            if (String(ev.status || '').toLowerCase() === 'completed') {
                                openCompletedAppointmentModal(ev.id, e);
                            } else {
                                openAppointmentModalForEdit(ev.id, e);
                            }
                        });

                        col.appendChild(el);
                    } catch (err) { console.warn('Error rendering event', err); }
                });

                gridBody.querySelectorAll('.calendar-empty-notice').forEach(el => el.remove());
                if (events.length === 0) {
                    const notice = document.createElement('div');
                    notice.className = 'calendar-empty-notice';
                    notice.style.position = 'absolute';
                    notice.style.inset = '0';
                    notice.style.display = 'flex';
                    notice.style.alignItems = 'center';
                    notice.style.justifyContent = 'center';
                    notice.style.pointerEvents = 'none';
                    notice.style.zIndex = '5';
                    notice.style.fontSize = '14px';
                    notice.style.color = '#7e8299';
                    notice.textContent = 'No appointments match the current filters.';
                    gridBody.appendChild(notice);
                }
            }
            appointmentForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                if (apptSaveBtn && apptSaveBtn.classList.contains('d-none')) return;

                const appointmentId = apptIdField.value;
                const staffId = staffField.value;
                const serviceId = serviceField.value;
                const start = fromInputDateTime(startField.value);
                let end = fromInputDateTime(endField.value);

                if (!staffId || !serviceId || !startField.value || !endField.value) {
                    showPageNotice('Staff, service, start and end are required.');
                    return;
                }

                const durationMinutes = getSelectedServiceDurationMinutes();
                if (durationMinutes) {
                    const minEnd = new Date(start.getTime() + (durationMinutes * 60000));
                    if (end < minEnd) {
                        end = minEnd;
                        endField.value = toInputDateTime(end);
                    }
                }

                if (end <= start) {
                    showPageNotice('End time must be after start time.');
                    return;
                }

                const hoursError = validateAppointmentWithinStaffHours(staffId, start, end);
                if (hoursError) {
                    showPageNotice(hoursError);
                    return;
                }

                try {
                    if (apptSaveBtn) {
                        apptSaveBtn.disabled = true;
                        apptSaveBtn.dataset.originalText = apptSaveBtn.textContent;
                        apptSaveBtn.textContent = 'Saving...';
                    }
                    const selectedClientId = clientField.value || null;

                    if (!selectedClientId) {
                        showPageNotice('Client is required. Select an existing client or add a new client.');
                        return;
                    }

                    const payload = {
                        staff_id: staffId,
                        service_id: serviceId,
                        client_id: selectedClientId,
                        location_id: locationField && locationField.value ? locationField.value : null,
                        start_time: toApiDateTime(start),
                        end_time: toApiDateTime(end),
                        status: statusField.value,
                        notes: notesField.value.trim() || null
                    };

                    const url = appointmentId ? calendarUrl(`appointments/${appointmentId}`) : calendarUrl('appointments');
                    const method = appointmentId ? 'PUT' : 'POST';

                    const res = await fetch(url, {
                        method: method,
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
                        body: JSON.stringify(payload)
                    });

                    if (!res.ok) {
                        const msg = await getErrorMessage(res, 'Save failed');
                        throw new Error(msg);
                    }

                    const resData = await res.json().catch(() => ({}));
                    appointmentModal.hide();
                    await loadDataAndRender();
                    showPageNotice('Appointment saved successfully.', 'success');

                    if (resData && resData.redirect_url && payload.status === 'completed') {
                        window.location.href = resData.redirect_url;
                    }
                } catch (err) {
                    showPageNotice(err.message || 'Error saving appointment.');
                } finally {
                    if (apptSaveBtn) {
                        apptSaveBtn.disabled = false;
                        apptSaveBtn.textContent = apptSaveBtn.dataset.originalText || 'Save';
                    }
                }
            });

            serviceField.addEventListener('change', function () {
                const currentStaff = staffField.value;
                const currentStaffObj = (window.CALENDAR_DATA.staffs || []).find(s => String(s.id) === String(currentStaff));
                if (apptIdField.value) {
                    hydrateStaffOptionsForSelectedLocation(true);
                } else {
                    applyServiceDurationToEndTime();
                }
                if (currentStaff) {
                    if (currentStaffObj) {
                        ensureSelectOption(staffField, currentStaff, currentStaffObj.name);
                    }
                    staffField.value = String(currentStaff);
                }
            });
            if (locationField) {
                locationField.addEventListener('change', function () {
                    const currentStaff = staffField.value;
                    if (apptIdField.value) {
                        hydrateStaffOptionsForSelectedLocation(true);
                    } else {
                        const start = fromInputDateTime(startField.value);
                        const end = fromInputDateTime(endField.value);
                        if (startField.value && endField.value && !Number.isNaN(start.getTime()) && !Number.isNaN(end.getTime())) {
                            hydrateStaffOptionsForSlot(start, end);
                        } else {
                            hydrateStaffOptionsForSelectedLocation(true);
                        }
                    }
                    if (currentStaff && hasSelectOptionValue(staffField, currentStaff)) {
                        staffField.value = currentStaff;
                    } else if (currentStaff) {
                        staffField.value = '';
                        showPageNotice('Selected staff is not assigned to this location.');
                    }
                    hydrateServiceOptionsForSelectedStaff();
                });
            }
            staffField.addEventListener('change', function () {
                syncLocationFromStaff();
                hydrateServiceOptionsForSelectedStaff();
                if (serviceField.value && startField.value) applyServiceDurationToEndTime();
            });
            ['calendar-filter-location', 'calendar-filter-staff', 'calendar-filter-service', 'calendar-filter-status'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('change', loadDataAndRender);
            });
            startField.addEventListener('change', function () {
                if (serviceField.value) applyServiceDurationToEndTime();
                if (apptIdField.value) return;
                const start = fromInputDateTime(startField.value);
                const end = fromInputDateTime(endField.value);
                if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) return;
                const current = staffField.value;
                const info = hydrateStaffOptionsForSlot(start, end);
                if (current && hasSelectOptionValue(staffField, current)) {
                    staffField.value = String(current);
                }
                hydrateServiceOptionsForSelectedStaff();
                if (info && info.hasScheduleData && info.count === 0) {
                    const msg = 'No staff scheduled for the selected time.';
                    showPageNotice(msg);
                }
            });

            endField.addEventListener('change', function () {
                if (apptIdField.value) return;
                const start = fromInputDateTime(startField.value);
                const end = fromInputDateTime(endField.value);
                if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) return;
                const current = staffField.value;
                const info = hydrateStaffOptionsForSlot(start, end);
                if (current && hasSelectOptionValue(staffField, current)) {
                    staffField.value = String(current);
                }
                hydrateServiceOptionsForSelectedStaff();
                if (info && info.hasScheduleData && info.count === 0) {
                    const msg = 'No staff scheduled for the selected time.';
                    showPageNotice(msg);
                }
            });

            if (openNewClientModalBtn) {
                openNewClientModalBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    clearNewClientFields();
                    newClientModal.show();
                });
            }


            const clientSearchModalEl = document.getElementById('clientSearchModal');
            const clientSearchModal = clientSearchModalEl ? new bootstrap.Modal(clientSearchModalEl) : null;
            const step1Input = document.getElementById('client-search-step1-input');
            const step1Results = document.getElementById('client-search-step1-results');
            const step1Empty = document.getElementById('client-search-step1-empty');
            const step1OpenNewClientBtn = document.getElementById('step1-open-new-client-modal');
            let step1SlotContext = null;

            if (step1OpenNewClientBtn) {
                step1OpenNewClientBtn.addEventListener('click', function () {
                    if (clientSearchModal) clientSearchModal.hide();
                    clearNewClientFields();
                    newClientModal.show();
                });
            }

            let step1SearchTimer = null;
            if (step1Input) {
                step1Input.addEventListener('input', function () {
                    const term = this.value.trim();
                    if (step1SearchTimer) clearTimeout(step1SearchTimer);
                    if (term.length < 2) {
                        step1Results.classList.add('d-none');
                        step1Empty.classList.add('d-none');
                        return;
                    }
                    step1SearchTimer = setTimeout(async () => {
                        try {
                            const res = await fetch(calendarUrl(`clients/search?q=${encodeURIComponent(term)}`), {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            const clients = await res.json();
                            step1Results.innerHTML = '';
                            if (Array.isArray(clients) && clients.length > 0) {
                                step1Results.classList.remove('d-none');
                                step1Empty.classList.add('d-none');
                                clients.forEach(c => {
                                    const a = document.createElement('a');
                                    a.href = '#';
                                    a.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                                    a.innerHTML = `<div><strong>${escapeHtml(c.name)}</strong><br><small class="text-muted">${escapeHtml(c.phone || c.email || '')}</small></div><button class="btn btn-sm btn-outline-primary">Select</button>`;
                                    a.addEventListener('click', function (ev) {
                                        ev.preventDefault();
                                        clientField.value = String(c.id);
                                        if (clientSearchModal) clientSearchModal.hide();
                                        if (step1SlotContext) {
                                            openAppointmentModalForCreate(step1SlotContext.start, step1SlotContext.end, step1SlotContext.staffId);
                                        } else {
                                            appointmentModal.show();
                                        }
                                    });
                                    step1Results.appendChild(a);
                                });
                            } else {
                                step1Results.classList.add('d-none');
                                step1Empty.classList.remove('d-none');
                            }
                        } catch (err) {
                            console.error(err);
                        }
                    }, 280);
                });
            }

            newClientForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                if (!newClientForm.reportValidity()) return;
                const payload = Object.fromEntries(new FormData(newClientForm).entries());

                try {
                    const quickClientButton = newClientForm.querySelector('button[type="submit"]');
                    window.AppButtonLoading?.set(quickClientButton, 'Adding...');
                    const createClientRes = await fetch(calendarUrl('quick-client'), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': getCsrfToken()
                        },
                        body: JSON.stringify(payload)
                    });

                    if (!createClientRes.ok) {
                        const msg = await getErrorMessage(createClientRes, 'Unable to create client');
                        throw new Error(msg);
                    }

                    const ct = (createClientRes.headers.get('content-type') || '').toLowerCase();
                    if (!ct.includes('application/json')) {
                        const raw = await createClientRes.text();
                        throw new Error(raw ? 'Unexpected server response. Please try again.' : 'Unexpected server response.');
                    }

                    const created = await createClientRes.json();
                    window.CALENDAR_DATA.clients.push(created.client);
                    hydrateClientOptions();

                    if (apptClientTomSelect) {
                        apptClientTomSelect.addOption({
                            id: String(created.client.id),
                            text: `${created.client.name}${created.client.phone ? ` (${created.client.phone})` : ''}`,
                            name: created.client.name || '',
                            first_name: created.client.first_name || '',
                            last_name: created.client.last_name || '',
                            email: created.client.email || '',
                            phone: created.client.phone || ''
                        });
                        apptClientTomSelect.setValue(String(created.client.id));
                    } else {
                        clientField.value = String(created.client.id);
                    }
                    clientField.dispatchEvent(new Event('change', { bubbles: true }));

                    newClientModal.hide();
                    showPageNotice('Client added successfully.', 'success');
                } catch (err) {
                    showPageNotice(err.message || 'Unable to create client.');
                } finally {
                    const quickClientButton = newClientForm.querySelector('button[type="submit"]');
                    window.AppButtonLoading?.reset(quickClientButton);
                }
            });

            // Drag and drop handlers
            let dragAppointmentId = null;
            function handleDragStart(e) {
                dragAppointmentId = this.dataset.appointmentId;
                e.dataTransfer.setData('text/plain', dragAppointmentId);
                // store original position
                e.dataTransfer.effectAllowed = 'move';
            }

            async function handleDropOnColumn(e) {
                e.preventDefault();
                const col = this;
                const apptId = e.dataTransfer.getData('text/plain');
                if (!apptId) return;
                const rect = col.getBoundingClientRect();
                const y = e.clientY - rect.top;
                const colHeight = rect.height;
                const minutes = Math.round((y / colHeight) * 24 * 60);

                // fetch current appointment to know duration
                try {
                    showPageNotice('Saving new appointment time...', 'success', 1800);
                    const res = await fetch(calendarUrl(`appointments/${apptId}`));
                    if (!res.ok) throw new Error(await getErrorMessage(res, 'Could not fetch appointment'));
                    const appt = await res.json();
                    const apptData = appt.appointment || appt;

                    const durationMs = parseCalendarDate(apptData.end).getTime() - parseCalendarDate(apptData.start).getTime();
                    const newStart = new Date(currentWeekStart);
                    newStart.setDate(currentWeekStart.getDate() + parseInt(col.dataset.dayIndex));
                    newStart.setHours(0, 0, 0, 0);
                    newStart.setMinutes(minutes);

                    const newEnd = new Date(newStart.getTime() + durationMs);
                    const apptStaffId = apptData.staffId || apptData.staff_id;
                    const hoursError = validateAppointmentWithinStaffHours(apptStaffId, newStart, newEnd);
                    if (hoursError) {
                        showPageNotice(hoursError);
                        await loadDataAndRender();
                        return;
                    }

                    // send update
                    const upd = await fetch(calendarUrl(`appointments/${apptId}`), {
                        method: 'PUT', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
                        body: JSON.stringify({ start_time: toApiDateTime(newStart), end_time: toApiDateTime(newEnd) })
                    });

                    if (!upd.ok) {
                        const msg = await getErrorMessage(upd, 'Unable to reschedule');
                        showPageNotice(msg || 'Unable to reschedule.');
                        await loadDataAndRender();
                        return;
                    }

                    await loadDataAndRender();
                    showPageNotice('Appointment rescheduled successfully.', 'success');
                } catch (err) { console.error('Drop error', err); showPageNotice(err.message || 'Unable to move appointment'); await loadDataAndRender(); }
            }
            // Click on empty column to create new appointment (Google Calendar-like modal)
            async function handleColumnClick(e) {
                // ignore clicks on appointments
                if (e.target.closest('.calendar-appointment')) return;

                const col = this;
                const rect = col.getBoundingClientRect();
                const y = e.clientY - rect.top;
                const colHeight = rect.height;
                const minutes = Math.round((y / colHeight) * 24 * 60);
                const rounded = Math.round(minutes / 15) * 15;

                const selectedDate = new Date(currentWeekStart);
                selectedDate.setDate(currentWeekStart.getDate() + parseInt(col.dataset.dayIndex, 10));
                selectedDate.setHours(0, 0, 0, 0);
                selectedDate.setMinutes(rounded);

                const durationMinutes = 30;
                const endDate = new Date(selectedDate.getTime() + durationMinutes * 60000);

                let preferredStaffId = '';
                if (currentView === 'day' && col.dataset.staffId) {
                    preferredStaffId = col.dataset.staffId;
                } else {
                    const dateKey = toLocalDate(selectedDate);
                    const dayOfWeek = (selectedDate.getDay() + 6) % 7;
                    const schedules = window._calendarSchedules || [];
                    const availableStaff = schedules.filter(s => {
                        const byDate = s.schedules_by_date && s.schedules_by_date[dateKey];
                        const segments = Array.isArray(byDate)
                            ? byDate
                            : ((s.schedules && s.schedules[dayOfWeek]) ? [s.schedules[dayOfWeek]] : []);
                        return segments.some(seg => seg && seg.is_working);
                    });
                    preferredStaffId = availableStaff.length > 0 ? availableStaff[0].id : '';
                }

                openAppointmentModalForCreate(selectedDate, endDate, preferredStaffId);
            }
            // Quick status action buttons on details card
            document.querySelectorAll('#card-quick-actions .btn-quick-status').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const apptId = appointmentDetailsCard ? appointmentDetailsCard.dataset.appointmentId : null;
                    const newStatus = this.dataset.status;
                    if (!apptId || !newStatus) return;

                    this.disabled = true;
                    try {
                        const res = await fetch(calendarUrl(`appointments/${apptId}`), {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken()
                            },
                            body: JSON.stringify({ status: newStatus })
                        });

                        const data = await res.json();
                        if (res.ok && data.success) {
                            hideAppointmentDetailsCard();
                            // Workflow 1: when an appointment is marked COMPLETED and an invoice
                            // was auto-created, redirect the user to the invoice page.
                            if (newStatus === 'completed' && data.invoiceUrl) {
                                window.location.href = data.invoiceUrl;
                                return;
                            }
                            showPageNotice(`Appointment status updated to ${newStatus.replace('_', ' ')}.`, 'success');
                            await loadDataAndRender();
                        } else {
                            showPageNotice(data.message || 'Failed to update status', 'danger');
                        }
                    } catch (err) {
                        showPageNotice('Error updating status: ' + err.message, 'danger');
                    } finally {
                        this.disabled = false;
                    }
                });
            });

            async function loadClientSnapshot(clientId) {
                const snapshotBox = document.getElementById('appt-client-snapshot');
                if (!snapshotBox) return;
                if (!clientId) {
                    snapshotBox.classList.add('d-none');
                    return;
                }

                try {
                    const res = await fetch(calendarUrl(`clients/${clientId}/snapshot`));
                    if (!res.ok) { snapshotBox.classList.add('d-none'); return; }
                    const data = await res.json();
                    if (!data.success || !data.client) { snapshotBox.classList.add('d-none'); return; }

                    const c = data.client;
                    const nameEl = document.getElementById('snapshot-client-name');
                    const vipEl = document.getElementById('snapshot-vip-badge');
                    const linkEl = document.getElementById('snapshot-full-profile-link');
                    const lastVisitEl = document.getElementById('snapshot-last-visit');
                    const nextApptEl = document.getElementById('snapshot-next-appt');
                    const totalApptsEl = document.getElementById('snapshot-total-appts');
                    const noShowEl = document.getElementById('snapshot-no-show');
                    const outstandingEl = document.getElementById('snapshot-outstanding');
                    const notesContainer = document.getElementById('snapshot-notes-container');
                    const notesSpan = document.getElementById('snapshot-notes');

                    if (nameEl) nameEl.textContent = c.name;
                    if (vipEl) vipEl.classList.toggle('d-none', !c.is_vip);
                    if (linkEl) linkEl.href = `/clients/${c.id}`;
                    if (lastVisitEl) lastVisitEl.textContent = c.last_visit || 'None';
                    if (nextApptEl) nextApptEl.textContent = c.next_appointment || 'None';
                    if (totalApptsEl) totalApptsEl.textContent = c.total_appointments || '0';
                    if (noShowEl) noShowEl.textContent = c.no_show_count || '0';
                    if (outstandingEl) outstandingEl.textContent = '$' + (c.outstanding_amount || 0).toFixed(2);

                    if (c.notes && notesContainer && notesSpan) {
                        notesSpan.textContent = c.notes;
                        notesContainer.classList.remove('d-none');
                    } else if (notesContainer) {
                        notesContainer.classList.add('d-none');
                    }

                    snapshotBox.classList.remove('d-none');
                } catch (e) {
                    snapshotBox.classList.add('d-none');
                }
            }

            if (clientField) {
                clientField.addEventListener('change', function () {
                    loadClientSnapshot(this.value);
                });
            }

            function getCsrfToken() {
                const meta = document.querySelector('meta[name="csrf-token"]');
                return meta ? meta.getAttribute('content') : '';
            }

            function copyFiltersToUrl(url) {
                ['calendar-filter-location', 'calendar-filter-staff', 'calendar-filter-service', 'calendar-filter-status'].forEach(id => {
                    const el = document.getElementById(id);
                    const value = el ? el.value : '';
                    const param = id.replace('calendar-filter-', '');
                    if (value) url.searchParams.set(param, value);
                    else url.searchParams.delete(param);
                });
                return url;
            }

            // Navigation
            document.getElementById('prev-week').addEventListener('click', () => {
                if (currentView === 'month') {
                    navigateMonth(-1);
                    return;
                }
                const step = getNavigationStepDays();
                currentWeekStart.setDate(currentWeekStart.getDate() - step);
                buildWeekView();
                loadDataAndRender();
            });
            document.getElementById('next-week').addEventListener('click', () => {
                if (currentView === 'month') {
                    navigateMonth(1);
                    return;
                }
                const step = getNavigationStepDays();
                currentWeekStart.setDate(currentWeekStart.getDate() + step);
                buildWeekView();
                loadDataAndRender();
            });
            document.getElementById('btn-today').addEventListener('click', () => {
                const now = new Date();
                if (currentView === 'month') {
                    const url = copyFiltersToUrl(new URL(window.location.href));
                    url.searchParams.set('view', 'month');
                    url.searchParams.set('month', monthKeyFromDate(now));
                    window.location.href = url.toString();
                    return;
                }
                if (currentView === 'day') {
                    currentWeekStart = new Date(now);
                    currentWeekStart.setHours(0, 0, 0, 0);
                } else {
                    const d = now.getDay();
                    const df = now.getDate() - d + (d === 0 ? -6 : 1);
                    currentWeekStart = new Date(now.setDate(df));
                    currentWeekStart.setHours(0, 0, 0, 0);
                }
                buildWeekView();
                loadDataAndRender();
            });

            const resetFiltersBtn = document.getElementById('calendar-reset-filters');
            if (resetFiltersBtn) resetFiltersBtn.addEventListener('click', function () {
                ['calendar-filter-location', 'calendar-filter-staff', 'calendar-filter-service', 'calendar-filter-status'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.value = '';
                });
                if (currentView === 'month') {
                    const url = new URL(window.location.href);
                    url.searchParams.set('view', 'month');
                    url.searchParams.set('month', monthKeyFromDate(monthCalendar ? monthCalendar.getDate() : new Date()));
                    window.location.href = url.toString();
                    return;
                }
                loadDataAndRender();
            });

            if (viewSelect) viewSelect.addEventListener('change', function () {
                const view = this.value;
                if (!view) return;

                currentView = view;
                const viewTitle = `${view.charAt(0).toUpperCase()}${view.slice(1)} View`;
                if (view === 'month') {
                    const url = copyFiltersToUrl(new URL(window.location.href));
                    url.searchParams.set('view', 'month');
                    url.searchParams.set('month', monthKeyFromDate(currentWeekStart));
                    window.location.href = url.toString();
                    return;
                }
                showTimeGridView();

                if (view === 'day') {
                    const now = new Date();
                    currentWeekStart = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0, 0, 0);
                } else {
                    const anchor = new Date(currentWeekStart);
                    const d = anchor.getDay();
                    const df = anchor.getDate() - d + (d === 0 ? -6 : 1);
                    currentWeekStart = new Date(anchor.setDate(df));
                    currentWeekStart.setHours(0, 0, 0, 0);
                }

                buildWeekView();
                loadDataAndRender();
            });
            if (closeDetailsCardBtn) {
                closeDetailsCardBtn.addEventListener('click', hideAppointmentDetailsCard);
            }
            document.addEventListener('click', function (e) {
                if (!appointmentDetailsCard || appointmentDetailsCard.classList.contains('d-none')) return;
                if (appointmentDetailsCard.contains(e.target)) return;
                if (e.target.closest('.calendar-appointment')) return;
                hideAppointmentDetailsCard();
            });
            newClientModalEl.addEventListener('hidden.bs.modal', clearNewClientFields);

            // initial build + load
            updateTimezoneLabel();
            if (currentView === 'month') {
                showMonthView();
            } else {
                showTimeGridView();
                buildWeekView();
                loadDataAndRender();
            }
        });
    </script>
@endpush