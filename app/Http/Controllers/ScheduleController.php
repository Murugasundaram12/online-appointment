<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleRequest;
use App\Models\Location;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ScheduleController extends Controller
{
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
        $currentStaff = $currentStaffId ? $staff->find($currentStaffId) : $staff->first();
        $weekStart = $start;
        $weekEnd = $end;

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
            'statusFilter'
        ));
    }

    public function create(Request $request)
    {
        $staffId = $request->query('staff_id');
        $staff = Staff::all();
        $selectedStaff = $staffId ? Staff::find($staffId) : $staff->first();

        return view('schedule.create', compact('staff', 'selectedStaff'));
    }

    public function store(StoreScheduleRequest $request)
    {
        $validated = $request->validated();
        $staffId = $validated['staff_id'];
        $staff = Staff::findOrFail($staffId);
        $recurrenceType = $validated['recurrence_type'];
        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];

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

        // Validate overlapping schedules across all target dates (date-specific
        // entries and recurring day-of-week templates are both checked)
        foreach ($targetDates as $date) {
            $dateStr = $date->toDateString();
            $templateDay = (string) (($date->dayOfWeek + 6) % 7); // 0=Mon ... 6=Sun
            $templateName = strtolower($date->format('l'));

            $overlap = StaffSchedule::where('staff_id', $staffId)
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
                })
                ->first();

            if ($overlap) {
                return back()
                    ->withInput()
                    ->with('error', "Overlapping schedule detected for {$staff->name} on {$date->format('d-m-Y')} ({$overlap->start_time} - {$overlap->end_time}). Please adjust the time range.");
            }
        }

        $groupId = (string) Str::uuid();
        $createdCount = 0;

        foreach ($targetDates as $date) {
            $dayIndex = $date->dayOfWeek; // 0=Sun, 1=Mon, ..., 6=Sat

            StaffSchedule::create([
                'staff_id' => $staffId,
                'working_date' => $date->toDateString(),
                'day_of_week' => (string) $dayIndex,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'is_working' => true,
                'recurrence_type' => $recurrenceType,
                'recurrence_days' => $validated['weekly_days'] ?? null,
                'start_date' => $validated['start_date'] ?? $date->toDateString(),
                'end_date' => $validated['end_date'] ?? $date->toDateString(),
                'recurrence_group_id' => $groupId,
            ]);
            $createdCount++;
        }

        return redirect()->route('schedule.index', ['staff_id' => $staffId, 'range' => 'this_month'])
            ->with('success', "Successfully created {$createdCount} schedule slot(s) for {$staff->name}.");
    }

    public function show(string $id)
    {
        return redirect()->route('schedule.index');
    }

    public function edit(string $id)
    {
        $staff = Staff::findOrFail($id);
        $schedules = StaffSchedule::where('staff_id', $staff->id)->get();
        return view('schedule.edit', compact('staff', 'schedules'));
    }

    public function update(Request $request, string $id)
    {
        $staff = Staff::findOrFail($id);

        $request->validate([
            'days' => 'required|array',
        ]);

        foreach ($request->days as $day => $data) {
            StaffSchedule::updateOrCreate(
                ['staff_id' => $staff->id, 'day_of_week' => $day],
                [
                    'is_working' => isset($data['is_working']),
                    'start_time' => $data['start_time'] ?? null,
                    'end_time' => $data['end_time'] ?? null,
                ]
            );
        }

        return redirect()->route('schedule.index', ['staff_id' => $staff->id])->with('success', 'Schedule updated successfully.');
    }

    public function destroy(string $id)
    {
        $schedule = StaffSchedule::findOrFail($id);
        $staffId = $schedule->staff_id;
        $schedule->delete();

        return redirect()->back()->with('success', 'Schedule entry deleted successfully.');
    }

    public function storeApi(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'working_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_working' => 'nullable|boolean',
        ]);

        $staffId = $validated['staff_id'];
        $workingDate = $validated['working_date'];
        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];

        $date = Carbon::parse($workingDate);
        $templateDay = (string) (($date->dayOfWeek + 6) % 7); // 0=Mon ... 6=Sun
        $templateName = strtolower($date->format('l'));

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

        $schedule = StaffSchedule::create([
            'staff_id' => $staffId,
            'working_date' => $workingDate,
            'day_of_week' => (string) $date->dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_working' => $request->has('is_working') ? (bool) $validated['is_working'] : true,
            'recurrence_type' => 'one_time',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Schedule created successfully',
            'schedule' => $schedule
        ], 201);
    }

    public function updateApi(Request $request, $id)
    {
        $schedule = StaffSchedule::findOrFail($id);

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

        $schedule->update([
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully',
            'schedule' => $schedule
        ]);
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
