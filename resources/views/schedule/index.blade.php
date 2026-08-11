@extends('layouts.app')

@section('title', 'Schedule')

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
    </style>
@endpush

@section('content')
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

    <!-- Create Schedule Modal -->
    <div class="modal fade app-modal" id="createScheduleModal" tabindex="-1" aria-labelledby="createScheduleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" id="create-schedule-form" data-app-managed="true">
                <div class="modal-header">
                    <div class="modal-heading">
                        <div class="modal-icon" aria-hidden="true"><i class="bx bx-time-five"></i></div>
                        <div>
                            <h5 class="modal-title" id="createScheduleModalLabel">Create Schedule</h5>
                            <p class="modal-subtitle">Set staff working hours for this date.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="create-schedule-error"></div>

                    <input type="hidden" id="cs-staff-id" name="staff_id" value="">
                    <input type="hidden" id="cs-working-date" name="working_date" value="">
                    <input type="hidden" id="cs-day-of-week" name="day_of_week" value="">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small text-muted">Staff Name</label>
                            <input type="text" class="form-control" id="cs-staff-name" value="" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted">Category</label>
                            <input type="text" class="form-control" id="cs-staff-category" value="" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-muted">Start Time <span class="required-mark">*</span></label>
                            <input type="time" class="form-control" id="cs-start-time" name="start_time" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-muted">End Time <span class="required-mark">*</span></label>
                            <input type="time" class="form-control" id="cs-end-time" name="end_time" required>
                        </div>
                    </div>
                    <div class="mt-2 small text-muted" id="cs-date-label"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
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

            const createModalEl = document.getElementById('createScheduleModal');
            const createModal = createModalEl ? new bootstrap.Modal(createModalEl) : null;
            const createForm = document.getElementById('create-schedule-form');
            const createError = document.getElementById('create-schedule-error');
            const csStaffId = document.getElementById('cs-staff-id');
            const csWorkingDate = document.getElementById('cs-working-date');
            const csDayOfWeek = document.getElementById('cs-day-of-week');
            const csStaffName = document.getElementById('cs-staff-name');
            const csStaffCategory = document.getElementById('cs-staff-category');
            const csStartTime = document.getElementById('cs-start-time');
            const csEndTime = document.getElementById('cs-end-time');
            const csDateLabel = document.getElementById('cs-date-label');
            const scheduleCreateApiUrl = new URL('schedule-api/create', window.location.href).toString();

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
                        onConfirm: () => createSchedule(currentStaffId, dayIndex, workingDate, startTime, endTime)
                    });
                    return;
                }

                if (createError) {
                    createError.classList.add('d-none');
                    createError.textContent = '';
                }
                if (csStaffId) csStaffId.value = String(currentStaffId);
                if (csWorkingDate) csWorkingDate.value = workingDate;
                if (csDayOfWeek) csDayOfWeek.value = String(dayIndex);
                if (csStaffName) csStaffName.value = currentStaffName || '';
                if (csStaffCategory) csStaffCategory.value = currentStaffCategory || '';
                if (csStartTime) csStartTime.value = startTime;
                if (csEndTime) csEndTime.value = endTime;
                if (csDateLabel) csDateLabel.textContent = `Date: ${workingDate}`;

                createModal.show();
            }

            async function parseApiResponse(res) {
                const contentType = res.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    return await res.json();
                }

                const rawText = await res.text();
                throw new Error(rawText.includes('<!doctype') || rawText.includes('<html')
                    ? `Server returned HTML (status ${res.status}). Check CSRF/session or backend error logs.`
                    : (rawText || `Unexpected response from server (status ${res.status}).`));
            }

            function createSchedule(staffId, dayIndex, workingDate, startTime, endTime) {
                fetch(scheduleCreateApiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        staff_id: staffId,
                        day_of_week: dayIndex,
                        working_date: workingDate,
                        start_time: startTime,
                        end_time: endTime,
                        is_working: true
                    })
                })
                    .then(parseApiResponse)
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            window.AppToast?.show({ type: 'danger', title: 'Schedule error', message: data.message || 'Could not create schedule' });
                        }
                    })
                    .catch(err => window.AppToast?.show({ type: 'danger', title: 'Schedule error', message: err.message }));
            }

            function editSchedule(scheduleId) {
                const newStart = prompt('Enter new start time (HH:MM):');
                if (!newStart) return;

                const newEnd = prompt('Enter new end time (HH:MM):');
                if (!newEnd) return;

                const updateUrl = new URL(`schedule-api/${scheduleId}`, window.location.href).toString();
                fetch(updateUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        start_time: newStart,
                        end_time: newEnd
                    })
                })
                    .then(parseApiResponse)
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            window.AppToast?.show({ type: 'danger', title: 'Schedule error', message: data.message || 'Could not update schedule' });
                        }
                    })
                    .catch(err => window.AppToast?.show({ type: 'danger', title: 'Schedule error', message: err.message }));
            }

            // Initial render
            renderScheduleGrid();

            if (createForm) {
                createForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const staffId = csStaffId?.value;
                    const workingDate = csWorkingDate?.value;
                    const dayIndex = csDayOfWeek?.value;
                    const startTime = csStartTime?.value;
                    const endTime = csEndTime?.value;

                    if (!staffId || !startTime || !endTime) return;

                    if (endTime <= startTime) {
                        if (createError) {
                            createError.textContent = 'End time must be after start time.';
                            createError.classList.remove('d-none');
                        }
                        return;
                    }

                    createSchedule(parseInt(staffId, 10), parseInt(dayIndex, 10), workingDate, startTime, endTime);
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
