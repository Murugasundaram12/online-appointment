<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Staff extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'location_id',
        'name',
        'email',
        'phone',
        'bio',
        'color',
        'access_level',
        'category',
        'salary',
        'password',
        'last_login_at',
        'is_active'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function schedules()
    {
        return $this->hasMany(StaffSchedule::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }
}
