<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Staff;
use App\Models\Client;
use App\Models\Service;
use App\Models\StaffSchedule;
use App\Models\Location;
use App\Models\Payroll;
use App\Services\AppointmentEmailService;
use App\Support\StaffCategoryService;
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
        $staffs  = Staff::where('is_active', true)->get(['id', 'name', 'location_id', 'category']);
        $clients = Client::orderByDesc('updated_at')->limit(100)->get(['id', 'name', 'email', 'phone']);
        $services = Service::where('is_active', true)
            ->with('category:id,name')
            ->get(['id', 'name', 'duration_minutes', 'service_category_id']);
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
            'pending'   => '#f59e0b',
            'booked'    => '#3699ff',
            'confirmed' => '#6366f1',
            'completed' => '#1bc5bd',
            'cancelled' => '#f64e60',
            'no_show'   => '#8b5cf6',
        ];

        $filters = [
            'location_id' => $request->query('location_id'),
            'staff_id' => $request->query('staff_id'),
            'service_id' => $request->query('service_id'),
            'status' => $request->query('status'),
        ];

        $monthAppointmentsQuery = Appointment::with(['staff'])
            ->whereBetween('start_time', [$monthStart, $monthEnd]);

        if ($request->filled('location_id')) {
            $monthAppointmentsQuery->where('location_id', $request->query('location_id'));
        }
        if ($request->filled('staff_id')) {
            $monthAppointmentsQuery->where('staff_id', $request->query('staff_id'));
        }
        if ($request->filled('service_id')) {
            $monthAppointmentsQuery->where('service_id', $request->query('service_id'));
        }
        if ($request->filled('status')) {
            $monthAppointmentsQuery->where('status', $request->query('status'));
        }

        $monthAppointments = $monthAppointmentsQuery->get();

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

        return view('calendar.index', compact('staffs', 'clients', 'services', 'locations', 'monthEvents', 'calendarMonth', 'view', 'filters'));
    }

    public function dashboard()
    {
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        // Today's appointment counts by status
        $todayAppointmentsQuery = Appointment::whereDate('start_time', $today);
        $todayTotal = (clone $todayAppointmentsQuery)->count();
        $todayPending = (clone $todayAppointmentsQuery)->where('status', 'pending')->count();
        $todayBooked = (clone $todayAppointmentsQuery)->where('status', 'booked')->count();
        $todayConfirmed = (clone $todayAppointmentsQuery)->where('status', 'confirmed')->count();
        $todayCompleted = (clone $todayAppointmentsQuery)->where('status', 'completed')->count();
        $todayCancelled = (clone $todayAppointmentsQuery)->where('status', 'cancelled')->count();
        $todayNoShow = (clone $todayAppointmentsQuery)->where('status', 'no_show')->count();

        // Today's revenue (collected payment records + paid invoices today)
        $todayRevenue = (float) \App\Models\PaymentRecord::whereDate('payment_date', $today)->sum('amount');
        if ($todayRevenue <= 0) {
            $todayRevenue = (float) \App\Models\Invoice::whereDate('issued_date', $today)
                ->where('status', '!=', 'void')
                ->sum('paid_amount');
        }

        $todayStats = [
            'total' => $todayTotal,
            'pending' => $todayPending,
            'booked' => $todayBooked,
            'confirmed' => $todayConfirmed,
            'completed' => $todayCompleted,
            'cancelled' => $todayCancelled,
            'no_show' => $todayNoShow,
            'revenue' => $todayRevenue,
        ];

        $stats = [
            'clients' => Client::count(),
            'active_staff' => Staff::where('is_active', true)->count(),
            'active_services' => Service::where('is_active', true)->count(),
            'today_appointments' => $todayTotal,
            'pending_appointments' => Appointment::where('status', 'pending')->count(),
            'completed_appointments' => Appointment::where('status', 'completed')->count(),
            'cancelled_appointments' => Appointment::where('status', 'cancelled')->count(),
            'outstanding_invoice_amount' => (float) max(0, \App\Models\Invoice::where('status', '!=', 'void')->selectRaw('SUM(total_amount - paid_amount) as bal')->value('bal') ?? 0),
            'paid_invoice_amount' => \App\Models\Invoice::where('status', '!=', 'void')->sum('paid_amount'),
            'pending_payroll_count' => Payroll::where('status', 'pending')->count(),
            'monthly_payroll_amount' => Payroll::whereBetween('payment_date', [$monthStart, $monthEnd])
                ->whereIn('status', ['completed', 'paid'])
                ->sum('total_payout'),
            'upcoming_salary_payments' => Payroll::where('status', 'pending')
                ->whereBetween('payment_date', [$today, Carbon::now()->addDays(14)])
                ->count(),
        ];

        $todaySchedule = Appointment::with(['client', 'staff', 'service', 'location', 'invoice'])
            ->whereDate('start_time', $today)
            ->orderBy('start_time')
            ->get();

        $recentAppointments = Appointment::with(['client', 'staff', 'service'])
            ->latest()
            ->limit(5)
            ->get();

        $upcomingAppointments = Appointment::with(['client', 'staff', 'service', 'location'])
            ->where('start_time', '>=', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->limit(10)
            ->get();

        // Staff daily workload summary
        $allActiveStaff = Staff::where('is_active', true)->get();
        $staffWorkload = $allActiveStaff->map(function ($staffMember) use ($todaySchedule) {
            $staffAppts = $todaySchedule->where('staff_id', $staffMember->id);
            return [
                'staff' => $staffMember,
                'total' => $staffAppts->count(),
                'confirmed' => $staffAppts->where('status', 'confirmed')->count(),
                'completed' => $staffAppts->where('status', 'completed')->count(),
                'no_show' => $staffAppts->where('status', 'no_show')->count(),
                'cancelled' => $staffAppts->where('status', 'cancelled')->count(),
            ];
        });

        $monthlyAppointments = Appointment::whereBetween('start_time', [$monthStart, $monthEnd])->count();
        $monthlyRevenue = \App\Models\Invoice::whereBetween('issued_date', [$monthStart, $monthEnd])
            ->where('status', '!=', 'void')
            ->sum('paid_amount');

        $appointmentsByDate = Appointment::selectRaw('DATE(start_time) as date, COUNT(*) as count')
            ->whereBetween('start_time', [$monthStart, $monthEnd->copy()->endOfDay()])
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $revenuesByDate = \App\Models\Invoice::selectRaw('DATE(issued_date) as date, SUM(paid_amount) as sum')
            ->whereBetween('issued_date', [$monthStart, $monthEnd])
            ->where('status', '!=', 'void')
            ->groupBy('date')
            ->pluck('sum', 'date')
            ->toArray();

        $dailyAppointmentCounts = [];
        $dailyRevenueCounts = [];
        $dailyLabels = [];
        $cursor = $monthStart->copy();
        while ($cursor->lte($monthEnd)) {
            $dateStr = $cursor->toDateString();
            $dailyLabels[] = $cursor->format('M j');
            $dailyAppointmentCounts[] = $appointmentsByDate[$dateStr] ?? 0;
            $dailyRevenueCounts[] = (float) ($revenuesByDate[$dateStr] ?? 0);
            $cursor->addDay();
        }
        $statusSummary = Appointment::select('status', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return view('dashboard.index', compact(
            'stats',
            'todayStats',
            'todaySchedule',
            'recentAppointments',
            'upcomingAppointments',
            'staffWorkload',
            'monthlyAppointments',
            'monthlyRevenue',
            'dailyLabels',
            'dailyAppointmentCounts',
            'dailyRevenueCounts',
            'statusSummary'
        ));
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
        $appointmentsQuery = Appointment::with(['client:id,name', 'service:id,name', 'staff:id,name', 'location:id,name,is_active', 'invoice:id,appointment_id,invoice_number'])
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
                'pending'   => '#f59e0b',
                'booked'    => '#3699ff',
                'confirmed' => '#6366f1',
                'completed' => '#1bc5bd',
                'cancelled' => '#f64e60',
                'no_show'   => '#8b5cf6',
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
                'invoiceId' => $appointment->invoice?->id,
                'invoiceNumber' => $appointment->invoice?->invoice_number,
                'hasForms' => $appointment->client_id ? \App\Models\FormRecord::where('client_id', $appointment->client_id)->exists() : false,
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
        $holidays = $this->holidayDates();

        $result = $staff->map(function ($s) use ($startDate, $endDate, $hasWorkingDate, $holidays) {
            // Weekly templates keyed by day_of_week (0=Mon ... 6=Sun)
            $schedules = [];
            // Date-specific schedules keyed by YYYY-MM-DD (array of segments; a date
            // with segments always overrides weekly templates, even when empty)
            $schedulesByDate = [];

            foreach ($s->schedules as $sch) {
                $scheduleData = [
                    'start_time' => $sch->start_time,
                    'end_time' => $sch->end_time,
                    'is_working' => (bool) $sch->is_working,
                    'breaks' => $sch->breaks ?? []
                ];

                if ($hasWorkingDate && !empty($sch->working_date)) {
                    $dateKey = Carbon::parse($sch->working_date)->toDateString();
                    $schedulesByDate[$dateKey][] = $scheduleData;
                    continue;
                }

                $normalizedDay = $this->normalizeDayOfWeek($sch->day_of_week);
                if (!is_null($normalizedDay)) {
                    $schedules[$normalizedDay] = $scheduleData;
                }
            }

            foreach ($schedulesByDate as $dateKey => $segments) {
                $schedulesByDate[$dateKey] = collect($segments)->sortBy('start_time')->values()->all();
            }

            // Build effective date schedule for requested range (segments sorted,
            // explicit empty array = not scheduled / date override wins over template)
            $effectiveSchedulesByDate = [];
            $cursor = $startDate->copy()->startOfDay();
            $rangeEnd = $endDate->copy()->startOfDay();
            while ($cursor->lte($rangeEnd)) {
                $dateKey = $cursor->toDateString();
                $dayIdx = ($cursor->dayOfWeek + 6) % 7; // Carbon 0=Sun -> 0=Mon
                if (in_array($dateKey, $holidays, true)) {
                    $effectiveSchedulesByDate[$dateKey] = [];
                } elseif (array_key_exists($dateKey, $schedulesByDate)) {
                    $effectiveSchedulesByDate[$dateKey] = $schedulesByDate[$dateKey];
                } else {
                    $effectiveSchedulesByDate[$dateKey] = isset($schedules[$dayIdx]) ? [$schedules[$dayIdx]] : [];
                }
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
        if (!\App\Models\Subscription::checkLimit('appointment')) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment limit for your current subscription plan has been reached.'
            ], 422);
        }

        $validated = $request->validate([
            'staff_id' => ['required', Rule::exists('staff', 'id')->where('is_active', true)],
            'service_id' => ['required', Rule::exists('services', 'id')->where('is_active', true)],
            'client_id' => 'required|exists:clients,id',
            'location_id' => ['nullable', 'exists:locations,id'],
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'status' => 'nullable|in:pending,booked,confirmed,completed,cancelled,no_show',
            'notes' => 'nullable|string'
        ]);

        if (!$this->authorizeAppointmentStaffAccess((int) $validated['staff_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to create appointment for this staff member.'
            ], 403);
        }

        $service = Service::where('is_active', true)->findOrFail($validated['service_id']);
        $staff = Staff::where('is_active', true)->findOrFail($validated['staff_id']);

        if (!StaffCategoryService::staffCanProvide($staff, $service)) {
            return response()->json([
                'success' => false,
                'message' => 'The selected service is not available for this staff member.',
            ], 422);
        }

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

        if (!$this->authorizeAppointmentStaffAccess((int) $appointment->staff_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to modify this appointment.'
            ], 403);
        }

        if ($request->filled('staff_id') && !$this->authorizeAppointmentStaffAccess((int) $request->input('staff_id'))) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to reassign appointment to this staff member.'
            ], 403);
        }

        // Terminal status lock: completed, cancelled, and no_show appointments cannot be rescheduled.
        $terminalStatuses = ['completed', 'cancelled', 'no_show'];
        $currentStatus = $appointment->status;
        $rescheduleFields = ['start_time', 'end_time', 'staff_id', 'service_id', 'location_id'];
        $isRescheduling = collect($rescheduleFields)->contains(fn($f) => $request->has($f));

        if ($isRescheduling && in_array($currentStatus, $terminalStatuses, true)) {
            $label = match ($currentStatus) {
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
                'no_show'   => 'No-show',
                default     => ucfirst($currentStatus),
            };
            return response()->json([
                'success' => false,
                'message' => "{$label} appointments cannot be rescheduled.",
            ], 422);
        }

        $previousAppointment = $appointment->replicate();
        $previousAppointment->setRelation('client', $appointment->client);
        $previousAppointment->setRelation('service', $appointment->service);
        $previousAppointment->setRelation('staff', $appointment->staff);
        $previousAppointment->setRelation('location', $appointment->location);

        $validated = $request->validate([
            'staff_id'    => ['nullable', 'exists:staff,id'],
            'service_id'  => ['nullable', 'exists:services,id'],
            'location_id' => ['nullable', Rule::exists('locations', 'id')->where('is_active', true)],
            'start_time'  => 'nullable|date',
            'end_time'    => 'nullable|date|after:start_time',
            'status'      => 'nullable|in:pending,booked,confirmed,completed,cancelled,no_show',
            'client_id'   => 'sometimes|exists:clients,id',
            'notes'       => 'nullable|string'
        ]);

        // Status transition guard
        if (isset($validated['status']) && $validated['status'] !== $currentStatus) {
            $allowed = $this->allowedTransitions();
            $allowedNext = $allowed[$currentStatus] ?? [];
            if (!in_array($validated['status'], $allowedNext, true)) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot change appointment status from '{$currentStatus}' to '{$validated['status']}'.",
                ], 422);
            }
        }

        // If rescheduling, validate staff availability
        if ($request->has('start_time') || $request->has('end_time') || $request->has('staff_id') || $request->has('service_id') || $request->has('location_id')) {
            $startTime = $validated['start_time'] ?? $appointment->start_time;
            $endTime = $validated['end_time'] ?? $appointment->end_time;
            $staffId = $validated['staff_id'] ?? $appointment->staff_id;
            $serviceId = $validated['service_id'] ?? $appointment->service_id;

            $serviceChanged = isset($validated['service_id']) && (int) $validated['service_id'] !== (int) $appointment->service_id;
            $staffChanged = isset($validated['staff_id']) && (int) $validated['staff_id'] !== (int) $appointment->staff_id;

            $service = Service::with('category')->find($serviceId);
            $staff = Staff::find($staffId);

            if (!$service || !$staff) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected service is not available for this staff member.',
                ], 422);
            }

            // A newly selected service must be active.
            if ($serviceChanged && !$service->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected service is not available for this staff member.',
                ], 422);
            }

            // A newly selected staff member must be active.
            if ($staffChanged && !$staff->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected staff member is no longer active.',
                ], 422);
            }

            // The chosen staff/service combination must be compatible.
            if (!StaffCategoryService::categoryMatches($staff->category, $service->category?->name)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected service is not available for this staff member.',
                ], 422);
            }

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

        if (!$this->authorizeAppointmentStaffAccess((int) $appointment->staff_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to modify this appointment.'
            ], 403);
        }

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
        if (!$request->filled('first_name') && $request->filled('name')) {
            $parts = explode(' ', trim((string) $request->input('name')), 2);
            $request->merge([
                'first_name' => $parts[0] ?? '',
                'last_name' => $parts[1] ?? '',
            ]);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'gender' => 'nullable|string|in:male,female,other',
            'dob' => 'nullable|date|before:today',
            'phone' => [
                'required',
                'string',
                'max:30',
                Rule::unique('clients', 'phone'),
            ],
            'alternate_phone' => 'nullable|string|max:30',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('clients', 'email'),
            ],
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:5000',
            'is_vip' => 'nullable|boolean',
        ], [
            'phone.unique' => 'A client with this phone number already exists.',
            'email.unique' => 'A client with this email address already exists.',
            'dob.before' => 'Date of birth must be a past date.',
        ]);

        $validated['name'] = trim(($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? ''));

        $client = Client::create($validated);

        return response()->json([
            'success' => true,
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'first_name' => $client->first_name,
                'last_name' => $client->last_name,
                'email' => $client->email,
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
     * Return compact client snapshot for appointment modal
     */
    public function clientSnapshot($id)
    {
        $client = Client::withCount([
            'appointments',
            'appointments as completed_count' => fn ($q) => $q->where('status', 'completed'),
            'appointments as cancelled_count' => fn ($q) => $q->where('status', 'cancelled'),
            'appointments as no_show_count'   => fn ($q) => $q->where('status', 'no_show'),
        ])
        ->withSum(['invoices as total_invoiced' => fn ($q) => $q->where('status', '!=', 'void')], 'total_amount')
        ->withSum(['invoices as total_paid' => fn ($q) => $q->where('status', '!=', 'void')], 'paid_amount')
        ->find($id);

        if (!$client) {
            return response()->json(['success' => false, 'message' => 'Client not found'], 404);
        }

        $lastVisit = $client->appointments()
            ->where('status', 'completed')
            ->where('start_time', '<=', now())
            ->latest('start_time')
            ->value('start_time');

        $nextAppointment = $client->appointments()
            ->whereIn('status', ['pending', 'booked', 'confirmed'])
            ->where('start_time', '>=', now())
            ->oldest('start_time')
            ->value('start_time');

        $outstanding = max(0, (float) ($client->total_invoiced ?? 0) - (float) ($client->total_paid ?? 0));

        return response()->json([
            'success' => true,
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'is_vip' => (bool) $client->is_vip,
                'age' => $client->age,
                'last_visit' => $lastVisit ? Carbon::parse($lastVisit)->format('M d, Y') : null,
                'next_appointment' => $nextAppointment ? Carbon::parse($nextAppointment)->format('M d, Y g:i A') : null,
                'total_appointments' => $client->appointments_count,
                'completed_count' => $client->completed_count,
                'no_show_count' => $client->no_show_count,
                'cancelled_count' => $client->cancelled_count,
                'outstanding_amount' => $outstanding,
                'notes' => $client->notes,
            ]
        ]);
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

        if (in_array($appointmentDate, $this->holidayDates(), true)) {
            return [
                'available' => false,
                'message' => 'The clinic is closed on this date (holiday).'
            ];
        }

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
                'message' => 'Staff is not available at the selected time.'
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
                    'message' => 'Staff is not available at the selected time.'
                ];
            }
        }

        if (!$schedule) {
            return [
                'available' => false,
                'message' => 'Staff is not available at the selected time.'
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
                        'message' => 'Staff is not available at the selected time.'
                    ];
                }
            }
        }

        // Check for conflicts with existing appointments
        // Compare in UTC to match database datetime storage and avoid timezone-shift false positives.
        $queryStart = $startTime->copy();
        $queryEnd = $endTime->copy()->addMinutes(max(0, $newBufferMinutes));

        $qs = Carbon::parse($queryStart);
        $qe = Carbon::parse($queryEnd);

        $existingAppointments = Appointment::with('service')
            ->where('staff_id', $staffId)
            ->whereIn('status', ['pending', 'booked', 'confirmed'])
            ->where('start_time', '<', $qe->toDateTimeString())
            ->where('end_time', '>', $qs->copy()->subHours(12)->toDateTimeString())
            ->when($excludeAppointmentId, fn ($q) => $q->where('id', '!=', $excludeAppointmentId))
            ->get();

        foreach ($existingAppointments as $existingAppt) {
            $buffer = (int) ($existingAppt->service?->buffer_minutes ?? 0);
            $effectiveEnd = $existingAppt->end_time->copy()->addMinutes($buffer);
            if ($existingAppt->start_time->lt($qe) && $effectiveEnd->gt($qs)) {
                return [
                    'available' => false,
                    'message' => 'This time slot is already booked.'
                ];
            }
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

    private function holidayDates(): array
    {
        $value = \App\Models\BusinessSetting::where('key', 'holiday_dates')->value('value');
        if (empty($value)) {
            return [];
        }
        $dates = json_decode($value, true);
        return is_array($dates) ? array_values($dates) : [];
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
            'pending'   => '#f59e0b',
            'booked'    => '#3699ff',
            'confirmed' => '#6366f1',
            'completed' => '#1bc5bd',
            'cancelled' => '#f64e60',
            'no_show'   => '#8b5cf6',
        ];

        $appointment->loadMissing('invoice');
        $invoice = $appointment->invoice;
        $hasForms = $appointment->client_id ? \App\Models\FormRecord::where('client_id', $appointment->client_id)->exists() : false;

        return [
            'id' => $appointment->id,
            'title' => $appointment->client ? $appointment->client->name : 'Unassigned',
            'staff' => $appointment->staff ? $appointment->staff->name : 'N/A',
            'service' => $appointment->service ? $appointment->service->name : 'N/A',
            'clientName' => $appointment->client ? $appointment->client->name : 'Unassigned',
            'staffName' => $appointment->staff ? $appointment->staff->name : 'N/A',
            'serviceName' => $appointment->service ? $appointment->service->name : 'N/A',
            'start' => $appointment->start_time->toIso8601String(),
            'end' => $appointment->end_time->toIso8601String(),
            'status' => $appointment->status ?? 'booked',
            'staffId' => $appointment->staff_id,
            'serviceId' => $appointment->service_id,
            'clientId' => $appointment->client_id,
            'locationId' => $appointment->location_id,
            'location' => $appointment->location ? $appointment->location->name : null,
            'locationName' => $appointment->location ? $appointment->location->name : null,
            'hasClient' => !is_null($appointment->client_id),
            'color' => $statusColorMap[$appointment->status ?? 'booked'] ?? '#3699ff',
            'notes' => $appointment->notes,
            'invoiceId' => $invoice?->id,
            'invoiceNumber' => $invoice?->invoice_number,
            'hasForms' => $hasForms,
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

    private function authorizeAppointmentStaffAccess(int $staffId): bool
    {
        $user = \Illuminate\Support\Facades\Auth::guard('staff')->user();
        if (!$user) {
            return false;
        }

        if (in_array($user->access_level, ['admin', 'business_owner', 'receptionist'], true)) {
            return true;
        }

        return (int) $user->id === $staffId;
    }

    /**
     * Centralized status transition policy.
     * Returns a map of current_status => allowed_next_statuses.
     * Terminal statuses (completed, cancelled, no_show) have no allowed outbound transitions.
     */
    private function allowedTransitions(): array
    {
        return [
            'pending'   => ['booked', 'confirmed', 'cancelled', 'no_show'],
            'booked'    => ['confirmed', 'cancelled', 'no_show'],
            'confirmed' => ['completed', 'cancelled', 'no_show'],
            'completed' => [],
            'cancelled' => [],
            'no_show'   => [],
        ];
    }
}
