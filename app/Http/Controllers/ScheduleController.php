<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleRequest;
use App\Models\BusinessSetting;
use App\Models\Location;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    private function authorizeScheduleAccess(int $staffId): bool
    {
        $user = Auth::guard('staff')->user();
        if (!$user) return false;
        if (in_array($user->access_level, ['admin', 'business_owner'])) return true;
        return (int) $user->id === $staffId;
    }
    public function index(Request $request)
    {
        $staff = Staff::with('location')->get();
        $locations = Location::where('is_active', true)->get();

        $selectedRange = $request->query('range', 'this_week');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');
        $currentStaffId = $request->query('staff_id');
        $locationId = $request->query('location_id');
        $statusFilter = $request->query('status'); // 'working', 'off', or null

        $now = Carbon::now();
        $weekDate = $request->query('week_date');
        $base = $weekDate ? Carbon::parse($weekDate) : $now;

        // Resolve date range based on filter
        switch ($selectedRange) {
            case 'today':
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                break;
            case 'tomorrow':
                $start = $now->copy()->addDay()->startOfDay();
                $end = $now->copy()->addDay()->endOfDay();
                break;
            case 'this_month':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                break;
            case 'this_year':
                $start = $now->copy()->startOfYear();
                $end = $now->copy()->endOfYear();
                break;
            case 'custom':
                $start = $fromDate ? Carbon::parse($fromDate)->startOfDay() : $now->copy()->startOfWeek();
                $end = $toDate ? Carbon::parse($toDate)->endOfDay() : $now->copy()->endOfWeek();
                break;
            case 'this_week':
            default:
                $start = $base->copy()->startOfWeek();
                $end = $base->copy()->endOfWeek();
                break;
        }

        $query = StaffSchedule::with(['staff', 'staff.location'])
            ->whereBetween('working_date', [$start->toDateString(), $end->toDateString()]);

        if ($currentStaffId) {
            $query->where('staff_id', $currentStaffId);
        }

        if ($locationId) {
            $query->whereHas('staff', function ($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
        }

        if ($statusFilter === 'working') {
            $query->where('is_working', true);
        } elseif ($statusFilter === 'off') {
            $query->where('is_working', false);
        }

        $schedules = $query->orderBy('working_date')->orderBy('start_time')->get();

        // Attach human-readable recurrence summaries (server-side, keeps Blade lean).
        $schedules = $schedules->map(function ($s) {
            $data = $s->toArray();
            $data['summary'] = $this->describeSchedule($s);
            return $data;
        });

        $currentStaff = $currentStaffId ? $staff->find($currentStaffId) : $staff->first();
        $weekStart = $start;
        $weekEnd = $end;

        $holidays = $this->holidayDates();

        return view('schedule.index', compact(
            'staff',
            'locations',
            'currentStaff',
            'schedules',
            'weekStart',
            'weekEnd',
            'selectedRange',
            'fromDate',
            'toDate',
            'currentStaffId',
            'locationId',
            'statusFilter',
            'holidays'
        ));
    }

    public function addHoliday(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
        ]);

        $date = Carbon::parse($validated['date'])->toDateString();
        $holidays = $this->holidayDates();
        if (!in_array($date, $holidays, true)) {
            $holidays[] = $date;
            sort($holidays);
            BusinessSetting::updateOrCreate(
                ['key' => 'holiday_dates'],
                ['value' => json_encode($holidays), 'group' => 'booking']
            );
        }

        return redirect()->route('schedule.index')->with('success', "Holiday added for {$date}. The clinic is closed this day.");
    }

    public function removeHoliday(string $date)
    {
        $holidays = $this->holidayDates();
        $date = Carbon::parse($date)->toDateString();
        $holidays = array_values(array_filter($holidays, fn ($d) => $d !== $date));

        if (empty($holidays)) {
            BusinessSetting::where('key', 'holiday_dates')->delete();
        } else {
            BusinessSetting::updateOrCreate(
                ['key' => 'holiday_dates'],
                ['value' => json_encode($holidays), 'group' => 'booking']
            );
        }

        return redirect()->route('schedule.index')->with('success', "Holiday removed for {$date}.");
    }

    private function holidayDates(): array
    {
        $value = BusinessSetting::where('key', 'holiday_dates')->value('value');
        if (empty($value)) {
            return [];
        }
        $dates = json_decode($value, true);
        return is_array($dates) ? array_values($dates) : [];
    }

    public function create(Request $request)
    {
        $staffId = $request->query('staff_id');
        $staff = Staff::all();
        $selectedStaff = $staffId ? Staff::find($staffId) : $staff->first();
        $editing = null;
        $schedule = null;

        return view('schedule.create', compact('staff', 'selectedStaff', 'editing', 'schedule'));
    }
