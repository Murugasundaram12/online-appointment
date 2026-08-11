<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffSchedule extends Model
{
    protected $fillable = [
        'staff_id',
        'day_of_week',
        'working_date',
        'start_time',
        'end_time',
        'is_working',
        'breaks',
        'recurrence_type',
        'recurrence_days',
        'start_date',
        'end_date',
        'recurrence_group_id',
    ];

    protected $casts = [
        'working_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_working' => 'boolean',
        'breaks' => 'array',
        'recurrence_days' => 'array',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
