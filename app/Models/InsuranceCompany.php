<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceCompany extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function insuranceInformations()
    {
        return $table = $this->hasMany(InsuranceInformation::class);
    }
}
