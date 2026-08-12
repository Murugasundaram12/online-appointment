<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'staff_id' => 'required|exists:staff,id',
            'recurrence_type' => 'required|in:one_time,daily,weekly,monthly,yearly',
            'is_working' => 'nullable|boolean',
            'start_time' => 'required_unless:is_working,0|nullable|date_format:H:i',
            'end_time' => 'required_unless:is_working,0|nullable|date_format:H:i|after:start_time',
            'working_date' => 'required_if:recurrence_type,one_time|nullable|date',
            'start_date' => 'required_unless:recurrence_type,one_time|nullable|date',
            'end_date' => 'required_unless:recurrence_type,one_time|nullable|date|after_or_equal:start_date',
            'weekly_days' => 'required_if:recurrence_type,weekly|nullable|array',
            'weekly_days.*' => 'integer|between:0,6',
            'monthly_day' => 'required_if:recurrence_type,monthly|nullable|integer|between:1,31',
            'yearly_month' => 'required_if:recurrence_type,yearly|nullable|integer|between:1,12',
            'yearly_day' => 'required_if:recurrence_type,yearly|nullable|integer|between:1,31',
            'schedule_id' => 'nullable|exists:staff_schedules,id',
            'break_start' => 'nullable|date_format:H:i',
            'break_end' => 'nullable|required_with:break_start|date_format:H:i|after:break_start',
        ];
    }

    public function messages(): array
    {
        return [
            'end_time.after' => 'End time must be after start time.',
            'end_date.after_or_equal' => 'End date must be on or after start date.',
            'weekly_days.required_if' => 'Please select at least one day for weekly recurrence.',
            'monthly_day.required_if' => 'Please select a day of the month.',
            'yearly_month.required_if' => 'Please select a month.',
            'yearly_day.required_if' => 'Please select a day of the month for yearly recurrence.',
            'break_end.after' => 'Break end time must be after break start time.',
            'break_end.required_with' => 'Break end time is required when a break start time is provided.',
        ];
    }
}
