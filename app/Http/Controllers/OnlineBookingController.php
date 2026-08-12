<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Location;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Services\AppointmentEmailService;
use App\Support\StaffCategoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OnlineBookingController extends Controller
{
    public function __construct(private AppointmentEmailService $appointmentEmailService)
    {
    }

    public function index()
    {
        $locations = Location::where('is_active', true)->orderBy('name')->get();
        $services = Service::where('is_active', true)
            ->with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'duration_minutes', 'service_category_id']);
        $staff = Staff::where('is_active', true)->orderBy('name')->get(['id', 'name', 'category', 'location_id']);
        return view('online_booking.index', compact('locations', 'services', 'staff'));
    }

    public function slots(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date|after_or_equal:today',
            'staff_id' => 'nullable|exists:staff,id',
            'location_id' => 'nullable|exists:locations,id',
        ]);

        $service = Service::with('category')->where('is_active', true)->findOrFail($validated['service_id']);
        $staffQuery = Staff::where('is_active', true);
        if (!empty($validated['staff_id'])) {
            $staffQuery->where('id', $validated['staff_id']);
        }
        if (!empty($validated['location_id'])) {
            $staffQuery->where('location_id', $validated['location_id']);
        }

        $staffQuery = StaffCategoryService::scopeStaffByCategory($staffQuery, $service->category?->name);

        $date = Carbon::parse($validated['date']);
        $slots = [];
        foreach ($staffQuery->get() as $member) {
            foreach ($this->staffWorkingWindows($member->id, $date) as $window) {
                $cursor = Carbon::parse($date->toDateString() . ' ' . $window->start_time);
                $end = Carbon::parse($date->toDateString() . ' ' . $window->end_time);
                while ($cursor->copy()->addMinutes($service->duration_minutes)->lte($end)) {
                    $slotEnd = $cursor->copy()->addMinutes($service->duration_minutes);
                    if ($cursor->gt(now()) && $this->isAvailable($member->id, $cursor, $slotEnd, $window, (int) ($service->buffer_minutes ?? 0))) {
                        $slots[] = [
                            'staff_id' => $member->id,
                            'staff_name' => $member->name,
                            'start' => $cursor->format('Y-m-d\TH:i:s'),
                            'end' => $slotEnd->format('Y-m-d\TH:i:s'),
                            'label' => $cursor->format('g:i A') . ' with ' . $member->name,
                        ];
                    }
                    $cursor->addMinutes((int) (\App\Models\BusinessSetting::where('key', 'appointment_interval')->value('value') ?: 30));
                }
            }
        }

        return response()->json($slots);
    }

    public function store(Request $request)
    {
        if (!\App\Models\Subscription::checkLimit('appointment')) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'start_time' => 'Clinic booking limits reached for this month. Please contact the clinic directly.'
            ]);
        }

        $validated = $request->validate([
            'location_id' => 'nullable|exists:locations,id',
            'service_id' => ['required', \Illuminate\Validation\Rule::exists('services', 'id')->where('is_active', true)],
            'staff_id' => ['required', \Illuminate\Validation\Rule::exists('staff', 'id')->where('is_active', true)],
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'client_name' => 'required|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'client_phone' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $appointment = DB::transaction(function () use ($validated) {
                $client = null;
                if (!empty($validated['client_email']) || !empty($validated['client_phone'])) {
                    $client = Client::where(function ($query) use ($validated) {
                        if (!empty($validated['client_email'])) {
                            $query->orWhere('email', $validated['client_email']);
                        }
                        if (!empty($validated['client_phone'])) {
                            $query->orWhere('phone', $validated['client_phone']);
                        }
                    })->first();
                }

                if (!$client) {
                    $client = Client::create([
                        'name' => $validated['client_name'],
                        'email' => $validated['client_email'] ?? null,
                        'phone' => $validated['client_phone'] ?? null,
                        'client_since' => now()->toDateString(),
                    ]);
                }

                $start = Carbon::parse($validated['start_time']);
                $end = Carbon::parse($validated['end_time']);
                $windows = $this->staffWorkingWindows($validated['staff_id'], $start);
                $service = Service::with('category')->where('is_active', true)->findOrFail($validated['service_id']);
                $staff = Staff::where('is_active', true)->findOrFail($validated['staff_id']);

                if (!StaffCategoryService::staffCanProvide($staff, $service)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'staff_id' => 'The selected service is not available for this staff member.',
                    ]);
                }

                $window = $windows->first(fn($item) => $start->gte(Carbon::parse($start->toDateString() . ' ' . $item->start_time)) && $end->lte(Carbon::parse($start->toDateString() . ' ' . $item->end_time)));
                if (!$window || !$this->isAvailable($validated['staff_id'], $start, $end, $window, (int) ($service->buffer_minutes ?? 0))) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['start_time' => 'Selected slot is no longer available.']);
                }

                return Appointment::create([
                    'client_id' => $client->id,
                    'staff_id' => $validated['staff_id'],
                    'service_id' => $validated['service_id'],
                    'location_id' => $validated['location_id'] ?? null,
                    'start_time' => $start,
                    'end_time' => $end,
                    'status' => \App\Models\BusinessSetting::where('key', 'default_appointment_status')->value('value') ?: 'pending',
                    'notes' => $validated['notes'] ?? null,
                ]);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }

        $appointment->load(['client', 'staff', 'service', 'location']);
        $emailResult = $this->appointmentEmailService->sendBooked($appointment);
        $message = 'Booking confirmed. ' . (($emailResult['attempted'] ?? false)
            ? (($emailResult['sent'] ?? false) ? 'Confirmation email sent.' : 'Confirmation email could not be sent.')
            : '');

        return redirect()->route('online-booking.confirmation', $appointment->id)->with('success', trim($message));
    }

    public function confirmation(string $id)
    {
        $appointment = Appointment::with(['client', 'staff', 'service', 'location'])->findOrFail($id);
        return view('online_booking.confirmation', compact('appointment'));
    }

    private function staffWorkingWindows(int $staffId, Carbon $date)
    {
        if ($this->isHoliday($date)) {
            return collect();
        }

        $dayIndex = $date->dayOfWeekIso - 1;

        // Date-specific rows (working or day-off) override weekly templates.
        $dateSpecific = StaffSchedule::where('staff_id', $staffId)->whereDate('working_date', $date)->get();
        if ($dateSpecific->isNotEmpty()) {
            return $dateSpecific->where('is_working', true)->values();
        }

        return StaffSchedule::where('staff_id', $staffId)
            ->whereNull('working_date')
            ->where(function ($query) use ($dayIndex, $date) {
                $query->where('day_of_week', (string) $dayIndex)->orWhere('day_of_week', strtolower($date->format('l')));
            })
            ->where('is_working', true)
            ->get();
    }

    private function isHoliday(Carbon $date): bool
    {
        $holidays = \App\Models\BusinessSetting::where('key', 'holiday_dates')->value('value');
        if (empty($holidays)) {
            return false;
        }
        $dates = json_decode($holidays, true);
        return is_array($dates) && in_array($date->toDateString(), $dates, true);
    }

    private function isAvailable(int $staffId, Carbon $start, Carbon $end, StaffSchedule $schedule, int $newBufferMinutes = 0): bool
    {
        foreach (($schedule->breaks ?? []) as $break) {
            $breakStart = Carbon::parse($start->toDateString() . ' ' . $break['start']);
            $breakEnd = Carbon::parse($start->toDateString() . ' ' . $break['end']);
            if ($start->lt($breakEnd) && $end->gt($breakStart)) {
                return false;
            }
        }

        $queryStart = $start->copy();
        $queryEnd = $end->copy()->addMinutes(max(0, $newBufferMinutes));

        return !Appointment::where('staff_id', $staffId)
            ->whereIn('status', ['pending', 'booked', 'confirmed'])
            ->where(function ($q) use ($queryStart, $queryEnd) {
                $q->where('start_time', '<', $queryEnd)
                    ->whereRaw('DATE_ADD(end_time, INTERVAL COALESCE((SELECT buffer_minutes FROM services WHERE services.id = appointments.service_id), 0) MINUTE) > ?', [$queryStart]);
            })
            ->exists();
    }
}
