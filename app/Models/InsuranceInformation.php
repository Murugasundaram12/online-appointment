<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceInformation extends Model
{
    use HasFactory;

    protected $table = 'insurance_information';

    protected $fillable = [
        'client_id',
        'insurance_company_id',
        'policy_id',
        'member_id_or_contract_number',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function insuranceCompany()
    {
        return $this->belongsTo(InsuranceCompany::class);
    }
}