// Creates staff schedule entries
    public function store(StoreScheduleRequest $request)
    {
        $validated = $request->validated();
        $staffId = $validated['staff_id'];
        if (!$this->authorizeScheduleAccess($staffId)) {
            abort(403, 'Unauthorized action.');
        }

        return DB::transaction(function () use ($validated, $staffId, $request) {
            $staff = Staff::findOrFail($staffId);
            $recurrenceType = $validated['recurrence_type'];
            $startTime = $validated['start_time'] ?? null;
            $endTime = $validated['end_time'] ?? null;

            $isEdit = !empty($validated['schedule_id']);
            $editScope = $validated['edit_scope'] ?? $request->input('edit_scope', 'group');
            $groupId = (string) Str::uuid();
            $excludeIds = [];
            $oldGroupIds = [];

            $isDayOff = !isset($validated['is_working']) ? false : !(bool) $validated['is_working'];
            if ($isDayOff && $recurrenceType !== 'one_time') {
                return back()
                    ->withInput()
                    ->with('error', 'Day off entries are date-specific. Please use "One Time" recurrence for a day off.');
            }

            if ($isEdit) {
                $original = StaffSchedule::findOrFail($validated['schedule_id']);
                if ($editScope === 'occurrence') {
                    $oldGroupIds = [$original->id];
                    $excludeIds = [$original->id];
                    $groupId = null;
                    $recurrenceType = 'one_time';
                    if (empty($validated['working_date']) && $original->working_date) {
                        $validated['working_date'] = $original->working_date->toDateString();
                    }
                } else {
                    $existingGroup = $this->resolveScheduleGroup($original);
                    $oldGroupIds = $existingGroup->pluck('id')->all();
                    if ((int) $original->staff_id === (int) $staffId) {
                        $excludeIds = $oldGroupIds;
                    }
                    $existingGroupId = $existingGroup->whereNotNull('recurrence_group_id')->first()?->recurrence_group_id;
                    if ($existingGroupId) {
                        $groupId = $existingGroupId;
                    }
                }
            }

            $targetDates = $this->calculateTargetDates(
                $recurrenceType,
                $validated['working_date'] ?? null,
                $validated['start_date'] ?? null,
                $validated['end_date'] ?? null,
                $validated['weekly_days'] ?? null,
                $validated['monthly_day'] ?? null,
                $validated['yearly_month'] ?? null,
                $validated['yearly_day'] ?? null
            );

            if (empty($targetDates)) {
                return back()
                    ->withInput()
                    ->with('error', 'No matching working dates were found in the selected date range.');
            }

            if (count($targetDates) > 366) {
                return back()
                    ->withInput()
                    ->with('error', 'The selected date range would generate more than 366 schedule entries. Please narrow the date range or choose a longer recurrence interval.');
            }

            if (!$isDayOff) {
                foreach ($targetDates as $date) {
                    $dateStr = $date->toDateString();
                    $templateDay = (string) (($date->dayOfWeek + 6) % 7);
                    $templateName = strtolower($date->format('l'));

                    $overlapQuery = StaffSchedule::where('staff_id', $staffId)
                        ->where('is_working', true)
                        ->whereNotNull('start_time')
                        ->whereNotNull('end_time')
                        ->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime)
                        ->where(function ($q) use ($dateStr, $templateDay, $templateName) {
                            $q->where('working_date', $dateStr)
                                ->orWhere(function ($w) use ($templateDay, $templateName) {
                                    $w->whereNull('working_date')
                                        ->whereIn('day_of_week', [$templateDay, $templateName]);
                                });
                        });

                    if (!empty($excludeIds)) {
                        $overlapQuery->whereNotIn('id', $excludeIds);
                    }

                    $overlap = $overlapQuery->first();

                    if ($overlap) {
                        return back()
                            ->withInput()
                            ->with('error', "Overlapping schedule detected for {$staff->name} on {$date->format('d-m-Y')} ({$overlap->start_time} - {$overlap->end_time}). Please adjust the time range.");
                    }
                }
            }

            $breaks = [];
            if (!$isDayOff && !empty($validated['break_start']) && !empty($validated['break_end'])) {
                if ($validated['break_start'] < $startTime || $validated['break_end'] > $endTime) {
                    return back()
                        ->withInput()
                        ->with('error', 'Break must be within the schedule working hours.');
                }
                $breaks[] = ['start' => $validated['break_start'], 'end' => $validated['break_end']];
            }

            if ($isEdit && !empty($oldGroupIds)) {
                $oldRows = StaffSchedule::whereIn('id', $oldGroupIds)->get();
                $targetDateStrings = collect($targetDates)->map(fn ($d) => $d->toDateString())->all();

                foreach ($oldRows as $oldRow) {
                    $oldDate = $oldRow->working_date ? $oldRow->working_date->toDateString() : null;
                    if (!$oldDate) continue;

                    if (!in_array($oldDate, $targetDateStrings, true) || $isDayOff) {
                        $conflict = $this->checkAppointmentConflictForSchedule($oldRow->staff_id, [$oldDate], null, null, null, true);
                        if ($conflict) {
                            return back()->withInput()->with('error', $conflict);
                        }
                    }
                }

                $conflict = $this->checkAppointmentConflictForSchedule($staffId, $targetDates, $startTime, $endTime, $breaks, $isDayOff);
                if ($conflict) {
                    return back()->withInput()->with('error', $conflict);
                }

                StaffSchedule::whereIn('id', $oldGroupIds)->delete();
            }

            $targetDateStrings = collect($targetDates)->map(fn ($d) => $d->toDateString())->all();
            StaffSchedule::where('staff_id', $staffId)
                ->whereIn('working_date', $targetDateStrings)
                ->where('is_working', $isDayOff)
                ->delete();

            $recurrenceDays = [
                'weekly_days' => $validated['weekly_days'] ?? null,
                'monthly_day' => $validated['monthly_day'] ?? null,
                'yearly_month' => $validated['yearly_month'] ?? null,
                'yearly_day' => $validated['yearly_day'] ?? null,
            ];

            $createdCount = 0;
            foreach ($targetDates as $date) {
                $dayIndex = $date->dayOfWeek;

                StaffSchedule::create([
                    'staff_id' => $staffId,
                    'working_date' => $date->toDateString(),
                    'day_of_week' => (string) $dayIndex,
                    'start_time' => $isDayOff ? null : $startTime,
                    'end_time' => $isDayOff ? null : $endTime,
                    'is_working' => !$isDayOff,
                    'breaks' => $breaks ?: null,
                    'recurrence_type' => $recurrenceType,
                    'recurrence_days' => $recurrenceDays,
                    'start_date' => $validated['start_date'] ?? $date->toDateString(),
                    'end_date' => $validated['end_date'] ?? $date->toDateString(),
                    'recurrence_group_id' => $groupId,
                ]);
                $createdCount++;
            }

            $message = $isDayOff
                ? "Day off saved for {$staff->name} on {$targetDates[0]->format('d-m-Y')}."
                : ($isEdit
                    ? "Schedule updated successfully."
                    : "Successfully created {$createdCount} schedule slot(s) for {$staff->name}.");

            return redirect()->route('schedule.index', ['staff_id' => $staffId, 'range' => 'this_month'])
                ->with('success', $message);
        });
    }

    public function show(string $id)
    {
        return redirect()->route('schedule.index');
    }

    public function edit(string $id)
    {
        $schedule = StaffSchedule::find($id);
        if ($schedule && !$this->authorizeScheduleAccess($schedule->staff_id)) {
            abort(403, 'Unauthorized action.');
        }

        if ($schedule) {
            $editScope = request('scope', 'group');
            if ($editScope === 'occurrence') {
                $editing = $schedule;
            } else {
                $editing = $this->resolveScheduleGroup($schedule)->sortBy('working_date')->first() ?? $schedule;
            }
            $staff = Staff::all();
            $selectedStaff = Staff::find($schedule->staff_id);

            return view('schedule.create', compact('staff', 'selectedStaff', 'editing', 'schedule', 'editScope'));
        }

        // Legacy fallback: weekly day-template editor keyed by staff id.
        $staff = Staff::findOrFail($id);
        if (!$this->authorizeScheduleAccess($staff->id)) {
            abort(403, 'Unauthorized action.');
        }
        $schedules = StaffSchedule::where('staff_id', $staff->id)->get();
        return view('schedule.edit', compact('staff', 'schedules'));
    }

    public function update(Request $request, string $id)
    {
        $staff = Staff::findOrFail($id);
        if (!$this->authorizeScheduleAccess($staff->id)) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'days' => 'required|array',
        ]);

        return DB::transaction(function () use ($request, $staff) {
            foreach ($request->days as $day => $data) {
                $isWorking = isset($data['is_working']);
                $startTime = $data['start_time'] ?? null;
                $endTime = $data['end_time'] ?? null;

                $existing = StaffSchedule::where('staff_id', $staff->id)
                    ->where('day_of_week', (string)$day)
                    ->whereNull('working_date')
                    ->first();

                if ($existing) {
                    $existing->update([
                        'is_working' => $isWorking,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                    ]);
                } elseif ($isWorking && !empty($startTime) && !empty($endTime)) {
                    $startDate = Carbon::now()->toDateString();
                    $endDate = Carbon::now()->addYear()->toDateString();
                    $groupId = (string) Str::uuid();

                    $targetDates = $this->calculateTargetDates(
                        'weekly', null, $startDate, $endDate, [(int)$day], null, null, null
                    );

                    $inserts = [];
                    $now = now();
                    foreach ($targetDates as $date) {
                        $inserts[] = [
                            'staff_id' => $staff->id,
                            'working_date' => $date->toDateString(),
                            'day_of_week' => (string) $date->dayOfWeek,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'is_working' => true,
                            'breaks' => null,
                            'recurrence_type' => 'weekly',
                            'recurrence_group_id' => $groupId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    if (!empty($inserts)) {
                        StaffSchedule::insert($inserts);
                    }
                }
            }

            return redirect()->route('schedule.index', ['staff_id' => $staff->id])->with('success', 'Schedule updated successfully.');
        });
    }

    public function destroy(string $id)
    {
        $schedule = StaffSchedule::findOrFail($id);
        if (!$this->authorizeScheduleAccess($schedule->staff_id)) {
            abort(403, 'Unauthorized action.');
        }
        $scope = request('scope', request('delete_mode', 'group'));

        if ($scope === 'occurrence') {
            $dateStr = $schedule->working_date ? $schedule->working_date->toDateString() : null;
            if ($dateStr && !empty($schedule->start_time) && !empty($schedule->end_time)) {
                $hasAppointment = \App\Models\Appointment::where('staff_id', $schedule->staff_id)
                    ->whereIn('status', ['booked', 'confirmed', 'pending'])
                    ->whereDate('start_time', $dateStr)
                    ->where('start_time', '<', $dateStr . ' ' . $schedule->end_time)
                    ->where('end_time', '>', $dateStr . ' ' . $schedule->start_time)
                    ->exists();

                if ($hasAppointment) {
                    return redirect()->back()->with('error', 'This schedule cannot be removed because appointments already exist during this time.');
                }
            }

            return DB::transaction(function () use ($schedule) {
                $schedule->delete();
                return redirect()->back()->with('success', 'Schedule deleted successfully.');
            });
        }

        if ($scope === 'skip') {
            $dateStr = $schedule->working_date ? $schedule->working_date->toDateString() : null;
            if ($dateStr && !empty($schedule->start_time) && !empty($schedule->end_time)) {
                $hasAppointment = \App\Models\Appointment::where('staff_id', $schedule->staff_id)
                    ->whereIn('status', ['booked', 'confirmed', 'pending'])
                    ->whereDate('start_time', $dateStr)
                    ->where('start_time', '<', $dateStr . ' ' . $schedule->end_time)
                    ->where('end_time', '>', $dateStr . ' ' . $schedule->start_time)
                    ->exists();

                if ($hasAppointment) {
                    return redirect()->back()->with('error', 'This schedule cannot be removed because appointments already exist during this time.');
                }
            }

            return DB::transaction(function () use ($schedule) {
                $schedule->update([
                    'is_working' => false,
                    'start_time' => null,
                    'end_time' => null,
                    'breaks' => null,
                    'recurrence_type' => 'one_time',
                    'recurrence_group_id' => null,
                ]);

                return redirect()->back()->with('success', 'Schedule updated successfully.');
            });
        }

        $groupRows = $this->resolveScheduleGroup($schedule);
        foreach ($groupRows as $row) {
            $dateStr = $row->working_date ? $row->working_date->toDateString() : null;
            if ($dateStr && !empty($row->start_time) && !empty($row->end_time)) {
                $hasAppointment = \App\Models\Appointment::where('staff_id', $row->staff_id)
                    ->whereIn('status', ['booked', 'confirmed', 'pending'])
                    ->whereDate('start_time', $dateStr)
                    ->where('start_time', '<', $dateStr . ' ' . $row->end_time)
                    ->where('end_time', '>', $dateStr . ' ' . $row->start_time)
                    ->exists();

                if ($hasAppointment) {
                    return redirect()->back()->with('error', 'This schedule cannot be removed because appointments already exist during this time.');
                }
            }
        }

        return DB::transaction(function () use ($schedule) {
            if (!empty($schedule->recurrence_group_id)) {
                StaffSchedule::where('recurrence_group_id', $schedule->recurrence_group_id)->delete();
            } else {
                $schedule->delete();
            }

            return redirect()->back()->with('success', 'Schedule deleted successfully.');
        });
    }

    private function checkAppointmentConflictForSchedule(
        int $staffId,
        array $targetDates,
        ?string $newStartTime,
        ?string $newEndTime,
        ?array $newBreaks,
        bool $isDayOff = false
    ): ?string {
        foreach ($targetDates as $d) {
            $dateStr = $d instanceof \Carbon\Carbon ? $d->toDateString() : (string) $d;

            $appointments = \App\Models\Appointment::where('staff_id', $staffId)
                ->whereIn('status', ['booked', 'confirmed', 'pending'])
                ->whereDate('start_time', $dateStr)
                ->get();

            if ($appointments->isEmpty()) {
                continue;
            }

            if ($isDayOff || empty($newStartTime) || empty($newEndTime)) {
                return $isDayOff
                    ? 'Day off cannot be created because appointments already exist on this date.'
                    : 'This schedule cannot be removed because appointments already exist during this time.';
            }

            $workStart = \Carbon\Carbon::parse($dateStr . ' ' . $newStartTime);
            $workEnd = \Carbon\Carbon::parse($dateStr . ' ' . $newEndTime);

            foreach ($appointments as $apt) {
                $aptStart = \Carbon\Carbon::parse($apt->start_time);
                $aptEnd = \Carbon\Carbon::parse($apt->end_time);

                if ($aptStart->lt($workStart) || $aptEnd->gt($workEnd)) {
                    return 'This change conflicts with an existing appointment.';
                }

                if (!empty($newBreaks)) {
                    foreach ($newBreaks as $b) {
                        $bStart = \Carbon\Carbon::parse($dateStr . ' ' . $b['start']);
                        $bEnd = \Carbon\Carbon::parse($dateStr . ' ' . $b['end']);

                        if ($aptStart->lt($bEnd) && $aptEnd->gt($bStart)) {
                            return 'This change conflicts with an existing appointment.';
                        }
                    }
                }
            }
        }

        return null;
    }

    public function storeApi(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'working_date' => 'required|date|after_or_equal:today',
            'is_working' => 'nullable|boolean',
            'start_time' => 'required_unless:is_working,0|nullable|date_format:H:i',
            'end_time' => 'required_unless:is_working,0|nullable|date_format:H:i|after:start_time',
        ], [
            'working_date.after_or_equal' => 'Staff schedules cannot be created for past dates.',
        ]);

        $staffId = $validated['staff_id'];
        if (!$this->authorizeScheduleAccess($staffId)) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }
        $workingDate = $validated['working_date'];
        $isDayOff = $request->has('is_working') ? !(bool)$validated['is_working'] : false;
        $startTime = $isDayOff ? null : $validated['start_time'];
        $endTime = $isDayOff ? null : $validated['end_time'];

        $conflict = $this->checkAppointmentConflictForSchedule(
            $staffId,
            [$workingDate],
            $startTime,
            $endTime,
            null,
            $isDayOff
        );
        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => $conflict
            ], 422);
        }

        $date = Carbon::parse($workingDate);
        $templateDay = (string) (($date->dayOfWeek + 6) % 7); // 0=Mon ... 6=Sun
        $templateName = strtolower($date->format('l'));

        if (!$isDayOff) {
            $existingOverlap = StaffSchedule::where('staff_id', $staffId)
                ->where('is_working', true)
                ->whereNotNull('start_time')
                ->whereNotNull('end_time')
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime)
                ->where(function ($q) use ($workingDate, $templateDay, $templateName) {
                    $q->where('working_date', $workingDate)
                        ->orWhere(function ($w) use ($templateDay, $templateName) {
                            $w->whereNull('working_date')
                                ->whereIn('day_of_week', [$templateDay, $templateName]);
                        });
                })
                ->exists();

            if ($existingOverlap) {
                return response()->json([
                    'success' => false,
                    'message' => 'Schedule overlaps with an existing schedule for this date.'
                ], 422);
            }
        }

        return DB::transaction(function () use ($staffId, $workingDate, $isDayOff, $startTime, $endTime, $date) {
            if ($isDayOff) {
                StaffSchedule::where('staff_id', $staffId)
                    ->where('working_date', $workingDate)
                    ->where('is_working', true)
                    ->delete();
            } else {
                StaffSchedule::where('staff_id', $staffId)
                    ->where('working_date', $workingDate)
                    ->where('is_working', false)
                    ->delete();
            }

            $schedule = StaffSchedule::create([
                'staff_id' => $staffId,
                'working_date' => $workingDate,
                'day_of_week' => (string) $date->dayOfWeek,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'is_working' => !$isDayOff,
                'recurrence_type' => 'one_time',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Schedule created successfully',
                'schedule' => $schedule
            ], 201);
        });
    }

    public function updateApi(Request $request, $id)
    {
        $schedule = StaffSchedule::findOrFail($id);
        if (!$this->authorizeScheduleAccess($schedule->staff_id)) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        if (!empty($schedule->working_date) && Carbon::parse($schedule->working_date)->lt(Carbon::today())) {
            return response()->json([
                'success' => false,
                'message' => 'Historical staff schedules cannot be modified.'
            ], 422);
        }

        $validated = $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time'
        ]);

        if (!empty($schedule->working_date)) {
            $date = Carbon::parse($schedule->working_date);
            $templateDay = (string) (($date->dayOfWeek + 6) % 7); // 0=Mon ... 6=Sun
            $templateName = strtolower($date->format('l'));
            $dateStr = $date->toDateString();

            $existingOverlap = StaffSchedule::where('staff_id', $schedule->staff_id)
                ->where('is_working', true)
                ->whereNotNull('start_time')
                ->whereNotNull('end_time')
                ->where('start_time', '<', $validated['end_time'])
                ->where('end_time', '>', $validated['start_time'])
                ->where('id', '!=', $schedule->id)
                ->where(function ($q) use ($dateStr, $templateDay, $templateName) {
                    $q->where('working_date', $dateStr)
                        ->orWhere(function ($w) use ($templateDay, $templateName) {
                            $w->whereNull('working_date')
                                ->whereIn('day_of_week', [$templateDay, $templateName]);
                        });
                })
                ->exists();

            if ($existingOverlap) {
                return response()->json([
                    'success' => false,
                    'message' => 'Schedule overlaps with an existing schedule for this date.'
                ], 422);
            }
        }

        if (!empty($schedule->working_date)) {
            $conflict = $this->checkAppointmentConflictForSchedule(
                $schedule->staff_id,
                [$schedule->working_date],
                $validated['start_time'],
                $validated['end_time'],
                $schedule->breaks
            );

            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'message' => $conflict
                ], 422);
            }
        }

        return DB::transaction(function () use ($schedule, $validated) {
            $schedule->update([
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Schedule updated successfully',
                'schedule' => $schedule
            ]);
        });
    }

    private function resolveScheduleGroup(StaffSchedule $schedule)
    {
        if (!empty($schedule->recurrence_group_id)) {
            $rows = StaffSchedule::where('recurrence_group_id', $schedule->recurrence_group_id)
                ->orderBy('working_date')
                ->get();

            if ($rows->isNotEmpty()) {
                return $rows;
            }
        }

        return collect([$schedule]);
    }

    /**
     * Human-readable recurrence description used by the schedule listing.
     * Recurrence metadata lives in recurrence_days (assoc array for new rows,
     * legacy plain arrays of weekday ints are still understood).
     */
    private function describeSchedule(StaffSchedule $s): array
    {
        $typeLabels = [
            'one_time' => 'One Time',
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'yearly' => 'Yearly',
        ];
        $weekdayNames = [
            '0' => 'Sunday', '1' => 'Monday', '2' => 'Tuesday', '3' => 'Wednesday',
            '4' => 'Thursday', '5' => 'Friday', '6' => 'Saturday',
            'sunday' => 'Sunday', 'monday' => 'Monday', 'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday', 'thursday' => 'Thursday', 'friday' => 'Friday',
            'saturday' => 'Saturday',
        ];
        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        $type = $s->recurrence_type ?: 'one_time';

        $weeklyDays = null;
        $monthlyDay = null;
        $yearlyMonth = null;
        $yearlyDay = null;

        if (is_array($s->recurrence_days)) {
            if (array_key_exists('weekly_days', $s->recurrence_days)) {
                $weeklyDays = $s->recurrence_days['weekly_days'];
                $monthlyDay = $s->recurrence_days['monthly_day'] ?? null;
                $yearlyMonth = $s->recurrence_days['yearly_month'] ?? null;
                $yearlyDay = $s->recurrence_days['yearly_day'] ?? null;
            } elseif (!empty($s->recurrence_days)) {
                $weeklyDays = array_values($s->recurrence_days);
            }
        }

        switch ($type) {
            case 'one_time':
                $recurrence = 'Specific date';
                break;
            case 'daily':
                $recurrence = 'Every day';
                break;
            case 'weekly':
                if (is_array($weeklyDays) && count($weeklyDays)) {
                    $names = collect($weeklyDays)
                        ->map(fn ($d) => $weekdayNames[(string) (((int) $d) % 7)] ?? 'Day ' . $d)
                        ->join(' + ');
                    $recurrence = 'Every ' . $names;
                } else {
                    $recurrence = 'Every ' . ($weekdayNames[(string) $s->day_of_week] ?? $s->day_of_week);
                }
                break;
            case 'monthly':
                $day = $monthlyDay ?: ($s->working_date ? $s->working_date->day : null);
                $recurrence = 'Monthly on the ' . $day . $this->ordinalSuffix((int) $day);
                break;
            case 'yearly':
                $month = $yearlyMonth ?: ($s->working_date ? $s->working_date->month : null);
                $day = $yearlyDay ?: ($s->working_date ? $s->working_date->day : null);
                $recurrence = 'Yearly on ' . ($monthNames[(int) $month] ?? $month) . ' ' . $day . $this->ordinalSuffix((int) $day);
                break;
            default:
                $recurrence = ucfirst($type);
        }

        return [
            'type' => $typeLabels[$type] ?? ucfirst($type),
            'recurrence' => $recurrence,
        ];
    }

    private function ordinalSuffix(int $num): string
    {
        if (!in_array(($num % 100), [11, 12, 13])) {
            switch ($num % 10) {
                case 1:  return 'st';
                case 2:  return 'nd';
                case 3:  return 'rd';
            }
        }
        return 'th';
    }

    private function calculateTargetDates(
        string $recurrenceType,
        ?string $workingDate,
        ?string $startDate,
        ?string $endDate,
        ?array $weeklyDays,
        ?int $monthlyDay,
        ?int $yearlyMonth,
        ?int $yearlyDay
    ): array {
        $dates = [];

        if ($recurrenceType === 'one_time') {
            if ($workingDate) {
                $dates[] = Carbon::parse($workingDate);
            }
            return $dates;
        }

        if (!$startDate || !$endDate) {
            return $dates;
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($start->gt($end)) {
            return $dates;
        }

        if ($recurrenceType === 'daily') {
            $curr = $start->copy();
            while ($curr->lte($end)) {
                $dates[] = $curr->copy();
                $curr->addDay();
            }
        } elseif ($recurrenceType === 'weekly') {
            $weeklyDays = array_map('intval', (array) $weeklyDays);
            $curr = $start->copy();
            while ($curr->lte($end)) {
                if (in_array($curr->dayOfWeek, $weeklyDays, true)) {
                    $dates[] = $curr->copy();
                }
                $curr->addDay();
            }
        } elseif ($recurrenceType === 'monthly') {
            $targetDay = (int) $monthlyDay;
            $curr = $start->copy()->startOfMonth();
            $endMonth = $end->copy()->endOfMonth();

            while ($curr->lte($endMonth)) {
                $daysInMonth = $curr->daysInMonth;
                $dayToUse = min($targetDay, $daysInMonth);
                $candidate = $curr->copy()->day($dayToUse);

                if ($candidate->gte($start) && $candidate->lte($end)) {
                    $dates[] = $candidate;
                }
                $curr->addMonth();
            }
        } elseif ($recurrenceType === 'yearly') {
            $targetMonth = (int) $yearlyMonth;
            $targetDay = (int) $yearlyDay;
            $startYear = $start->year;
            $endYear = $end->year;

            for ($yr = $startYear; $yr <= $endYear; $yr++) {
                try {
                    $candidate = Carbon::create($yr, $targetMonth, 1);
                    $daysInMonth = $candidate->daysInMonth;
                    $candidate->day(min($targetDay, $daysInMonth));

                    if ($candidate->gte($start) && $candidate->lte($end)) {
                        $dates[] = $candidate;
                    }
                } catch (\Throwable $e) {
                    // ignore invalid date
                }
            }
        }

        return $dates;
    }
}
