<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffSchedule extends Model
{
    protected $fillable = ['staff_id', 'day_of_week', 'working_date', 'start_time', 'end_time', 'is_working', 'breaks'];

    protected $casts = [
        'working_date' => 'date',
        'is_working' => 'boolean',
        'breaks' => 'array'
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
