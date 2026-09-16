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

    public function getFieldsAttribute($value)
    {
        if (is_array($value)) {
            $fields = $value;
        } elseif (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            $fields = is_array($decoded) ? $decoded : [];
        } else {
            $fields = [];
        }

        if (isset($fields['questions']) && is_array($fields['questions'])) {
            $normalized = [];
            foreach ($fields['questions'] as $q) {
                if (is_string($q)) {
                    $normalized[] = ['name' => $q, 'label' => $q, 'type' => 'text', 'required' => false];
                } elseif (is_array($q)) {
                    $normalized[] = $q;
                }
            }
            return $normalized;
        }

        return $fields;
    }

    public function setFieldsAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $value = $decoded;
            }
        }
        $this->attributes['fields'] = is_array($value) ? json_encode($value) : $value;
    }

    public function records()
    {
        return $this->hasMany(FormRecord::class);
    }
}
