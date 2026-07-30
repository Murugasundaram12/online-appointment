<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = ['name', 'description', 'price', 'validity_days', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function services()
    {
        return $this->belongsToMany(Service::class, 'package_services')->withPivot('quantity');
    }
}
