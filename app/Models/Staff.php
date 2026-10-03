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
        'registration_number',
        'designation',
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

    protected $appends = [
        'category_ids',
    ];

    public const SUPER_ADMIN_EMAIL = 'udhayakumarn@gmail.com';

    public function isSuperAdmin(): bool
    {
        return strtolower(trim((string) $this->email)) === self::SUPER_ADMIN_EMAIL;
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function categories()
    {
        return $this->belongsToMany(ServiceCategory::class, 'staff_categories');
    }

    public function getCategoryIdsAttribute(): array
    {
        if ($this->relationLoaded('categories')) {
            return $this->categories->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        }
        return $this->categories()->pluck('service_categories.id')->map(fn ($id) => (int) $id)->values()->all();
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

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public function assignedQuotationItems()
    {
        return $this->hasMany(QuotationItem::class);
    }
}

