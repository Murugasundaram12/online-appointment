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

        #new-client-form-error {
            font-size: 0.86rem;
            padding: 0.62rem 0.75rem;
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

        .calendar-notice {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 1100;
            min-width: 280px;
            max-width: 420px;
            padding: 0.75rem 0.9rem;
            border-radius: 8px;
            box-shadow: 0 10px 24px rgba(24, 28, 50, 0.15);
            font-size: 0.88rem;
            font-weight: 500;
        }

        .calendar-notice.notice-danger {
            background: #fff5f8;
            color: #a11a39;
            border: 1px solid #f7ceda;
        }

        .calendar-notice.notice-success {
            background: #f0fff6;
            color: #0f6a34;
            border: 1px solid #bfeccc;
        }

        #appointment-form-error {
            font-size: 0.88rem;
            padding: 0.65rem 0.75rem;
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
                            <option value="{{ $location->id }}" {{ (string)($filters['location_id'] ?? '') === (string) $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                        @endforeach
                    </select>
                    <select id="calendar-filter-staff" class="form-select form-select-sm" style="max-width: 210px;">
                        <option value="">All staff</option>
                        @foreach($staffs ?? [] as $staff)
                            <option value="{{ $staff->id }}" {{ (string)($filters['staff_id'] ?? '') === (string) $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                        @endforeach
                    </select>
                    <select id="calendar-filter-service" class="form-select form-select-sm" style="max-width: 210px;">
                        <option value="">All services</option>
                        @foreach($services ?? [] as $service)
                            <option value="{{ $service->id }}" {{ (string)($filters['service_id'] ?? '') === (string) $service->id ? 'selected' : '' }}>{{ $service->name }}</option>
                        @endforeach
                    </select>
                    <select id="calendar-filter-status" class="form-select form-select-sm" style="max-width: 180px;">
                        <option value="">All statuses</option>
                        <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="booked" {{ ($filters['status'] ?? '') === 'booked' ? 'selected' : '' }}>Booked</option>
                        <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
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

    <div id="calendar-notice" class="calendar-notice d-none" role="alert" aria-live="polite"></div>

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
    </div>

    <div class="modal fade app-modal" id="appointmentModal" tabindex="-1" aria-labelledby="appointment-modal-title" aria-hidden="true">
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
                            <div id="appointment-form-error" class="alert alert-danger d-none" role="alert"></div>

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
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Client <span class="required-mark">*</span></label>
                                <input type="search" id="appt-client-search" class="form-control mb-2" placeholder="Search clients by name, phone, or email" autocomplete="off" />
                                <div class="d-flex gap-2">
                                    <select id="appt-client" class="form-select"></select>
                                    <button type="button" class="btn btn-new-client" id="open-new-client-modal">+
                                        New</button>
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
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Notes</label>
                                <textarea id="appt-notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <div id="appointment-readonly-details" class="d-none">
                            <div class="detail-row">
                                <div class="detail-label">Staff</div>
                                <div class="detail-value" id="readonly-staff"></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Service</div>
                                <div class="detail-value" id="readonly-service"></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Client</div>
                                <div class="detail-value" id="readonly-client"></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Start</div>
                                <div class="detail-value" id="readonly-start"></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">End</div>
                                <div class="detail-value" id="readonly-end"></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Status</div>
                                <div class="detail-value" id="readonly-status"></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Notes</div>
                                <div class="detail-value" id="readonly-notes"></div>
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

    <div class="modal fade app-modal" id="newClientModal" tabindex="-1" aria-labelledby="newClientModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
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
                    <div class="modal-body">
                        <div id="new-client-form-error" class="alert alert-danger d-none" role="alert"></div>
                        <div class="mb-2">
                            <label class="form-label">Client Name <span class="required-mark">*</span></label>
                            <input type="text" id="new-client-name" class="form-control" required />
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Client Email <span class="required-mark">*</span></label>
                            <input type="email" id="new-client-email" class="form-control" required />
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Client Phone</label>
                            <input type="text" id="new-client-phone" class="form-control" />
                        </div>
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
            const pageNotice = document.getElementById('calendar-notice');
            const formErrorBox = document.getElementById('appointment-form-error');
            const viewSelect = document.getElementById('calendar-view-select');
            const appointmentDetailsCard = document.getElementById('appointment-details-card');
            const closeDetailsCardBtn = document.getElementById('close-appointment-details-card');
            const monthContainer = document.getElementById('calendar-month-container');
            const monthCalendarRoot = document.getElementById('staff-month-calendar');
            let monthCalendar = null;
            let noticeTimer = null;
            if (viewSelect && viewSelect.value) currentView = viewSelect.value;

            function showPageNotice(message, type = 'danger', timeout = 4200) {
                window.AppToast?.show({
                    type: type === 'success' ? 'success' : 'danger',
                    title: type === 'success' ? 'Success' : 'Calendar notice',
                    message
                });
                if (!pageNotice) return;
                if (noticeTimer) clearTimeout(noticeTimer);
                pageNotice.classList.remove('d-none', 'notice-danger', 'notice-success');
                pageNotice.classList.add(type === 'success' ? 'notice-success' : 'notice-danger');
                pageNotice.textContent = message;
                noticeTimer = setTimeout(() => pageNotice.classList.add('d-none'), timeout);
            }

            function showFormError(message) {
                if (!formErrorBox) return;
                formErrorBox.textContent = message;
                formErrorBox.classList.remove('d-none');
            }

            function clearFormError() {
                if (!formErrorBox) return;
                formErrorBox.textContent = '';
                formErrorBox.classList.add('d-none');
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
                        if (typeof openAppointmentModalForEdit === 'function') {
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
            const newClientFormErrorBox = document.getElementById('new-client-form-error');

            function showNewClientFormError(message) {
                if (!newClientFormErrorBox) return;
                newClientFormErrorBox.textContent = message;
                newClientFormErrorBox.classList.remove('d-none');
            }

            function clearNewClientFormError() {
                if (!newClientFormErrorBox) return;
                newClientFormErrorBox.textContent = '';
                newClientFormErrorBox.classList.add('d-none');
            }

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
            const newClientNameField = document.getElementById('new-client-name');
            const newClientEmailField = document.getElementById('new-client-email');
            const newClientPhoneField = document.getElementById('new-client-phone');

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
                if (readonlyStaff) readonlyStaff.textContent = appt.staff || 'N/A';
                if (readonlyService) readonlyService.textContent = appt.service || 'N/A';
                if (readonlyClient) readonlyClient.textContent = appt.title || 'Unassigned';
                if (readonlyStart) readonlyStart.textContent = formatDateTimeLong(appt.start);
                if (readonlyEnd) readonlyEnd.textContent = formatDateTimeLong(appt.end);
                if (readonlyStatus) readonlyStatus.textContent = appt.status || '-';
                if (readonlyNotes) readonlyNotes.textContent = appt.notes || '-';
            }

            function fillReadonlyAppointmentCard(appt) {
                if (readonlyCardStaff) readonlyCardStaff.textContent = appt.staff || 'N/A';
                if (readonlyCardService) readonlyCardService.textContent = appt.service || 'N/A';
                if (readonlyCardClient) readonlyCardClient.textContent = appt.title || 'Unassigned';
                if (readonlyCardStart) readonlyCardStart.textContent = formatDateTimeLong(appt.start);
                if (readonlyCardEnd) readonlyCardEnd.textContent = formatDateTimeLong(appt.end);
                if (readonlyCardStatus) readonlyCardStatus.textContent = appt.status || '-';
                if (readonlyCardNotes) readonlyCardNotes.textContent = appt.notes || '-';
                if (readonlyCardStatusChip) {
                    const status = String(appt.status || '').toLowerCase();
                    readonlyCardStatusChip.className = 'card-status-chip d-none';
                    readonlyCardStatusChip.textContent = '';
                    if (status === 'tentative') {
                        readonlyCardStatusChip.classList.remove('d-none');
                        readonlyCardStatusChip.classList.add('status-tentative');
                        readonlyCardStatusChip.textContent = 'Tentative';
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
                    // ignore JSON parse errors and fall back to raw text
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

            function getStaffScheduleForDate(staffId, dateObj) {
                const schedules = window._calendarSchedules || [];
                const numericStaffId = Number.parseInt(staffId, 10);
                const staff = schedules.find(s => Number.parseInt(s.id, 10) === numericStaffId);
                if (!staff) return null;

                const dateKey = toLocalDate(dateObj);
                const dayOfWeek = (dateObj.getDay() + 6) % 7; // 0=Mon ... 6=Sun
                const byDate = staff.schedules_by_date && staff.schedules_by_date[dateKey];
                const byWeek = staff.schedules && staff.schedules[dayOfWeek];
                return byDate || byWeek || null;
            }

            function validateAppointmentWithinStaffHours(staffId, startDate, endDate) {
                if (!staffId || !(startDate instanceof Date) || !(endDate instanceof Date)) return null;
                if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) return null;

                if (toLocalDate(startDate) !== toLocalDate(endDate)) {
                    return 'Appointment must be within one day and inside staff working hours.';
                }

                const schedule = getStaffScheduleForDate(staffId, startDate);
                if (!schedule || !schedule.is_working) {
                    return 'This staff member is not working on the selected date.';
                }

                const workingStart = parseTimeToMinutes(schedule.start_time);
                const workingEnd = parseTimeToMinutes(schedule.end_time);
                if (workingStart === null || workingEnd === null) {
                    return 'Staff working hours are not configured for this date.';
                }

                const apptStart = (startDate.getHours() * 60) + startDate.getMinutes();
                const apptEnd = (endDate.getHours() * 60) + endDate.getMinutes();

                if (apptStart < workingStart || apptEnd > workingEnd) {
                    return `Outside working hours. Allowed time is ${format12Hour(schedule.start_time)} to ${format12Hour(schedule.end_time)}.`;
                }

                return null;
            }

            function clearNewClientFields() {
                newClientNameField.value = '';
                newClientEmailField.value = '';
                newClientPhoneField.value = '';
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
                    option.textContent = item[labelKey];
                    selectEl.appendChild(option);
                });
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
                let staffs = (window.CALENDAR_DATA.staffs || []).filter(staff => staffMatchesLocation(staff, locationId));
                if (respectService) {
                    const serviceId = serviceField ? serviceField.value : '';
                    staffs = staffs.filter(staff => staffMatchesServiceCategory(staff, serviceId));
                }
                fillSelect(staffField, staffs, staffs.length ? 'Select staff' : 'No staff assigned to this location');
                return staffs;
            }

            function normalizeCategory(value) {
                return String(value ?? '').trim().toLowerCase();
            }

            function servicesForStaff(staffId) {
                const staff = (window.CALENDAR_DATA.staffs || []).find(s => String(s.id) === String(staffId));
                const services = window.CALENDAR_DATA.services || [];
                const staffCategory = normalizeCategory(staff ? staff.category : '');
                if (!staffCategory) return services;

                return services.filter(s => {
                    const category = normalizeCategory(s.category ? s.category.name : '');
                    if (!category) return true;
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
                } else {
                    serviceField.value = '';
                }

                serviceField.disabled = !!(staffId && matching.length === 0);
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

            function isScheduleCoveringSlot(schedule, startDate, endDate) {
                if (!schedule || !schedule.is_working) return false;
                if (!(startDate instanceof Date) || !(endDate instanceof Date)) return false;
                if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) return false;
                if (toLocalDate(startDate) !== toLocalDate(endDate)) return false;

                const workingStart = parseTimeToMinutes(schedule.start_time);
                const workingEnd = parseTimeToMinutes(schedule.end_time);
                if (workingStart === null || workingEnd === null) return false;

                const apptStart = (startDate.getHours() * 60) + startDate.getMinutes();
                const apptEnd = (endDate.getHours() * 60) + endDate.getMinutes();
                return apptStart >= workingStart && apptEnd <= workingEnd;
            }

            function hydrateStaffOptionsForSlot(startDate, endDate) {
                const locationId = locationField ? locationField.value : '';
                const serviceId = serviceField ? serviceField.value : '';
                let allStaffs = (window.CALENDAR_DATA.staffs || []).filter(staff => staffMatchesLocation(staff, locationId));
                if (serviceId) {
                    allStaffs = allStaffs.filter(staff => staffMatchesServiceCategory(staff, serviceId));
                }
                const schedules = window._calendarSchedules || [];

                if (!(startDate instanceof Date) || Number.isNaN(startDate.getTime())) {
                    fillSelect(staffField, allStaffs, 'Select staff');
                    return { count: allStaffs.length, hasScheduleData: false };
                }

                if (!Array.isArray(schedules) || schedules.length === 0) {
                    fillSelect(staffField, [], 'No staff schedule data');
                    return { count: 0, hasScheduleData: false };
                }

                const dateKey = toLocalDate(startDate);
                const dayOfWeek = (startDate.getDay() + 6) % 7; // 0=Mon ... 6=Sun
                const workingStaffIds = new Set(
                    schedules
                        .filter(s => {
                            const byDate = s.schedules_by_date && s.schedules_by_date[dateKey];
                            const byWeek = s.schedules && s.schedules[dayOfWeek];
                            const effectiveSchedule = byDate || byWeek;
                            return isScheduleCoveringSlot(effectiveSchedule, startDate, endDate);
                        })
                        .map(s => String(s.id)),
                );

                const dateStaffs = allStaffs.filter(s => workingStaffIds.has(String(s.id)));
                fillSelect(staffField, dateStaffs, dateStaffs.length ? 'Select staff' : 'No staff scheduled for selected time');
                return { count: dateStaffs.length, hasScheduleData: true };
            }

            function hydrateClientOptions() {
                clientField.innerHTML = '';
                const selectClient = document.createElement('option');
                selectClient.value = '';
                selectClient.textContent = 'Select client';
                clientField.appendChild(selectClient);

                (window.CALENDAR_DATA.clients || []).forEach(client => {
                    const option = document.createElement('option');
                    option.value = client.id;
                    option.textContent = `${client.name}${client.phone ? ` (${client.phone})` : ''}`;
                    clientField.appendChild(option);
                });
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
                        showFormError(msg);
                        showPageNotice(msg);
                    } else {
                        clearFormError();
                    }
                }
            }

            function openAppointmentModalForCreate(startDate, endDate, staffId = '') {
                clearFormError();
                hideAppointmentDetailsCard();
                modalTitle.textContent = 'New Appointment';
                setAppointmentReadOnlyMode(false);
                apptIdField.value = '';
                hydrateFormOptions();
                const staffInfo = hydrateStaffOptionsForSlot(startDate, endDate);
                if (staffInfo && staffInfo.hasScheduleData && staffInfo.count === 0) {
                    const msg = 'No staff scheduled for the selected time. Please create staff schedule first.';
                    showPageNotice(msg);
                    showFormError(msg);
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
                    clearFormError();
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
                    clearFormError();
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

            // Build header and day columns
            function buildWeekView() {
                // Generate time column
                generateTimeColumn();
                setGridHeight();
                visibleDays = currentView === 'day' ? 1 : 7;
                header.style.gridTemplateColumns = `80px repeat(${visibleDays}, 1fr)`;
                gridBody.style.gridTemplateColumns = `80px repeat(${visibleDays}, 1fr)`;

                // header
                const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

                // preserve timezone cell
                const timezoneCell = header.firstElementChild;
                header.innerHTML = '';
                header.appendChild(timezoneCell);

                for (let i = 0; i < visibleDays; i++) {
                    const date = new Date(currentWeekStart);
                    date.setDate(currentWeekStart.getDate() + i);

                    const headerCell = document.createElement('div');
                    headerCell.className = 'header-cell';
                    if (isSameDay(date, new Date())) headerCell.classList.add('active-day');

                    const dayLabel = currentView === 'day' ? date.toLocaleDateString([], { weekday: 'short' }) : days[i];
                    headerCell.innerHTML = `<span class="day-label">${dayLabel}</span><span class="day-number">${date.getDate()}</span>`;

                    // Add staff schedule display under the date
                    const scheduleContainer = document.createElement('div');
                    scheduleContainer.className = 'staff-schedule-container';
                    scheduleContainer.id = `staff-schedules-${i}`;
                    scheduleContainer.style.borderTop = '1px solid var(--calendar-border-hourly)';

                    headerCell.appendChild(scheduleContainer);
                    header.appendChild(headerCell);
                }

                // Show today's date when this week contains today, otherwise show week start date.
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

                // body columns
                // Keep the first child (time column) and replace the 7 day columns with containers
                const children = Array.from(gridBody.children);
                // remove all day columns after the time column
                while (gridBody.children.length > 1) gridBody.removeChild(gridBody.lastChild);

                for (let i = 0; i < visibleDays; i++) {
                    const col = document.createElement('div');
                    col.className = 'grid-cell day-column';
                    col.style.position = 'relative';
                    col.dataset.dayIndex = i;
                    // allow dropping
                    col.addEventListener('dragover', ev => ev.preventDefault());
                    col.addEventListener('drop', handleDropOnColumn);
                    // click to quick create
                    col.addEventListener('click', handleColumnClick);
                    gridBody.appendChild(col);
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

                    schedules.forEach(staff => {
                        const byDate = staff.schedules_by_date && staff.schedules_by_date[dateKey]
                            ? staff.schedules_by_date[dateKey]
                            : null;

                        const sch = byDate;

                        if (sch && sch.is_working) {
                            const item = document.createElement('div');
                            item.className = 'staff-schedule-item';
                            const startTime = format12Hour(sch.start_time);
                            const endTime = format12Hour(sch.end_time);
                            item.innerHTML = `<strong>${staff.name}</strong><div class="hours">${startTime} – ${endTime}</div>`;
                            container.appendChild(item);
                        }
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

                        let dayIndex = -1;
                        for (let i = 0; i < visibleDays; i++) {
                            const dayDate = new Date(currentWeekStart);
                            dayDate.setDate(currentWeekStart.getDate() + i);
                            if (toLocalDate(dayDate) === eventDayKey) {
                                dayIndex = i;
                                break;
                            }
                        }
                        if (dayIndex < 0 || dayIndex >= visibleDays) return;

                        const col = cols[dayIndex];
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
                            openAppointmentModalForEdit(ev.id, e);
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
                    showFormError('Staff, service, start and end are required.');
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
                    showFormError('End time must be after start time.');
                    return;
                }

                if (!appointmentId && isPastDate(start)) {
                    const pastDateMessage = 'Only current date and future dates can be scheduled.';
                    showFormError(pastDateMessage);
                    showPageNotice(pastDateMessage);
                    return;
                }

                const hoursError = validateAppointmentWithinStaffHours(staffId, start, end);
                if (hoursError) {
                    showFormError(hoursError);
                    showPageNotice(hoursError);
                    return;
                }

                try {
                    clearFormError();
                    if (apptSaveBtn) {
                        apptSaveBtn.disabled = true;
                        apptSaveBtn.dataset.originalText = apptSaveBtn.textContent;
                        apptSaveBtn.textContent = 'Saving...';
                    }
                    const selectedClientId = clientField.value || null;

                    if (!selectedClientId) {
                        showFormError('Client is required. Select an existing client or add a new client.');
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

                    appointmentModal.hide();
                    await loadDataAndRender();
                    showPageNotice('Appointment saved successfully.', 'success');
                } catch (err) {
                    showFormError(err.message || 'Error saving appointment.');
                } finally {
                    if (apptSaveBtn) {
                        apptSaveBtn.disabled = false;
                        apptSaveBtn.textContent = apptSaveBtn.dataset.originalText || 'Save';
                    }
                }
            });

            serviceField.addEventListener('change', function () {
                const currentStaff = staffField.value;
                if (apptIdField.value) {
                    hydrateStaffOptionsForSelectedLocation(true);
                } else {
                    applyServiceDurationToEndTime();
                }
                if (currentStaff && hasSelectOptionValue(staffField, currentStaff)) {
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
                        showFormError('Selected staff is not assigned to this location.');
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
                    showFormError(msg);
                    showPageNotice(msg);
                } else {
                    clearFormError();
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
                    showFormError(msg);
                    showPageNotice(msg);
                } else {
                    clearFormError();
                }
            });

                if (openNewClientModalBtn) {
                openNewClientModalBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    clearNewClientFields();
                    clearNewClientFormError();
                    newClientModal.show();
                });
            }


            newClientForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                clearNewClientFormError();

                const name = newClientNameField.value.trim();
                const email = newClientEmailField.value.trim();
                const phone = newClientPhoneField.value.trim();

                if (!name || !email) {
                    showNewClientFormError('Client name and email are required.');
                    return;
                }

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
                        body: JSON.stringify({
                            name: name,
                            email: email,
                            phone: phone || null
                        })
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

                    // Ensure the appointment form dropdown reflects the newly created client immediately
                    clientField.value = String(created.client.id);
                    clientField.dispatchEvent(new Event('change', { bubbles: true }));

                    newClientModal.hide();
                    showPageNotice('Client added successfully.', 'success');
                } catch (err) {
                    showNewClientFormError(err.message || 'Unable to create client.');
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

                if (isPastDate(selectedDate)) {
                    showPageNotice('Only current date and future dates can be scheduled.');
                    return;
                }

                const durationMinutes = 30;
                const endDate = new Date(selectedDate.getTime() + durationMinutes * 60000);

                // Default to first working staff on that day if available
                const dateKey = toLocalDate(selectedDate);
                const dayOfWeek = (selectedDate.getDay() + 6) % 7;
                const schedules = window._calendarSchedules || [];
                const availableStaff = schedules.filter(s => {
                    const byDate = s.schedules_by_date && s.schedules_by_date[dateKey];
                    const byWeek = s.schedules && s.schedules[dayOfWeek];
                    const effectiveSchedule = byDate || byWeek;
                    return effectiveSchedule && effectiveSchedule.is_working;
                });
                const preferredStaffId = availableStaff.length > 0 ? availableStaff[0].id : '';

                openAppointmentModalForCreate(selectedDate, endDate, preferredStaffId);
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
            modalEl.addEventListener('hidden.bs.modal', clearFormError);
            newClientModalEl.addEventListener('hidden.bs.modal', function () { clearNewClientFields(); clearNewClientFormError(); });

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
