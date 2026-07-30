<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['service_category_id', 'name', 'description', 'type', 'price', 'duration_minutes', 'buffer_minutes', 'color', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function packages()
    {
        return $this->belongsToMany(Package::class, 'package_services');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
