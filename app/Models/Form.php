<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    protected $fillable = ['name', 'description', 'fields', 'is_active'];

    protected $casts = [
        'fields' => 'array',
        'is_active' => 'boolean'
    ];

    public function Records()
    {
        return $this->hasMany(FormRecord::class);
    }
}
