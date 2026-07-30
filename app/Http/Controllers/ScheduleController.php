<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $staff = \App\Models\Staff::all();
        $currentStaffId = $request->query('staff_id');
        $currentStaff = $currentStaffId ? $staff->find($currentStaffId) : $staff->first();

        $schedules = $currentStaff ? \App\Models\StaffSchedule::where('staff_id', $currentStaff->id)->get() : collect();

        $weekDate = $request->query('week_date');
        try {
            $base = $weekDate ? Carbon::parse($weekDate) : now();
        } catch (\Exception $e) {
            $base = now();
        }
        $weekStart = $base->copy()->startOfWeek();
        $weekEnd = $base->copy()->endOfWeek();

        return view('schedule.index', compact('staff', 'currentStaff', 'schedules', 'weekStart', 'weekEnd'));
    }

    public function create(Request $request)
    {
        $staffId = $request->query('staff_id');
        if (!$staffId) {
            return redirect()->route('schedule.index')->with('error', 'Please select a staff member first.');
        }
        $staff = \App\Models\Staff::findOrFail($staffId);
        return view('schedule.create', compact('staff'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'working_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $staffId = $validated['staff_id'];
        $date = Carbon::parse($validated['working_date']);
        $dayIndex = $date->dayOfWeekIso - 1; // Monday=0 ... Sunday=6

        if (!Schema::hasColumn('staff_schedules', 'working_date')) {
            // Fallback: DB doesn't have working_date column yet. Save as weekly schedule by day_of_week.
            $existingExact = \App\Models\StaffSchedule::where('staff_id', $staffId)
                ->where('day_of_week', (string) $dayIndex)
                ->where('is_working', true)
                ->where('start_time', $validated['start_time'])
                ->where('end_time', $validated['end_time'])
                ->exists();

            if ($existingExact) {
                return back()
                    ->withInput()
                    ->with('error', 'This staff already has a schedule for the selected day and time.');
            }

            \App\Models\StaffSchedule::updateOrCreate(
                ['staff_id' => $staffId, 'day_of_week' => $dayIndex !== null ? (int) $dayIndex : null],
                [
                    'is_working' => true,
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                ]
            );

            return redirect()->route('schedule.index', ['staff_id' => $staffId])
                ->with('success', 'Schedule created successfully.');
        }

        $existingExact = \App\Models\StaffSchedule::where('staff_id', $staffId)
            ->where('working_date', $validated['working_date'])
            ->where('is_working', true)
            ->where('start_time', $validated['start_time'])
            ->where('end_time', $validated['end_time'])
            ->exists();

        if ($existingExact) {
            return back()
                ->withInput()
                ->with('error', 'This staff already has a schedule for the selected date and time.');
        }

        $existingOverlap = \App\Models\StaffSchedule::where('staff_id', $staffId)
            ->where('working_date', $validated['working_date'])
            ->where('is_working', true)
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->where('start_time', '<', $validated['end_time'])
            ->where('end_time', '>', $validated['start_time'])
            ->exists();

        if ($existingOverlap) {
            return back()
                ->withInput()
                ->with('error', 'This staff already has a schedule that overlaps the selected time.');
        }

        \App\Models\StaffSchedule::create([
            'staff_id' => $staffId,
            'working_date' => $validated['working_date'],
            'day_of_week' => $dayIndex !== null ? (int) $dayIndex : null,
            'is_working' => true,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
        ]);

        return redirect()->route('schedule.index', ['staff_id' => $staffId, 'week_date' => $validated['working_date']])
            ->with('success', 'Schedule created successfully.');
    }

    public function show(string $id)
    {
        // Not really used in this UI flow, but we can redirect or show details
        return redirect()->route('schedule.index');
    }

    public function edit(string $id)
    {
        // The ID passed here might be ambiguous.
        // If the route is resource /schedule/{id}/edit, usually {id} is the schedule ID.
        // But our UI seems to edit "Schedule for a Staff".
        // Let's assume the ID passed is the STAFF ID because the view says "Edit Schedule for {{ $staff->name }}".
        // And the form loops through all days.

        $staff = \App\Models\Staff::findOrFail($id);
        $schedules = \App\Models\StaffSchedule::where('staff_id', $staff->id)->get();
        return view('schedule.edit', compact('staff', 'schedules'));
    }

    public function update(Request $request, string $id)
    {
        // ID is Staff ID here based on edit method
        $staff = \App\Models\Staff::findOrFail($id);

        $request->validate([
            'days' => 'required|array',
        ]);

        foreach ($request->days as $day => $data) {
            \App\Models\StaffSchedule::updateOrCreate(
                ['staff_id' => $staff->id, 'day_of_week' => $day],
                [
                    'is_working' => isset($data['is_working']),
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                ]
            );
        }

        return redirect()->route('schedule.index', ['staff_id' => $staff->id])->with('success', 'Schedule updated successfully.');
    }

    public function destroy(string $id)
    {
        // This would delete a specific schedule entry, but our UI manages them in bulk.
        // Maybe clear all schedules for a staff?
        \App\Models\StaffSchedule::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Schedule deleted successfully.');
    }

    /**
     * API: Create or update schedule
     */
    public function storeApi(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'day_of_week' => 'nullable|numeric|min:0|max:6',
            'working_date' => 'nullable|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_working' => 'nullable|boolean'
        ]);

        if (!$request->filled('working_date') && !$request->filled('day_of_week')) {
            return response()->json([
                'success' => false,
                'message' => 'Either working_date or day_of_week is required'
            ], 422);
        }

        try {
            $hasWorkingDate = Schema::hasColumn('staff_schedules', 'working_date');

            // Fallback for older DB schema: ignore working_date and store as weekly schedule by day_of_week.
            if (!$hasWorkingDate && !empty($validated['working_date'])) {
                $dayIndex = $validated['day_of_week'] ?? (Carbon::parse($validated['working_date'])->dayOfWeekIso - 1);

                $schedule = \App\Models\StaffSchedule::updateOrCreate(
                    ['staff_id' => $validated['staff_id'], 'day_of_week' => (string) $dayIndex],
                    [
                        'start_time' => $validated['start_time'],
                        'end_time' => $validated['end_time'],
                        'is_working' => $request->has('is_working') ? $validated['is_working'] : true
                    ]
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Schedule created successfully',
                    'schedule' => $schedule
                ], 201);
            }

            // If working_date is provided, allow multiple schedules per date,
            // but prevent duplicate/overlapping time ranges.
            if (!empty($validated['working_date'])) {
                $staffId = $validated['staff_id'];
                $workingDate = $validated['working_date'];

                $existingExact = \App\Models\StaffSchedule::where('staff_id', $staffId)
                    ->where('working_date', $workingDate)
                    ->where('is_working', true)
                    ->where('start_time', $validated['start_time'])
                    ->where('end_time', $validated['end_time'])
                    ->exists();

                if ($existingExact) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Schedule already exists for this date and time.'
                    ], 422);
                }

                $existingOverlap = \App\Models\StaffSchedule::where('staff_id', $staffId)
                    ->where('working_date', $workingDate)
                    ->where('is_working', true)
                    ->whereNotNull('start_time')
                    ->whereNotNull('end_time')
                    ->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time'])
                    ->exists();

                if ($existingOverlap) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Schedule overlaps with an existing schedule for this date.'
                    ], 422);
                }

                $dayIndex = null;
                try {
                    $dayIndex = Carbon::parse($workingDate)->dayOfWeekIso - 1;
                } catch (\Exception $e) {
                    $dayIndex = $validated['day_of_week'] ?? null;
                }

                $schedule = \App\Models\StaffSchedule::create([
                    'staff_id' => $staffId,
                    'working_date' => $workingDate,
                    'day_of_week' => $dayIndex !== null ? (int) $dayIndex : null,
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'is_working' => $request->has('is_working') ? $validated['is_working'] : true,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Schedule created successfully',
                    'schedule' => $schedule
                ], 201);
            }

            // For weekly template schedules (no working_date), keep 1 record per day_of_week.
            $schedule = \App\Models\StaffSchedule::updateOrCreate(
                ['staff_id' => $validated['staff_id'], 'day_of_week' => $validated['day_of_week'] ? (int)$validated['day_of_week'] : null],
                [
                    'working_date' => null,
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'is_working' => $request->has('is_working') ? $validated['is_working'] : true
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Schedule created successfully',
                'schedule' => $schedule
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Update schedule
     */
    public function updateApi(Request $request, $id)
    {
        try {
            $schedule = \App\Models\StaffSchedule::findOrFail($id);

            $validated = $request->validate([
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time'
            ]);

            if (!empty($schedule->working_date)) {
                $existingExact = \App\Models\StaffSchedule::where('staff_id', $schedule->staff_id)
                    ->where('working_date', $schedule->working_date->toDateString())
                    ->where('is_working', true)
                    ->where('start_time', $validated['start_time'])
                    ->where('end_time', $validated['end_time'])
                    ->where('id', '!=', $schedule->id)
                    ->exists();

                if ($existingExact) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Schedule already exists for this date and time.'
                    ], 422);
                }

                $existingOverlap = \App\Models\StaffSchedule::where('staff_id', $schedule->staff_id)
                    ->where('working_date', $schedule->working_date->toDateString())
                    ->where('is_working', true)
                    ->whereNotNull('start_time')
                    ->whereNotNull('end_time')
                    ->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time'])
                    ->where('id', '!=', $schedule->id)
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating schedule: ' . $e->getMessage()
            ], 500);
        }
    }
}
