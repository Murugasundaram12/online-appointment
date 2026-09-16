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

    public function getSubmittedDataAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    public function setSubmittedDataAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $value = $decoded;
            }
        }
        $this->attributes['submitted_data'] = is_array($value) ? json_encode($value) : $value;
    }

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
