@extends('layouts.app')

@section('title', 'Schedule')

@php
    if (!function_exists('ordinalSuffix')) {
        function ordinalSuffix($num)
        {
            if (!in_array(($num % 100), [11, 12, 13])) {
                switch ($num % 10) {
                    case 1:
                        return 'st';
                    case 2:
                        return 'nd';
                    case 3:
                        return 'rd';
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
            min-width: 280px;
            background-color: #fff;
            border-right: 1px solid #eef0f7;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .schedule-container {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            background-color: #fff;
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
            position: relative;
            overflow-x: auto;
            overflow-y: visible;
        }

        .grid-header {
            display: grid;
            grid-template-columns: 80px repeat(7, 1fr);
            min-width: 700px;
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
            min-width: 700px;
            height: auto;
            border-bottom: 1px solid #eef0f7;
        }

        .time-cell {
            height: 50px;
            box-sizing: border-box;
            padding: 4px 8px 0 0;
            line-height: 1;
            text-align: right;
            border-right: 1px solid #eef0f7;
            border-bottom: 1px solid #f8f9fa;
            font-size: 0.72rem;
            font-weight: 500;
            color: #7e8299;
            display: flex;
            align-items: flex-start;
            justify-content: flex-end;
        }

        .slot-cell {
            height: 50px;
            min-height: 50px;
            max-height: 50px;
            box-sizing: border-box;
            border-right: 1px solid #f8f9fa;
            border-bottom: 1px solid #f8f9fa;
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
            box-sizing: border-box;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            overflow: hidden;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: flex-start;
            text-align: left;
            white-space: nowrap;
            text-overflow: ellipsis;
            z-index: 5;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            border: 1px solid rgba(0, 0, 0, 0.15);
            line-height: 1.2;
        }

        .schedule-block:hover {
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transform: scale(1.02);
            z-index: 10;
        }

        .schedule-block.weekend {
            /* Weekend shifts preserve the staff color vibrancy */
            opacity: 0.98;
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

        .weekday-checkbox-btn input[type="checkbox"]:checked+label {
            background-color: #3699ff;
            color: #ffffff;
            border-color: #3699ff;
            box-shadow: 0 4px 12px rgba(54, 153, 255, 0.25);
        }

        .schedule-entries-card {
            overflow: visible !important;
        }

        .schedule-entries-card:hover {
            transform: none !important;
        }

        .schedule-entries-card .table-responsive {
            min-height: 200px;
        }

        @media (min-width: 992px) {
            .schedule-entries-card .table-responsive {
                overflow: visible;
            }
        }

        /* Schedule Entries Section & Card */
        .schedule-entries-section {
            background-color: #f8fafc;
            border-top: 1px solid #eef0f7;
        }

        .schedule-entries-card {
            border: 1px solid #e2e8f0 !important;
            border-radius: 14px !important;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05) !important;
            background: #ffffff !important;
            overflow: visible !important;
        }

        .schedule-entries-card .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            border-top-left-radius: 14px !important;
            border-top-right-radius: 14px !important;
        }

        .schedule-entries-header-icon {
            width: 38px;
            height: 38px;
            background-color: #eff6ff;
            color: #3b82f6;
            flex-shrink: 0;
        }

        .schedule-count-badge {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
        }

        .schedule-search-input {
            width: 250px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding-left: 2.2rem !important;
            font-size: 0.82rem;
            transition: all 0.15s ease;
        }

        .schedule-search-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        /* Schedule Table */
        .schedule-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        .schedule-table thead th {
            background-color: #f8fafc;
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 13px 16px;
            border-bottom: 1px solid #e2e8f0;
            border-top: none;
            white-space: nowrap;
        }

        .schedule-table tbody tr.schedule-entry-row {
            transition: background-color 0.15s ease;
        }

        .schedule-table tbody tr.schedule-entry-row:hover {
            background-color: #f8fafc;
        }

        .schedule-table tbody td {
            padding: 14px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.84rem;
        }

        .staff-row-name {
            font-size: 0.88rem;
            line-height: 1.25;
            color: #1e293b;
        }

        .staff-row-cat {
            font-size: 0.75rem;
            line-height: 1.2;
            margin-top: 2px;
            color: #64748b;
        }

        .schedule-type-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 9px;
            border-radius: 6px;
            letter-spacing: 0.02em;
        }

        .type-badge-weekly {
            background-color: #eff6ff !important;
            color: #2563eb !important;
            border: 1px solid #dbeafe !important;
        }

        .type-badge-monthly {
            background-color: #f5f3ff !important;
            color: #7c3aed !important;
            border: 1px solid #ede9fe !important;
        }

        .type-badge-yearly {
            background-color: #fffbeb !important;
            color: #d97706 !important;
            border: 1px solid #fef3c7 !important;
        }

        .type-badge-onetime {
            background-color: #f1f5f9 !important;
            color: #475569 !important;
            border: 1px solid #e2e8f0 !important;
        }

        .schedule-time-pill {
            background: #f8fafc;
            color: #334155;
            border: 1px solid #e2e8f0;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 4px 7px;
            border-radius: 6px;
        }

        .badge-status-pill {
            display: inline-flex !important;
            align-items: center !important;
            gap: 5px !important;
            padding: 4px 10px !important;
            border-radius: 20px !important;
            font-size: 0.74rem !important;
            font-weight: 600 !important;
            letter-spacing: 0.02em;
        }

        .status-dot-working {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.9);
            display: inline-block;
        }

        .status-dot-off {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.9);
            display: inline-block;
        }

        .btn-white-border {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            color: #334155;
            font-size: 0.8rem;
            font-weight: 500;
            border-radius: 6px;
            padding: 4px 10px;
            transition: all 0.15s ease;
        }

        .btn-white-border:hover {
            background-color: #f8fafc;
            border-color: #cbd5e1;
            color: #0f172a;
        }

        .btn-outline-danger-subtle {
            background-color: #ffffff;
            border: 1px solid #fecdd3;
            color: #e11d48;
            font-size: 0.8rem;
            font-weight: 500;
            border-radius: 6px;
            padding: 4px 10px;
            transition: all 0.15s ease;
        }

        .btn-outline-danger-subtle:hover {
            background-color: #fff1f2;
            border-color: #fda4af;
            color: #be123c;
        }

        /* Premium Custom Dropdown Component */
        .custom-dropdown {
            position: relative;
            display: inline-block;
        }

        .custom-dropdown-trigger {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.15s ease-in-out;
            white-space: nowrap;
        }

        .custom-dropdown-trigger .bx-chevron-down {
            font-size: 0.85rem;
            transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .custom-dropdown.show>.custom-dropdown-trigger .bx-chevron-down {
            transform: rotate(180deg);
        }

        .custom-dropdown-menu {
            position: absolute;
            top: calc(100% + 5px);
            right: 0;
            z-index: 1060;
            min-width: 230px;
            max-width: calc(100vw - 32px);
            padding: 6px;
            margin: 0;
            background-color: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.12), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px) scale(0.98);
            transform-origin: top right;
            transition: opacity 0.16s cubic-bezier(0.16, 1, 0.3, 1), transform 0.16s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.16s ease;
            pointer-events: none;
        }

        .custom-dropdown.show>.custom-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }

        .custom-dropdown.dropup>.custom-dropdown-menu {
            top: auto;
            bottom: calc(100% + 5px);
            transform: translateY(6px) scale(0.98);
            transform-origin: bottom right;
        }

        .custom-dropdown.dropup.show>.custom-dropdown-menu {
            transform: translateY(0) scale(1);
        }

        .custom-dropdown-item {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 8px 12px;
            font-size: 0.83rem;
            font-weight: 500;
            color: #334155;
            text-align: left;
            text-decoration: none;
            background: transparent;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.14s ease, color 0.14s ease;
            white-space: nowrap;
            box-sizing: border-box;
            gap: 8px;
        }

        .custom-dropdown-item i {
            font-size: 1.1rem;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .custom-dropdown-item span {
            flex: 1;
        }

        .custom-dropdown-item-primary {
            color: #1e293b;
        }

        .custom-dropdown-item-primary:hover,
        .custom-dropdown-item-primary:focus {
            background-color: #f1f5f9;
            color: #0f172a;
            outline: none;
        }

        .custom-dropdown-item-warning {
            color: #b45309;
        }

        .custom-dropdown-item-warning:hover,
        .custom-dropdown-item-warning:focus {
            background-color: #fffbeb;
            color: #b45309;
            outline: none;
        }

        .custom-dropdown-item-danger {
            color: #e11d48;
        }

        .custom-dropdown-item-danger:hover,
        .custom-dropdown-item-danger:focus {
            background-color: #fff1f2;
            color: #be123c;
            outline: none;
        }

        .custom-dropdown-divider {
            height: 0;
            margin: 4px 6px;
            overflow: hidden;
            border-top: 1px solid #f1f5f9;
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
    <div class="d-flex">
        <!-- Secondary Sidebar: Provider Selection -->
        <div class="secondary-sidebar">
            <div class="p-4 border-bottom">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="fs-6 m-0 fw-bold">Schedule list</h2>
                    <button class="btn btn-link text-muted p-0"><i class='bx bx-chevron-left fs-4'></i></button>
                </div>
                <div class="provider-list">
                    @foreach($staff as $member)
                        @php $col = $staffColors[$member->id] ?? '#2563eb'; @endphp
                        <div class="provider-item {{ empty($isAllStaff) && isset($currentStaff) && $currentStaff->id == $member->id ? 'active' : '' }}"
                            data-staff-id="{{ $member->id }}" onclick="selectStaffFilter({{ $member->id }})">
                            <div class="d-flex align-items-center">
                                <div class="avatar-initials me-2 rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm"
                                    style="width: 28px; height: 28px; font-size: 0.75rem; background-color: {{ $col }};">
                                    {{ strtoupper(substr($member->name, 0, 2)) }}
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
                    <input type="hidden" name="staff_id" value="{{ $currentStaffId ?? '' }}">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-sm-6 col-md-auto">
                            <label class="small text-muted fw-bold d-block mb-1">Quick Range</label>
                            <select name="range" class="form-select form-select-sm" id="filter-range"
                                onchange="this.form.submit()">
                                <option value="today" {{ ($selectedRange ?? '') == 'today' ? 'selected' : '' }}>Today</option>
                                <option value="tomorrow" {{ ($selectedRange ?? '') == 'tomorrow' ? 'selected' : '' }}>Tomorrow
                                </option>
                                <option value="this_week" {{ ($selectedRange ?? '') == 'this_week' ? 'selected' : '' }}>This
                                    Week</option>
                                <option value="this_month" {{ ($selectedRange ?? '') == 'this_month' ? 'selected' : '' }}>This
                                    Month</option>
                                <option value="this_year" {{ ($selectedRange ?? '') == 'this_year' ? 'selected' : '' }}>This
                                    Year</option>
                                <option value="custom" {{ ($selectedRange ?? '') == 'custom' ? 'selected' : '' }}>Custom
                                </option>
                            </select>
                        </div>

                        <div class="col-6 col-md-auto {{ ($selectedRange ?? '') == 'custom' ? '' : 'd-none' }}"
                            id="custom-date-from">
                            <label class="small text-muted fw-bold d-block mb-1">From Date</label>
                            <input type="date" name="from_date" class="form-control form-control-sm"
                                value="{{ $fromDate ?? '' }}" onchange="this.form.submit()">
                        </div>

                        <div class="col-6 col-md-auto {{ ($selectedRange ?? '') == 'custom' ? '' : 'd-none' }}"
                            id="custom-date-to">
                            <label class="small text-muted fw-bold d-block mb-1">To Date</label>
                            <input type="date" name="to_date" class="form-control form-control-sm"
                                value="{{ $toDate ?? '' }}" onchange="this.form.submit()">
                        </div>

                        <div class="col-12 col-sm-6 col-md-auto">
                            <label class="small text-muted fw-bold d-block mb-1">Location</label>
                            <select name="location_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All Locations</option>
                                @foreach($locations ?? [] as $loc)
                                    <option value="{{ $loc->id }}" {{ ($locationId ?? '') == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-md-auto">
                            <label class="small text-muted fw-bold d-block mb-1">Status</label>
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="working" {{ ($statusFilter ?? '') == 'working' ? 'selected' : '' }}>Working
                                </option>
                                <option value="off" {{ ($statusFilter ?? '') == 'off' ? 'selected' : '' }}>Off / Unavailable
                                </option>
                            </select>
                        </div>

                        @if(($selectedRange ?? 'this_week') !== 'this_week' || !empty($fromDate) || !empty($toDate) || !empty($locationId) || !empty($statusFilter))
                            <div class="col-12 col-sm-6 col-md-auto">
                                <label class="small text-muted fw-bold d-block mb-1">&nbsp;</label>
                                <a href="{{ route('schedule.index', array_filter(['staff_id' => $currentStaffId ?? null])) }}"
                                    class="btn btn-outline-secondary btn-sm">
                                    <i class="bx bx-x me-1"></i>Reset
                                </a>
                            </div>
                        @endif

                        <div class="col-12 col-md-auto ms-auto text-end">
                            <a href="{{ route('schedule.create', array_filter(['staff_id' => ($currentStaffId !== 'all' ? ($currentStaffId ?? null) : null)])) }}"
                                class="btn btn-primary btn-sm px-4 fw-semibold rounded-pill">+ New Schedule</a>
                        </div>
                    </div>
                </form>
            </nav>

            <!-- Calendar Toolbar -->
            <div class="px-4 py-3 d-flex align-items-center justify-content-between border-bottom">
                <div class="view-toggle btn-group btn-group-sm">
                    <a href="{{ route('schedule.index', array_merge(request()->all(), ['range' => 'today', 'staff_id' => $currentStaffId ?? ''])) }}"
                        class="btn {{ in_array(($selectedRange ?? ''), ['today', 'day']) ? 'active' : '' }}">Day</a>
                    <a href="{{ route('schedule.index', array_merge(request()->all(), ['range' => 'this_week', 'staff_id' => $currentStaffId ?? ''])) }}"
                        class="btn {{ !in_array(($selectedRange ?? ''), ['today', 'day', 'this_month', 'month']) ? 'active' : '' }}">Week</a>
                    <a href="{{ route('schedule.index', array_merge(request()->all(), ['range' => 'this_month', 'staff_id' => $currentStaffId ?? ''])) }}"
                        class="btn {{ in_array(($selectedRange ?? ''), ['this_month', 'month']) ? 'active' : '' }}">Month</a>
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
    <div class="px-4 py-4 schedule-entries-section">
        <div class="schedule-entries-card card">
            <div class="card-header py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div
                        class="schedule-entries-header-icon rounded-circle d-flex align-items-center justify-content-center">
                        <i class="bx bx-calendar-check fs-5"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="fw-bold text-dark mb-0">Schedule Entries</h5>
                            <span class="badge schedule-count-badge" id="schedule-entries-count">{{ count($schedules) }}
                                total</span>
                        </div>
                        <p class="text-muted small mb-0">All schedules for the selected staff and date range.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="schedule-table-search position-relative">
                        <i class="bx bx-search position-absolute top-50 start-0 translate-middle-y ms-2.5 text-muted"
                            style="left: 12px;"></i>
                        <input type="text" id="schedule-table-search-input"
                            class="form-control form-control-sm schedule-search-input"
                            placeholder="Search staff, category, type..." autocomplete="off">
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table schedule-table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="ps-4" style="min-width: 230px;">Staff</th>
                            <th style="min-width: 100px;">Type</th>
                            <th style="min-width: 140px;">Recurrence</th>
                            <th style="min-width: 130px;">Working Date</th>
                            <th style="min-width: 90px;">Start Time</th>
                            <th style="min-width: 90px;">End Time</th>
                            <th style="min-width: 120px;">From Date</th>
                            <th style="min-width: 120px;">To Date</th>
                            <th class="text-center" style="min-width: 100px;">Status</th>
                            <th class="text-end pe-4" style="min-width: 160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schedules as $entry)
                            <tr class="schedule-entry-row" data-staff-id="{{ $entry['staff_id'] }}"
                                data-search-keywords="{{ strtolower(($entry['staff']['name'] ?? '') . ' ' . (!empty($entry['staff']['categories']) ? collect($entry['staff']['categories'])->pluck('name')->join(' ') : ($entry['staff']['category'] ?? '')) . ' ' . ($entry['summary']['type'] ?? $entry['recurrence_type'] ?? '') . ' ' . ($entry['summary']['recurrence'] ?? '') . ' ' . ($entry['is_working'] ? 'working' : 'off')) }}">
                                <td class="ps-4" style="min-width: 230px;">
                                    <div class="d-flex align-items-center gap-3">
                                        @php
                                            $staffColor = $entry['staff_color'] ?? ($staffColors[$entry['staff_id']] ?? '#2563eb');
                                            $staffName = $entry['staff']['name'] ?? '—';

                                            $hex = ltrim($staffColor, '#');
                                            if (strlen($hex) === 3) {
                                                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
                                            }
                                            $avatarTextColor = '#ffffff';
                                            if (strlen($hex) === 6 && ctype_xdigit($hex)) {
                                                $r = hexdec(substr($hex, 0, 2));
                                                $g = hexdec(substr($hex, 2, 2));
                                                $b = hexdec(substr($hex, 4, 2));
                                                $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
                                                $avatarTextColor = ($yiq >= 160) ? '#181c32' : '#ffffff';
                                            }

                                            $nameParts = array_filter(preg_split('/\s+/u', trim($staffName)));
                                            if (count($nameParts) >= 2) {
                                                $first = mb_substr(array_shift($nameParts), 0, 1, 'UTF-8');
                                                $second = mb_substr(array_shift($nameParts), 0, 1, 'UTF-8');
                                                $staffInitials = mb_strtoupper($first . $second, 'UTF-8');
                                            } elseif (count($nameParts) === 1) {
                                                $staffInitials = mb_strtoupper(mb_substr(reset($nameParts), 0, 2, 'UTF-8'), 'UTF-8');
                                            } else {
                                                $staffInitials = '—';
                                            }

                                            $entryCategories = !empty($entry['staff']['categories'])
                                                ? collect($entry['staff']['categories'])->pluck('name')->join(', ')
                                                : ($entry['staff']['category'] ?? '');

                                            $entryOccurrenceDate = !empty($entry['working_date'])
                                                ? \Carbon\Carbon::parse($entry['working_date'])->format('d M Y')
                                                : (!empty($entry['start_date']) ? \Carbon\Carbon::parse($entry['start_date'])->format('d M Y') : '—');

                                            $entryGroupPeriod = !empty($entry['start_date'])
                                                ? \Carbon\Carbon::parse($entry['start_date'])->format('d M Y') . (!empty($entry['end_date']) ? ' – ' . \Carbon\Carbon::parse($entry['end_date'])->format('d M Y') : '')
                                                : '—';

                                            $rawType = $entry['recurrence_type'] ?? 'one_time';
                                            $typeLabel = $entry['summary']['type'] ?? ucfirst($rawType);
                                            $badgeClass = match ($rawType) {
                                                'weekly' => 'type-badge-weekly',
                                                'monthly' => 'type-badge-monthly',
                                                'yearly' => 'type-badge-yearly',
                                                default => 'type-badge-onetime',
                                            };
                                        @endphp
                                        <div class="avatar-initials rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0 shadow-sm"
                                            style="width: 36px; height: 36px; font-size: 0.8rem; background-color: {{ $staffColor }}; color: {{ $avatarTextColor }};"
                                            title="{{ $staffName }}">
                                            {{ $staffInitials }}
                                        </div>
                                        <div class="min-w-0 flex-grow-1">
                                            <div class="fw-semibold text-truncate staff-row-name" style="max-width: 180px;"
                                                title="{{ $staffName }}">{{ $staffName }}</div>
                                            @if(!empty($entryCategories))
                                                <div class="small text-truncate staff-row-cat" style="max-width: 180px;"
                                                    title="{{ $entryCategories }}">{{ $entryCategories }}</div>
                                            @else
                                                <div class="small text-muted opacity-50">—</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge schedule-type-badge {{ $badgeClass }}">{{ $typeLabel }}</span>
                                </td>
                                <td>
                                    <span
                                        class="text-secondary small fw-medium">{{ $entry['summary']['recurrence'] ?? '—' }}</span>
                                </td>
                                <td>
                                    @if(!empty($entry['working_date']))
                                        <span class="d-inline-flex align-items-center text-dark small fw-medium">
                                            <i class="bx bx-calendar text-muted me-1.5 fs-6"></i>
                                            {{ \Carbon\Carbon::parse($entry['working_date'])->format('d M Y') }}
                                        </span>
                                    @else
                                        <span class="text-muted opacity-50">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($entry['start_time']))
                                        <span
                                            class="badge schedule-time-pill">{{ substr((string) $entry['start_time'], 0, 5) }}</span>
                                    @else
                                        <span class="text-muted opacity-50">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($entry['end_time']))
                                        <span
                                            class="badge schedule-time-pill">{{ substr((string) $entry['end_time'], 0, 5) }}</span>
                                    @else
                                        <span class="text-muted opacity-50">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($entry['start_date']))
                                        <span
                                            class="text-secondary small">{{ \Carbon\Carbon::parse($entry['start_date'])->format('d M Y') }}</span>
                                    @else
                                        <span class="text-muted opacity-50">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($entry['end_date']))
                                        <span
                                            class="text-secondary small">{{ \Carbon\Carbon::parse($entry['end_date'])->format('d M Y') }}</span>
                                    @else
                                        <span class="text-muted opacity-50">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($entry['is_working'])
                                        <span class="badge bg-success text-white badge-status-pill">
                                            <span class="status-dot-working"></span>Working
                                        </span>
                                    @else
                                        <span class="badge bg-secondary text-white badge-status-pill">
                                            <span class="status-dot-off"></span>Off
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end align-items-center gap-2">
                                        @if(($entry['recurrence_type'] ?? 'one_time') !== 'one_time' || !empty($entry['recurrence_group_id']))
                                            <!-- Custom Edit Dropdown -->
                                            <div class="custom-dropdown d-inline-block">
                                                <button class="btn btn-sm btn-white-border custom-dropdown-trigger" type="button"
                                                    aria-haspopup="true" aria-expanded="false">
                                                    <span>Edit</span>
                                                    <i class="bx bx-chevron-down text-muted"></i>
                                                </button>
                                                <div class="custom-dropdown-menu">
                                                    <a class="custom-dropdown-item custom-dropdown-item-primary"
                                                        href="{{ route('schedule.edit', [$entry['id'], 'scope' => 'occurrence']) }}">
                                                        <i class="bx bx-calendar-event text-primary"></i>
                                                        <span>Edit this occurrence only</span>
                                                    </a>
                                                    <a class="custom-dropdown-item custom-dropdown-item-primary"
                                                        href="{{ route('schedule.edit', [$entry['id'], 'scope' => 'group']) }}">
                                                        <i class="bx bx-repeat text-primary"></i>
                                                        <span>Edit entire recurring schedule</span>
                                                    </a>
                                                </div>
                                            </div>

                                            <!-- Custom Delete Dropdown -->
                                            <div class="custom-dropdown d-inline-block">
                                                <button class="btn btn-sm btn-outline-danger-subtle custom-dropdown-trigger"
                                                    type="button" aria-haspopup="true" aria-expanded="false">
                                                    <span>Delete</span>
                                                    <i class="bx bx-chevron-down"></i>
                                                </button>
                                                <div class="custom-dropdown-menu">
                                                    <form action="{{ route('schedule.destroy', $entry['id']) }}" method="POST"
                                                        data-confirm="Are you sure you want to delete this schedule occurrence?"
                                                        data-confirm-title="Confirm Schedule Deletion"
                                                        data-confirm-subtitle="This action cannot be undone."
                                                        data-confirm-record="Staff: {{ $staffName }} | Date: {{ $entryOccurrenceDate }}"
                                                        data-confirm-text="Yes, Delete" data-confirm-class="btn-danger"
                                                        data-confirm-type="danger" data-confirm-loading="Deleting...">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="scope" value="occurrence">
                                                        <button type="submit"
                                                            class="custom-dropdown-item custom-dropdown-item-danger">
                                                            <i class="bx bx-calendar-x text-danger"></i>
                                                            <span>Delete this occurrence</span>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('schedule.destroy', $entry['id']) }}" method="POST"
                                                        data-confirm="Are you sure you want to skip this schedule occurrence? Staff will be unavailable on this date."
                                                        data-confirm-title="Confirm Schedule Skip"
                                                        data-confirm-subtitle="This will add an exception for this date."
                                                        data-confirm-record="Staff: {{ $staffName }} | Date: {{ $entryOccurrenceDate }}"
                                                        data-confirm-text="Yes, Skip" data-confirm-class="btn-warning"
                                                        data-confirm-type="warning" data-confirm-loading="Skipping...">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="scope" value="skip">
                                                        <button type="submit"
                                                            class="custom-dropdown-item custom-dropdown-item-warning">
                                                            <i class="bx bx-block text-warning"></i>
                                                            <span>Skip this occurrence</span>
                                                        </button>
                                                    </form>
                                                    <div class="custom-dropdown-divider"></div>
                                                    <form action="{{ route('schedule.destroy', $entry['id']) }}" method="POST"
                                                        data-confirm="Are you sure you want to delete the entire recurring schedule? All occurrences in this recurring group will be affected."
                                                        data-confirm-title="Confirm Schedule Deletion"
                                                        data-confirm-subtitle="This action cannot be undone."
                                                        data-confirm-record="Staff: {{ $staffName }} | Period: {{ $entryGroupPeriod }}"
                                                        data-confirm-text="Yes, Delete Entire Schedule"
                                                        data-confirm-class="btn-danger" data-confirm-type="danger"
                                                        data-confirm-loading="Deleting...">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="scope" value="group">
                                                        <button type="submit"
                                                            class="custom-dropdown-item custom-dropdown-item-danger fw-semibold">
                                                            <i class="bx bx-trash text-danger"></i>
                                                            <span>Delete entire schedule</span>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        @else
                                            <a href="{{ route('schedule.edit', $entry['id']) }}"
                                                class="btn btn-sm btn-white-border">Edit</a>
                                            <form action="{{ route('schedule.destroy', $entry['id']) }}" method="POST"
                                                data-confirm="Are you sure you want to delete this schedule?"
                                                data-confirm-title="Confirm Schedule Deletion"
                                                data-confirm-subtitle="This action cannot be undone."
                                                data-confirm-record="Staff: {{ $staffName }} | Date: {{ $entryOccurrenceDate }}"
                                                data-confirm-text="Yes, Delete" data-confirm-class="btn-danger"
                                                data-confirm-type="danger" data-confirm-loading="Deleting...">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger-subtle">Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="no-schedules-server-row">
                                <td colspan="10" class="text-center py-5">
                                    <div class="py-4">
                                        <div class="empty-state-icon mb-3 mx-auto rounded-circle d-flex align-items-center justify-content-center"
                                            style="width: 54px; height: 54px; background-color: #f1f5f9; color: #94a3b8;">
                                            <i class="bx bx-calendar-x fs-2"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-1">No schedules found</h6>
                                        <p class="text-muted small mb-0">No schedules match the selected filters or date range.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        <tr id="no-staff-schedules-row" style="display: none;">
                            <td colspan="10" class="text-center py-5">
                                <div class="py-4">
                                    <div class="empty-state-icon mb-3 mx-auto rounded-circle d-flex align-items-center justify-content-center"
                                        style="width: 54px; height: 54px; background-color: #f1f5f9; color: #94a3b8;">
                                        <i class="bx bx-search fs-2"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">No matching schedules</h6>
                                    <p class="text-muted small mb-0">No schedules match the current search or staff filter.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Business Holidays -->
    <div class="px-4 py-4 bg-light">
        <div class="card border-0 shadow-sm rounded">
            <div class="card-body">
                <h6 class="fw-bold mb-1"><i class="bx bx-calendar-x me-1 text-danger"></i>Business Holidays (Closed Dates)
                </h6>
                <p class="text-muted small mb-3">On these dates the clinic is closed: no staff is available and no
                    appointments can be booked.</p>
                <form action="{{ route('schedule.holidays.store') }}" method="POST"
                    class="d-flex flex-wrap align-items-center gap-2 mb-3" novalidate>
                    @csrf
                    <input type="date" name="date" class="form-control form-control-sm w-auto" min="{{ date('Y-m-d') }}"
                        required>
                    <button type="submit" class="btn btn-sm btn-primary">Add Holiday</button>
                </form>
                @forelse($holidays as $holiday)
                    <span class="badge bg-light border text-dark fs-6 fw-normal me-2 mb-2 px-3 py-2">
                        {{ \Carbon\Carbon::parse($holiday)->format('D, d M Y') }}
                        <form action="{{ route('schedule.holidays.destroy', $holiday) }}" method="POST" class="d-inline"
                            data-confirm="Are you sure you want to remove this holiday?" data-confirm-title="Remove Holiday?"
                            data-confirm-subtitle="Staff availability will be restored for this date."
                            data-confirm-record="{{ \Carbon\Carbon::parse($holiday)->format('D, d M Y') }}"
                            data-confirm-text="Remove" data-confirm-class="btn-danger" data-confirm-type="danger"
                            data-confirm-loading="Removing...">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-link text-danger p-0 ms-1"><i
                                    class="bx bx-x"></i></button>
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
            <form class="modal-content" id="create-schedule-form" action="{{ route('schedule.store') }}" method="POST"
                novalidate>
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
                            <label class="form-label small text-muted">Recurrence Type <span
                                    class="required-mark">*</span></label>
                            <select class="form-select" id="cs-recurrence-type" name="recurrence_type" required>
                                <option value="one_time">One Time</option>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small text-muted" for="cs-start-time">Start Time <span
                                    class="required-mark">*</span></label>
                            <input type="time" class="form-control" id="cs-start-time" name="start_time" required>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small text-muted" for="cs-end-time">End Time <span
                                    class="required-mark">*</span></label>
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
                                <input class="form-check-input" type="checkbox" role="switch" id="cs-day-off"
                                    name="is_working" value="0">
                                <label class="form-check-label small" for="cs-day-off">Day Off — staff is <strong>not
                                        available</strong> on this date</label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="row g-3">
                        <!-- One Time Working Date -->
                        <div class="col-12 col-md-6 recurrence-panel" id="panel-one_time">
                            <label class="form-label small text-muted" for="cs-working-date">Date <span
                                    class="required-mark">*</span></label>
                            <input type="date" class="form-control" name="working_date" id="cs-working-date"
                                min="{{ date('Y-m-d') }}" required>
                            <div class="form-text">Schedule applies only to this single date.</div>
                        </div>

                        <!-- Date Range for Recurring -->
                        <div class="col-12 col-md-6 recurrence-panel d-none" id="panel-date-range">
                            <label class="form-label small text-muted">Start Date <span
                                    class="required-mark">*</span></label>
                            <input type="date" class="form-control" name="start_date" id="cs-start-date"
                                min="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-12 col-md-6 recurrence-panel d-none" id="panel-date-range-end">
                            <label class="form-label small text-muted">End Date <span class="required-mark">*</span></label>
                            <input type="date" class="form-control" name="end_date" id="cs-end-date"
                                min="{{ date('Y-m-d') }}">
                        </div>

                        <!-- Weekly Days Selection -->
                        <div class="col-12 recurrence-panel d-none" id="panel-weekly">
                            <label class="form-label small text-muted d-block mb-2">Select Weekdays <span
                                    class="required-mark">*</span></label>
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
                                        <input type="checkbox" class="cs-weekly-day" name="weekly_days[]" id="day-{{ $val }}"
                                            value="{{ $val }}">
                                        <label for="day-{{ $val }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Monthly Day Selection -->
                        <div class="col-12 col-md-6 recurrence-panel d-none" id="panel-monthly">
                            <label class="form-label small text-muted">Day of Month <span
                                    class="required-mark">*</span></label>
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
                                        1 => 'January',
                                        2 => 'February',
                                        3 => 'March',
                                        4 => 'April',
                                        5 => 'May',
                                        6 => 'June',
                                        7 => 'July',
                                        8 => 'August',
                                        9 => 'September',
                                        10 => 'October',
                                        11 => 'November',
                                        12 => 'December'
                                    ];
                                @endphp
                                @foreach($months as $mVal => $mName)
                                    <option value="{{ $mVal }}">{{ $mName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-3 recurrence-panel d-none" id="panel-yearly-day">
                            <label class="form-label small text-muted">Day of Month <span
                                    class="required-mark">*</span></label>
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
            const currentStaffId = @json($currentStaffId);
            const currentStaffColor = @json($currentStaffColor ?? ($currentStaff ? \App\Http\Controllers\CalendarController::resolveStaffColor($currentStaff) : null));
            const isAllStaff = @json($isAllStaff ?? false);
            const currentStaffLocationId = {{ isset($currentStaff) && $currentStaff->location_id ? $currentStaff->location_id : 'null' }};
            const currentStaffName = @json($currentStaff->name ?? 'Staff');
            const firstStaffId = {{ isset($staff) && $staff->isNotEmpty() ? $staff->first()->id : 'null' }};
            window.FIRST_STAFF_ID = firstStaffId;

            let activeStaffFilter = isAllStaff ? 'all' : (currentStaffId || (firstStaffId ? String(firstStaffId) : 'all'));

            window.selectStaffFilter = function (staffId) {
                activeStaffFilter = staffId;
                const staffIdStr = String(staffId);

                // Update sidebar .active class
                document.querySelectorAll('.provider-list .provider-item').forEach(el => {
                    if (el.dataset.staffId === staffIdStr) {
                        el.classList.add('active');
                    } else {
                        el.classList.remove('active');
                    }
                });

                // Update hidden form inputs and create schedule links
                const staffInput = document.querySelector('input[name="staff_id"]');
                if (staffInput) staffInput.value = staffIdStr;

                const newScheduleBtn = document.querySelector('a[href*="schedule/create"]');
                if (newScheduleBtn) {
                    const targetStaffId = staffIdStr !== 'all' ? staffIdStr : '';
                    newScheduleBtn.href = '{{ route("schedule.create") }}' + (targetStaffId ? `?staff_id=${targetStaffId}` : '');
                }

                // Update Day/Week/Month toggle links so clicking them preserves staff_id
                document.querySelectorAll('.view-toggle a').forEach(a => {
                    try {
                        const url = new URL(a.href, window.location.origin);
                        url.searchParams.set('staff_id', staffIdStr);
                        a.href = url.pathname + url.search;
                    } catch (e) { }
                });

                // Update browser URL query string without reloading page
                try {
                    const url = new URL(window.location.href);
                    url.searchParams.set('staff_id', staffIdStr);
                    window.history.pushState({ staff_id: staffIdStr }, '', url.toString());
                } catch (e) { }

                // Filter table rows to match active staff
                filterScheduleTableRows();

                // Re-render blocks with new filter
                renderScheduleBlocks();
            };

            function filterScheduleTableRows() {
                const staffFilter = String(activeStaffFilter || 'all');
                const searchInput = document.getElementById('schedule-table-search-input');
                const searchQuery = searchInput ? searchInput.value.toLowerCase().trim() : '';
                let visibleCount = 0;

                document.querySelectorAll('.schedule-entry-row').forEach(row => {
                    const matchesStaff = (staffFilter === 'all' || row.dataset.staffId === staffFilter);
                    const keywords = (row.dataset.searchKeywords || row.textContent).toLowerCase();
                    const matchesSearch = !searchQuery || keywords.includes(searchQuery);

                    if (matchesStaff && matchesSearch) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                const countBadge = document.getElementById('schedule-entries-count');
                if (countBadge) {
                    const totalRows = document.querySelectorAll('.schedule-entry-row').length;
                    if (searchQuery || staffFilter !== 'all') {
                        countBadge.textContent = `${visibleCount} of ${totalRows}`;
                    } else {
                        countBadge.textContent = `${totalRows} total`;
                    }
                }

                const emptyRow = document.getElementById('no-staff-schedules-row');
                if (emptyRow) {
                    emptyRow.style.display = (visibleCount === 0 && document.querySelectorAll('.schedule-entry-row').length > 0) ? '' : 'none';
                }
            }

            const tableSearchInput = document.getElementById('schedule-table-search-input');
            if (tableSearchInput) {
                tableSearchInput.addEventListener('input', filterScheduleTableRows);
            }

            const currentStaffCategory = @json(
                $currentStaff && $currentStaff->categories->isNotEmpty()
                ? $currentStaff->categories->pluck('name')->join(', ')
                : ($currentStaff->category ?? '')
            );
            const schedules = @json($schedules ?? []);
            const staffColors = @json($staffColors ?? []);
            window.STAFF_COLORS = staffColors;
            window.CURRENT_STAFF_COLOR = currentStaffColor;
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

                if (!activeStaffFilter && !currentStaffId) {
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

            function getContrastColor(hexColor) {
                if (!hexColor || typeof hexColor !== 'string') return '#ffffff';
                let hex = hexColor.trim().replace('#', '');
                if (hex.length === 3) {
                    hex = hex.split('').map(c => c + c).join('');
                }
                if (hex.length !== 6) return '#ffffff';
                const r = parseInt(hex.substring(0, 2), 16);
                const g = parseInt(hex.substring(2, 4), 16);
                const b = parseInt(hex.substring(4, 6), 16);
                const yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
                return (yiq >= 150) ? '#181c32' : '#ffffff';
            }

            function escapeHtml(str) {
                if (!str) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function renderScheduleBlocks() {
                // Clear old blocks
                document.querySelectorAll('.schedule-block').forEach(b => b.remove());

                if (!schedules || schedules.length === 0) return;

                const weekStart = getCurrentWeekStart();

                // Group valid working schedules by day column (0..6)
                const dayItemsMap = new Map();

                schedules.forEach(sch => {
                    if (!sch.is_working) return;
                    if (activeStaffFilter !== 'all' && String(sch.staff_id) !== String(activeStaffFilter)) {
                        return;
                    }

                    let dayOfWeek = null;
                    if (sch.working_date) {
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

                    if (dayOfWeek === null || dayOfWeek === undefined || isNaN(dayOfWeek) || dayOfWeek < 0 || dayOfWeek > 6) return;

                    const [startH, startM] = (sch.start_time || '00:00').split(':').map(Number);
                    const [endH, endM] = (sch.end_time || '00:00').split(':').map(Number);

                    const startMinutes = (startH || 0) * 60 + (startM || 0);
                    let endMinutes = (endH || 0) * 60 + (endM || 0);
                    if (endMinutes <= startMinutes) {
                        endMinutes = Math.min(startMinutes + 60, 24 * 60);
                    }
                    const durationMinutes = Math.max(endMinutes - startMinutes, 15);

                    if (!dayItemsMap.has(dayOfWeek)) {
                        dayItemsMap.set(dayOfWeek, []);
                    }

                    dayItemsMap.get(dayOfWeek).push({
                        sch,
                        dayOfWeek,
                        startMinutes,
                        endMinutes,
                        durationMinutes,
                    });
                });

                const slotHeight = 50;
                const minutesPerSlot = 60;

                // Process each day's schedules with track clustering
                dayItemsMap.forEach((items, dayOfWeek) => {
                    const dayCol = document.querySelector(`.day-col[data-day-index="${dayOfWeek}"]`);
                    if (!dayCol) return;

                    // 1. Sort: start time ascending, duration descending
                    items.sort((a, b) => {
                        if (a.startMinutes !== b.startMinutes) return a.startMinutes - b.startMinutes;
                        if (b.durationMinutes !== a.durationMinutes) return b.durationMinutes - a.durationMinutes;
                        return (a.sch.id || 0) - (b.sch.id || 0);
                    });

                    // 2. Cluster overlapping shifts
                    const clusters = [];
                    let currentCluster = [];
                    let clusterEnd = -1;

                    items.forEach(item => {
                        if (currentCluster.length === 0) {
                            currentCluster.push(item);
                            clusterEnd = item.endMinutes;
                        } else if (item.startMinutes < clusterEnd) {
                            currentCluster.push(item);
                            if (item.endMinutes > clusterEnd) clusterEnd = item.endMinutes;
                        } else {
                            clusters.push(currentCluster);
                            currentCluster = [item];
                            clusterEnd = item.endMinutes;
                        }
                    });
                    if (currentCluster.length > 0) clusters.push(currentCluster);

                    // 3. Assign sub-column tracks within each cluster
                    clusters.forEach(cluster => {
                        if (cluster.length === 1) {
                            cluster[0].trackIndex = 0;
                            cluster[0].totalTracks = 1;
                            return;
                        }

                        const tracks = []; // holds end time of active item in each track
                        cluster.forEach(item => {
                            let assignedTrack = -1;
                            for (let t = 0; t < tracks.length; t++) {
                                if (tracks[t] <= item.startMinutes) {
                                    assignedTrack = t;
                                    tracks[t] = item.endMinutes;
                                    break;
                                }
                            }
                            if (assignedTrack === -1) {
                                assignedTrack = tracks.length;
                                tracks.push(item.endMinutes);
                            }
                            item.trackIndex = assignedTrack;
                        });

                        const totalTracks = tracks.length;
                        cluster.forEach(item => {
                            item.totalTracks = totalTracks;
                        });
                    });

                    // 4. Render blocks with precise positioning, colors, and separation
                    items.forEach(item => {
                        const { sch, startMinutes, durationMinutes, trackIndex, totalTracks } = item;
                        const topOffset = (startMinutes / minutesPerSlot) * slotHeight;
                        const blockHeight = (durationMinutes / minutesPerSlot) * slotHeight;

                        const block = document.createElement('div');
                        block.className = `schedule-block ${isWeekend(dayOfWeek) ? 'weekend' : ''}`;
                        block.dataset.scheduleId = sch.id;

                        // Horizontal sub-column track assignment
                        if (totalTracks > 1) {
                            const trackWidthPct = 100 / totalTracks;
                            block.style.left = `calc(${trackIndex * trackWidthPct}% + 2px)`;
                            block.style.width = `calc(${trackWidthPct}% - 4px)`;
                            block.style.right = 'auto';
                        } else {
                            block.style.left = '4px';
                            block.style.width = 'calc(100% - 8px)';
                            block.style.right = 'auto';
                        }

                        // Vertical positioning with 1px top margin and 2px height gap so adjacent shifts clearly separate
                        block.style.top = (topOffset + 1) + 'px';
                        block.style.height = Math.max(blockHeight - 2, 18) + 'px';

                        // Staff color resolution
                        const targetStaffId = sch.staff_id || (activeStaffFilter !== 'all' ? activeStaffFilter : currentStaffId);
                        const staffColor = sch.staff_color
                            || sch.color
                            || sch.staffColor
                            || (window.STAFF_COLORS && targetStaffId && window.STAFF_COLORS[targetStaffId])
                            || (currentStaffColor && (activeStaffFilter !== 'all' || String(targetStaffId) === String(currentStaffId)) ? currentStaffColor : null)
                            || (window.CURRENT_STAFF_COLOR && activeStaffFilter !== 'all' ? window.CURRENT_STAFF_COLOR : null)
                            || '#2563eb';
                        const textColor = getContrastColor(staffColor);

                        block.style.setProperty('background', staffColor, 'important');
                        block.style.setProperty('background-color', staffColor, 'important');
                        block.style.setProperty('border-color', staffColor, 'important');
                        block.style.setProperty('color', textColor, 'important');

                        const staffName = (sch.staff && sch.staff.name) ? sch.staff.name : (currentStaffName || 'Staff');
                        const timeText = `${sch.start_time.slice(0, 5)} – ${sch.end_time.slice(0, 5)}`;
                        const recurrenceDesc = sch.summary && sch.summary.recurrence ? ` • ${sch.summary.recurrence}` : '';

                        block.title = `${staffName} • ${timeText}${recurrenceDesc}`;

                        if (totalTracks >= 3 || blockHeight < 28) {
                            block.innerHTML = `
                                        <div class="fw-bold text-truncate w-100" style="font-size: 0.65rem; line-height: 1.1;">${escapeHtml(staffName)}</div>
                                        <div class="text-truncate w-100 opacity-90" style="font-size: 0.6rem; line-height: 1;">${timeText}</div>
                                    `;
                        } else {
                            block.innerHTML = `
                                        <div class="fw-bold text-truncate w-100" style="font-size: 0.72rem; line-height: 1.2;">${escapeHtml(staffName)}</div>
                                        <div class="text-truncate w-100 opacity-90" style="font-size: 0.65rem; line-height: 1.1;">${timeText}</div>
                                    `;
                        }

                        block.addEventListener('click', (e) => {
                            e.stopPropagation();
                            editSchedule(sch.id);
                        });

                        dayCol.appendChild(block);
                    });
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
                const effectiveStaffId = (activeStaffFilter && activeStaffFilter !== 'all') ? activeStaffFilter : (currentStaffId || firstStaffId || null);
                if (!effectiveStaffId) {
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
                            if (csStaffId) csStaffId.value = String(effectiveStaffId);
                            if (csWorkingDate) csWorkingDate.value = workingDate;
                            if (csStartTime) csStartTime.value = startTime;
                            if (csEndTime) csEndTime.value = endTime;
                            createSchedule();
                        }
                    });
                    return;
                }

                if (csStaffId) csStaffId.value = String(effectiveStaffId);
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
                formData.append('location_id', currentStaffLocationId || '');
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
                        if (res.status === 422) {
                            return res.json().then(data => {
                                if (btn) { btn.disabled = false; btn.textContent = originalText; }
                                if (window.AppFormErrors && data.errors) {
                                    window.AppFormErrors.show(createForm, data.errors);
                                }
                                return null;
                            });
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
            filterScheduleTableRows();

            if (createForm) {
                window.AppFormErrors?.attachAutoClear(createForm);
                createForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    if (window.AppFormErrors && !window.AppFormErrors.validate(createForm)) {
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
                const qs = new URLSearchParams(window.location.search);
                if (activeStaffFilter) {
                    qs.set('staff_id', String(activeStaffFilter));
                }
                qs.delete('week_date');
                window.location.href = '{{ route("schedule.index") }}' + (qs.toString() ? `?${qs.toString()}` : '');
            });

            document.getElementById('btn-prev-week').addEventListener('click', function () {
                const ws = getCurrentWeekStart();
                const prev = new Date(ws);
                prev.setDate(prev.getDate() - 7);
                const weekDate = `${prev.getFullYear()}-${String(prev.getMonth() + 1).padStart(2, '0')}-${String(prev.getDate()).padStart(2, '0')}`;
                const qs = new URLSearchParams(window.location.search);
                if (activeStaffFilter) qs.set('staff_id', String(activeStaffFilter));
                qs.set('week_date', weekDate);
                window.location.href = '{{ route("schedule.index") }}' + `?${qs.toString()}`;
            });

            document.getElementById('btn-next-week').addEventListener('click', function () {
                const ws = getCurrentWeekStart();
                const next = new Date(ws);
                next.setDate(next.getDate() + 7);
                const weekDate = `${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, '0')}-${String(next.getDate()).padStart(2, '0')}`;
                const qs = new URLSearchParams(window.location.search);
                if (activeStaffFilter) qs.set('staff_id', String(activeStaffFilter));
                qs.set('week_date', weekDate);
                window.location.href = '{{ route("schedule.index") }}' + `?${qs.toString()}`;
            });

            // Custom Dropdowns for Schedule Entries
            function initCustomDropdowns() {
                let activeDropdown = null;

                function closeActive() {
                    if (!activeDropdown) return;
                    const trigger = activeDropdown.querySelector('.custom-dropdown-trigger');
                    if (trigger) trigger.setAttribute('aria-expanded', 'false');
                    activeDropdown.classList.remove('show', 'dropup');
                    activeDropdown = null;
                }

                function openDropdown(dropdown, trigger) {
                    if (activeDropdown && activeDropdown !== dropdown) {
                        closeActive();
                    }

                    const menu = dropdown.querySelector('.custom-dropdown-menu');
                    if (!menu) return;

                    const triggerRect = trigger.getBoundingClientRect();
                    const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
                    const spaceBelow = viewportHeight - triggerRect.bottom;
                    const spaceAbove = triggerRect.top;

                    const menuHeight = menu.offsetHeight || 160;

                    // Check space inside table scroll container if present
                    const tableContainer = dropdown.closest('.table-responsive');
                    let containerSpaceBelow = spaceBelow;
                    if (tableContainer) {
                        const containerRect = tableContainer.getBoundingClientRect();
                        containerSpaceBelow = Math.min(spaceBelow, containerRect.bottom - triggerRect.bottom);
                    }

                    // Choose upward (dropup) placement if space below is limited and space above is greater
                    if ((spaceBelow < menuHeight + 15 || containerSpaceBelow < menuHeight + 15) && spaceAbove > menuHeight) {
                        dropdown.classList.add('dropup');
                    } else {
                        dropdown.classList.remove('dropup');
                    }

                    dropdown.classList.add('show');
                    trigger.setAttribute('aria-expanded', 'true');
                    activeDropdown = dropdown;
                }

                // Delegated click on trigger
                document.addEventListener('click', function (e) {
                    const trigger = e.target.closest('.custom-dropdown-trigger');
                    if (trigger) {
                        e.preventDefault();
                        e.stopPropagation();
                        const dropdown = trigger.closest('.custom-dropdown');
                        if (!dropdown) return;

                        if (dropdown === activeDropdown) {
                            closeActive();
                        } else {
                            openDropdown(dropdown, trigger);
                        }
                        return;
                    }

                    if (activeDropdown) {
                        // If click is on a link or button inside the active menu, close menu
                        if (activeDropdown.contains(e.target)) {
                            const actionItem = e.target.closest('a, button[type="submit"]');
                            if (actionItem) {
                                closeActive();
                            }
                            return;
                        }
                        // Click outside closes the dropdown
                        closeActive();
                    }
                });

                // Close on Escape key and return focus to trigger
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && activeDropdown) {
                        const trigger = activeDropdown.querySelector('.custom-dropdown-trigger');
                        closeActive();
                        if (trigger && typeof trigger.focus === 'function') {
                            trigger.focus();
                        }
                    }
                });

                // Close on window resize or table scroll
                window.addEventListener('resize', closeActive, { passive: true });
                const tableResponsive = document.querySelector('.schedule-entries-card .table-responsive');
                if (tableResponsive) {
                    tableResponsive.addEventListener('scroll', closeActive, { passive: true });
                }
            }

            initCustomDropdowns();
        });
    </script>
@endpush