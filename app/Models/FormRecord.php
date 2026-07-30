<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormRecord extends Model
{
    protected $fillable = ['form_id', 'client_id', 'submitted_data', 'submitted_at'];

    protected $casts = [
        'submitted_data' => 'array',
        'submitted_at' => 'datetime'
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
