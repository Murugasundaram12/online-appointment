<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Staff;
use App\Models\Client;
use App\Models\Service;
use App\Models\StaffSchedule;
use App\Models\Location;
use App\Services\AppointmentEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class CalendarController extends Controller
{
    public function __construct(private AppointmentEmailService $appointmentEmailService)
    {
    }

    /**
     * Display calendar index page
     */
    public function index(Request $request)
    {
        $staffs  = Staff::where('is_active', true)->get(['id', 'name', 'location_id']);
        $clients = Client::orderByDesc('updated_at')->limit(100)->get(['id', 'name', 'email', 'phone']);
        $services = Service::where('is_active', true)->get(['id', 'name', 'duration_minutes']);
        $locations = Location::where('is_active', true)->get(['id', 'name']);

        $view = $request->query('view', 'week');
        $monthParam = $request->query('month');
        try {
            $calendarMonth = $monthParam
                ? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()
                : Carbon::now()->startOfMonth();
        } catch (\Throwable $e) {
            $calendarMonth = Carbon::now()->startOfMonth();
        }

        $monthStart = $calendarMonth->copy()->startOfMonth();
        $monthEnd = $calendarMonth->copy()->endOfMonth();

        $statusColorMap = [
            'pending' => '#f59e0b',
            'booked' => '#3699ff',
            'completed' => '#1bc5bd',
            'cancelled' => '#f64e60'
        ];

        $monthAppointments = Appointment::with(['staff'])
            ->whereBetween('start_time', [$monthStart, $monthEnd])
            ->get();

        $monthEvents = $monthAppointments->map(function ($appointment) use ($statusColorMap) {
            $staffName = $appointment->staff ? $appointment->staff->name : 'N/A';
            $status = $appointment->status ?? 'booked';

            return [
                'id' => $appointment->id,
                'title' => $staffName,
                'staff' => $staffName,
                'start' => $appointment->start_time->toIso8601String(),
                'end' => $appointment->end_time->toIso8601String(),
                'status' => $status,
                'color' => $statusColorMap[$status] ?? '#3699ff',
            ];
        })->values();

        return view('calendar.index', compact('staffs', 'clients', 'services', 'locations', 'monthEvents', 'calendarMonth', 'view'));
    }

    public function dashboard()
    {
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $stats = [
            'clients' => Client::count(),
            'active_staff' => Staff::where('is_active', true)->count(),
            'active_services' => Service::where('is_active', true)->count(),
            'today_appointments' => Appointment::whereDate('start_time', $today)->count(),
            'pending_appointments' => Appointment::where('status', 'pending')->count(),
            'completed_appointments' => Appointment::where('status', 'completed')->count(),
            'cancelled_appointments' => Appointment::where('status', 'cancelled')->count(),
            'outstanding_invoice_amount' => \App\Models\Invoice::where('status', '!=', 'void')->sum(\Illuminate\Support\Facades\DB::raw('GREATEST(total_amount - paid_amount, 0)')),
            'paid_invoice_amount' => \App\Models\Invoice::where('status', '!=', 'void')->sum('paid_amount'),
        ];

        $recentAppointments = Appointment::with(['client', 'staff', 'service'])
            ->latest()
            ->limit(5)
            ->get();

        $upcomingAppointments = Appointment::with(['client', 'staff', 'service'])
            ->where('start_time', '>=', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->limit(5)
            ->get();

        $monthlyAppointments = Appointment::whereBetween('start_time', [$monthStart, $monthEnd])->count();
        $monthlyRevenue = \App\Models\Invoice::whereBetween('issued_date', [$monthStart, $monthEnd])
            ->where('status', '!=', 'void')
            ->sum('paid_amount');
        $dailyAppointmentCounts = [];
        $dailyRevenueCounts = [];
        $dailyLabels = [];
        $cursor = $monthStart->copy();
        while ($cursor->lte($monthEnd)) {
            $dailyLabels[] = $cursor->format('M j');
            $dailyAppointmentCounts[] = Appointment::whereDate('start_time', $cursor)->count();
            $dailyRevenueCounts[] = (float) \App\Models\Invoice::whereDate('issued_date', $cursor)
                ->where('status', '!=', 'void')
                ->sum('paid_amount');
            $cursor->addDay();
        }
        $statusSummary = Appointment::select('status', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return view('dashboard.index', compact('stats', 'recentAppointments', 'upcomingAppointments', 'monthlyAppointments', 'monthlyRevenue', 'dailyLabels', 'dailyAppointmentCounts', 'dailyRevenueCounts', 'statusSummary'));
    }

    /**
     * Get events for calendar (appointments and availability)
     */
    public function getEvents(Request $request)
    {
        $start = $request->query('start');
        $end = $request->query('end');

        // Parse dates or default to current week
        $startDate = $start ? Carbon::parse($start)->startOfDay() : Carbon::now()->startOfWeek();
        $endDate = $end ? Carbon::parse($end)->endOfDay() : Carbon::now()->endOfWeek();

        // Get all appointments
        $appointmentsQuery = Appointment::with(['client:id,name', 'service:id,name', 'staff:id,name', 'location:id,name,is_active'])
            ->whereBetween('start_time', [$startDate, $endDate]);

        if ($request->filled('staff_id')) {
            $appointmentsQuery->where('staff_id', $request->query('staff_id'));
        }
        if ($request->filled('service_id')) {
            $appointmentsQuery->where('service_id', $request->query('service_id'));
        }
        if ($request->filled('status')) {
            $appointmentsQuery->where('status', $request->query('status'));
        }
        if ($request->filled('location_id')) {
            $appointmentsQuery->where('location_id', $request->query('location_id'));
        }

        $appointments = $appointmentsQuery->get();

        $events = $appointments->map(function ($appointment) {
            $statusColorMap = [
                'pending' => '#f59e0b',
                'booked' => '#3699ff',
                'completed' => '#1bc5bd',
                'cancelled' => '#f64e60'
            ];

            return [
                'id' => $appointment->id,
                'title' => $appointment->client ? $appointment->client->name : 'Unassigned',
                'staff' => $appointment->staff ? $appointment->staff->name : 'N/A',
                'service' => $appointment->service ? $appointment->service->name : 'N/A',
                'start' => $appointment->start_time->toIso8601String(),
                'end' => $appointment->end_time->toIso8601String(),
                'status' => $appointment->status ?? 'booked',
                'staffId' => $appointment->staff_id,
                'serviceId' => $appointment->service_id,
                'clientId' => $appointment->client_id,
                'locationId' => $appointment->location_id,
                'location' => $appointment->location ? $appointment->location->name : null,
                'hasClient' => !is_null($appointment->client_id),
                'color' => $statusColorMap[$appointment->status ?? 'booked'] ?? '#3699ff',
                'notes' => $appointment->notes,
            ];
        });

        return response()->json($events);
    }

    /**
     * Return staff schedules for a given date range (week)
     */
    public function getStaffSchedules(Request $request)
    {
        $start = $request->query('start');
        $end = $request->query('end');

        $startDate = $start ? Carbon::parse($start)->startOfDay() : Carbon::now()->startOfWeek();
        $endDate = $end ? Carbon::parse($end)->endOfDay() : Carbon::now()->endOfWeek();

        $staff = Staff::where('is_active', true)->with(['schedules'])->get();
        $hasWorkingDate = Schema::hasColumn('staff_schedules', 'working_date');

        $result = $staff->map(function ($s) use ($startDate, $endDate, $hasWorkingDate) {
            // Weekly schedules keyed by day_of_week (0=Mon ... 6=Sun)
            $schedules = [];
            // Date-specific schedules keyed by YYYY-MM-DD
            $schedulesByDate = [];

            foreach ($s->schedules as $sch) {
                $scheduleData = [
                    'start_time' => $sch->start_time,
                    'end_time' => $sch->end_time,
                    'is_working' => (bool) $sch->is_working,
                    'breaks' => $sch->breaks ?? []
                ];

                if ($hasWorkingDate && !empty($sch->working_date)) {
                    $schedulesByDate[Carbon::parse($sch->working_date)->toDateString()] = $scheduleData;
                    continue;
                }

                $normalizedDay = $this->normalizeDayOfWeek($sch->day_of_week);
                if (!is_null($normalizedDay)) {
                    $schedules[$normalizedDay] = $scheduleData;
                }
            }

            // Build effective date schedule for requested range
            $effectiveSchedulesByDate = [];
            $cursor = $startDate->copy()->startOfDay();
            $rangeEnd = $endDate->copy()->startOfDay();
            while ($cursor->lte($rangeEnd)) {
                $dateKey = $cursor->toDateString();
                $dayIdx = ($cursor->dayOfWeek + 6) % 7; // Carbon 0=Sun -> 0=Mon
                $effectiveSchedulesByDate[$dateKey] = $schedulesByDate[$dateKey] ?? ($schedules[$dayIdx] ?? null);
                $cursor->addDay();
            }

            return [
                'id' => $s->id,
                'name' => $s->name,
                'color' => $s->color ?? null,
                'schedules' => $schedules,
                'schedules_by_date' => $effectiveSchedulesByDate
            ];
        });

        return response()->json(['start' => $startDate->toDateString(), 'end' => $endDate->toDateString(), 'staff' => $result]);
    }

    /**
     * Create new appointment
     */
    public function storeAppointment(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => ['required', Rule::exists('staff', 'id')->where('is_active', true)],
            'service_id' => ['required', Rule::exists('services', 'id')->where('is_active', true)],
            'client_id' => 'required|exists:clients,id',
            'location_id' => ['nullable', 'exists:locations,id'],
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'status' => 'nullable|in:pending,booked,completed,cancelled',
            'notes' => 'nullable|string'
        ]);

        $service = Service::where('is_active', true)->findOrFail($validated['service_id']);
        $staff = Staff::where('is_active', true)->findOrFail($validated['staff_id']);

        $durationValidation = $this->validateAppointmentDuration($validated['start_time'], $validated['end_time'], $service);
        if (!$durationValidation['valid']) {
            return response()->json(['success' => false, 'message' => $durationValidation['message']], 422);
        }

        $locationValidation = $this->validateStaffLocation($staff, $validated['location_id'] ?? null);
        if (!$locationValidation['valid']) {
            return response()->json(['success' => false, 'message' => $locationValidation['message']], 422);
        }

        $validation = $this->validateStaffAvailability(
            $validated['staff_id'],
            $validated['start_time'],
            $validated['end_time'],
            null,
            (int) ($service->buffer_minutes ?? 0)
        );

        if (!$validation['available']) {
            return response()->json([
                'success' => false,
                'message' => $validation['message']
            ], 422);
        }

        try {
            $appointment = Appointment::create($validated);
            $appointment->load(['client', 'service', 'staff', 'location']);
            $emailResult = $this->appointmentEmailService->sendBooked($appointment);

            return response()->json([
                'success' => true,
                'message' => $this->appointmentEmailMessage('Appointment created successfully', $emailResult),
                'email' => $emailResult,
                'appointment' => $this->formatAppointment($appointment)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating appointment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update appointment (reschedule or status change)
     */
    public function updateAppointment(Request $request, $id)
    {
        $appointment = Appointment::with(['client', 'service', 'staff', 'location'])->findOrFail($id);
        $previousAppointment = $appointment->replicate();
        $previousAppointment->setRelation('client', $appointment->client);
        $previousAppointment->setRelation('service', $appointment->service);
        $previousAppointment->setRelation('staff', $appointment->staff);
        $previousAppointment->setRelation('location', $appointment->location);

        $validated = $request->validate([
            'staff_id' => ['nullable', Rule::exists('staff', 'id')->where('is_active', true)],
            'service_id' => ['nullable', Rule::exists('services', 'id')->where('is_active', true)],
            'location_id' => ['nullable', Rule::exists('locations', 'id')->where('is_active', true)],
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'status' => 'nullable|in:pending,booked,completed,cancelled',
            'client_id' => 'sometimes|exists:clients,id',
            'notes' => 'nullable|string'
        ]);

        // If rescheduling, validate staff availability
        if ($request->has('start_time') || $request->has('end_time') || $request->has('staff_id') || $request->has('service_id') || $request->has('location_id')) {
            $startTime = $validated['start_time'] ?? $appointment->start_time;
            $endTime = $validated['end_time'] ?? $appointment->end_time;
            $staffId = $validated['staff_id'] ?? $appointment->staff_id;
            $serviceId = $validated['service_id'] ?? $appointment->service_id;
            $service = Service::where('is_active', true)->findOrFail($serviceId);
            $staff = Staff::where('is_active', true)->findOrFail($staffId);

            $durationValidation = $this->validateAppointmentDuration($startTime, $endTime, $service);
            if (!$durationValidation['valid']) {
                return response()->json(['success' => false, 'message' => $durationValidation['message']], 422);
            }

            $locationValidation = $this->validateStaffLocation($staff, $validated['location_id'] ?? $appointment->location_id, $appointment->location_id);
            if (!$locationValidation['valid']) {
                return response()->json(['success' => false, 'message' => $locationValidation['message']], 422);
            }

            $validation = $this->validateStaffAvailability(
                $staffId,
                $startTime,
                $endTime,
                $id,
                (int) ($service->buffer_minutes ?? 0)
            );

            if (!$validation['available']) {
                return response()->json([
                    'success' => false,
                    'message' => $validation['message']
                ], 422);
            }
        }

        try {
            $appointment->update($validated);
            $appointment->load(['client', 'service', 'staff', 'location']);
            $emailResult = $this->appointmentEmailAfterUpdate($appointment, $previousAppointment);

            return response()->json([
                'success' => true,
                'message' => $this->appointmentEmailMessage('Appointment updated successfully', $emailResult),
                'email' => $emailResult,
                'appointment' => $this->formatAppointment($appointment)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating appointment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign client to appointment
     */
    public function assignClient(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id'
        ]);

        try {
            $appointment->update(['client_id' => $validated['client_id']]);
            $appointment->load(['client', 'service', 'staff']);

            return response()->json([
                'success' => true,
                'message' => 'Client assigned successfully',
                'appointment' => $this->formatAppointment($appointment)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error assigning client: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Return single appointment details
     */
    public function getAppointment($id)
    {
        $appointment = Appointment::with(['client', 'service', 'staff', 'location'])->findOrFail($id);

        return response()->json($this->formatAppointment($appointment));
    }

    /**
     * Create quick client from calendar modal
     */
    public function quickCreateClient(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'nullable|string|max:30'
        ]);

        $client = Client::create($validated);

        return response()->json([
            'success' => true,
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
            ]
        ], 201);
    }

    public function searchClients(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        if (strlen($term) < 2) {
            return response()->json([]);
        }

        $clients = Client::query()
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', '%' . $term . '%')
                    ->orWhere('email', 'like', '%' . $term . '%')
                    ->orWhere('phone', 'like', '%' . $term . '%');
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email', 'phone']);

        return response()->json($clients);
    }

    /**
     * Validate staff availability for given time slot
     */
    private function validateStaffAvailability($staffId, $startTime, $endTime, $excludeAppointmentId = null, int $newBufferMinutes = 0)
    {
        /**
         * Important:
         * JS sends datetime strings in local time (no explicit timezone).
         * Using Carbon::parse() can introduce implicit timezone assumptions and shift the window,
         * leading to false conflict detection.
         *
         * We treat incoming strings as app-local time deterministically.
         */
        $appTz = config('app.timezone', 'UTC');
        try {
            $startTime = Carbon::createFromFormat('Y-m-d\TH:i:s', (string) $startTime, $appTz);
        } catch (\Throwable $e) {
            $startTime = Carbon::parse((string) $startTime, $appTz);
        }

        try {
            $endTime = Carbon::createFromFormat('Y-m-d\TH:i:s', (string) $endTime, $appTz);
        } catch (\Throwable $e) {
            $endTime = Carbon::parse((string) $endTime, $appTz);
        }

        $inputTimezone = $appTz;
        $dayOfWeek = ($startTime->dayOfWeek + 6) % 7; // 0=Mon ... 6=Sun
        $appointmentDate = $startTime->toDateString();
        $dayName = strtolower($startTime->format('l')); // monday ... sunday
        $hasWorkingDate = Schema::hasColumn('staff_schedules', 'working_date');

        $findCoveringSchedule = function ($schedules) use ($startTime, $endTime, $inputTimezone) {
            foreach ($schedules as $sch) {
                if (!$sch || !$sch->is_working || empty($sch->start_time) || empty($sch->end_time)) {
                    continue;
                }
                $scheduleStart = Carbon::parse($startTime->format('Y-m-d') . ' ' . $sch->start_time, $inputTimezone);
                $scheduleEnd = Carbon::parse($startTime->format('Y-m-d') . ' ' . $sch->end_time, $inputTimezone);
                if ($startTime->gte($scheduleStart) && $endTime->lte($scheduleEnd)) {
                    return $sch;
                }
            }
            return null;
        };

        // Date-specific schedules have priority over weekly schedules.
        $dateSchedules = collect();
        if ($hasWorkingDate) {
            $dateSchedules = StaffSchedule::where('staff_id', $staffId)
                ->whereDate('working_date', $appointmentDate)
                ->orderBy('start_time')
                ->get();
        }

        $schedule = $findCoveringSchedule($dateSchedules);
        if (!$schedule && $dateSchedules->count() > 0) {
            return [
                'available' => false,
                'message' => 'Appointment time is outside staff working hours'
            ];
        }

        if (!$schedule) {
            $query = StaffSchedule::where('staff_id', $staffId);
            if ($hasWorkingDate) {
                $query->whereNull('working_date');
            }

            $weeklySchedules = $query
                ->where(function ($q) use ($dayOfWeek, $dayName) {
                    $q->where('day_of_week', (string) $dayOfWeek)
                        ->orWhere('day_of_week', $dayOfWeek)
                        ->orWhere('day_of_week', $dayName);
                })
                ->orderBy('start_time')
                ->get();

            $schedule = $findCoveringSchedule($weeklySchedules);
            if (!$schedule && $weeklySchedules->count() > 0) {
                return [
                    'available' => false,
                    'message' => 'Appointment time is outside staff working hours'
                ];
            }
        }

        if (!$schedule) {
            return [
                'available' => false,
                'message' => 'Staff is not scheduled to work on this day'
            ];
        }

        // Check if appointment overlaps with breaks
        if ($schedule->breaks && is_array($schedule->breaks)) {
            foreach ($schedule->breaks as $break) {
                $breakStart = Carbon::parse($startTime->format('Y-m-d') . ' ' . $break['start'], $inputTimezone);
                $breakEnd = Carbon::parse($startTime->format('Y-m-d') . ' ' . $break['end'], $inputTimezone);

                if ($startTime->lt($breakEnd) && $endTime->gt($breakStart)) {
                    return [
                        'available' => false,
                        'message' => 'Appointment conflicts with staff break time'
                    ];
                }
            }
        }

        // Check for conflicts with existing appointments
        // Compare in UTC to match database datetime storage and avoid timezone-shift false positives.
        $queryStart = $startTime->copy()->utc();
        $queryEnd = $endTime->copy()->addMinutes(max(0, $newBufferMinutes))->utc();

        $query = Appointment::where('staff_id', $staffId)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($queryStart, $queryEnd) {
                $q->where('start_time', '<', $queryEnd)
                    ->whereRaw('DATE_ADD(end_time, INTERVAL COALESCE((SELECT buffer_minutes FROM services WHERE services.id = appointments.service_id), 0) MINUTE) > ?', [$queryStart]);
            });

        if ($excludeAppointmentId) {
            $query->where('id', '!=', $excludeAppointmentId);
        }

        if ($query->exists()) {
            return [
                'available' => false,
                'message' => 'Staff has a conflicting appointment at this time'
            ];
        }

        return [
            'available' => true,
            'message' => 'Time slot is available'
        ];
    }

    private function validateAppointmentDuration($startTime, $endTime, Service $service): array
    {
        $appTz = config('app.timezone', 'UTC');
        $start = Carbon::parse((string) $startTime, $appTz);
        $end = Carbon::parse((string) $endTime, $appTz);

        if ($end->lte($start)) {
            return ['valid' => false, 'message' => 'End time must be after start time'];
        }

        if (!$start->isSameDay($end)) {
            return ['valid' => false, 'message' => 'Cross-day appointments are not supported'];
        }

        $duration = $start->diffInMinutes($end);
        if ($duration > 12 * 60) {
            return ['valid' => false, 'message' => 'Appointment duration is too long'];
        }

        if ($duration < (int) $service->duration_minutes) {
            return ['valid' => false, 'message' => 'Appointment duration must be at least the selected service duration'];
        }

        return ['valid' => true, 'message' => 'Duration is valid'];
    }

    private function validateStaffLocation(Staff $staff, ?int $locationId, ?int $historicalLocationId = null): array
    {
        if (!$locationId) {
            return ['valid' => true, 'message' => 'Location is valid'];
        }

        $location = Location::find($locationId);
        if (!$location) {
            return ['valid' => false, 'message' => 'Selected location is not available'];
        }

        if (!$location->is_active && (int) $locationId !== (int) $historicalLocationId) {
            return ['valid' => false, 'message' => 'Selected location is not available'];
        }

        if ($staff->location_id && (int) $staff->location_id !== (int) $locationId) {
            return ['valid' => false, 'message' => 'Selected staff does not belong to this location'];
        }

        return ['valid' => true, 'message' => 'Location is valid'];
    }

    private function normalizeDayOfWeek($value)
    {
        if (is_null($value) || empty(trim(strval($value)))) {
            return null;
        }
        $value = trim(strval($value));
        if (is_numeric($value)) {
            $n = (int) $value;
            return ($n >= 0 && $n <= 6) ? $n : null;
        }
        $days = [
            'monday' => 0,
            'tuesday' => 1,
            'wednesday' => 2,
            'thursday' => 3,
            'friday' => 4,
            'saturday' => 5,
            'sunday' => 6,
        ];
        $key = strtolower($value);
        return $days[$key] ?? null;
    }

    /**
     * Format appointment for JSON response
     */
    private function formatAppointment($appointment)
    {
        $statusColorMap = [
            'pending' => '#f59e0b',
            'booked' => '#3699ff',
            'completed' => '#1bc5bd',
            'cancelled' => '#f64e60'
        ];

        return [
            'id' => $appointment->id,
            'title' => $appointment->client ? $appointment->client->name : 'Unassigned',
            'staff' => $appointment->staff ? $appointment->staff->name : 'N/A',
            'service' => $appointment->service ? $appointment->service->name : 'N/A',
            'start' => $appointment->start_time->toIso8601String(),
            'end' => $appointment->end_time->toIso8601String(),
            'status' => $appointment->status ?? 'booked',
            'staffId' => $appointment->staff_id,
            'serviceId' => $appointment->service_id,
            'clientId' => $appointment->client_id,
            'locationId' => $appointment->location_id,
            'location' => $appointment->location ? $appointment->location->name : null,
            'hasClient' => !is_null($appointment->client_id),
            'color' => $statusColorMap[$appointment->status ?? 'booked'] ?? '#3699ff',
            'notes' => $appointment->notes,
        ];
    }

    private function appointmentEmailAfterUpdate(Appointment $appointment, Appointment $previousAppointment): array
    {
        if ($appointment->status === 'cancelled') {
            return $this->appointmentEmailService->sendCancelledIfTransitioned($appointment, $previousAppointment);
        }

        if ($appointment->status === 'completed') {
            return $this->appointmentEmailService->sendCompletedIfTransitioned($appointment, $previousAppointment);
        }

        return $this->appointmentEmailService->sendUpdatedIfRelevant($appointment, $previousAppointment);
    }

    private function appointmentEmailMessage(string $baseMessage, array $emailResult): string
    {
        if (($emailResult['attempted'] ?? false) === false) {
            return $baseMessage . '.';
        }

        return $baseMessage . '. ' . (($emailResult['sent'] ?? false)
            ? 'Confirmation email sent.'
            : 'Confirmation email could not be sent.');
    }
}
