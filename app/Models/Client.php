<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'name',
        'client_code',
        'email',
        'gender',
        'dob',
        'phone',
        'alternate_phone',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'country',
        'postal_code',
        'emergency_contact',
        'emergency_phone',
        'notes',
        'photo',
        'status',
        'client_since',
        'is_vip',
    ];

    protected $casts = [
        'dob' => 'date',
        'client_since' => 'date',
        'is_vip' => 'boolean',
    ];

    protected $appends = ['age', 'formatted_address'];

    protected static function booted()
    {
        static::creating(function ($client) {
            if (empty($client->name)) {
                $client->name = trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? ''));
            }
            if (empty($client->client_code)) {
                $nextId = (static::max('id') ?? 0) + 1;
                $client->client_code = 'CLI-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
            }
            if (empty($client->client_since)) {
                $client->client_since = now()->toDateString();
            }
        });

        static::updating(function ($client) {
            if (empty($client->name) || $client->isDirty(['first_name', 'last_name'])) {
                $fullName = trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? ''));
                if ($fullName !== '') {
                    $client->name = $fullName;
                }
            }
        });
    }

    public function getAgeAttribute(): ?int
    {
        if (!$this->dob) {
            return null;
        }

        return Carbon::parse($this->dob)->age;
    }

    public function getFormattedAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return !empty($parts) ? implode(', ', $parts) : '-';
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function formRecords()
    {
        return $this->hasMany(FormRecord::class);
    }

    public function insuranceInformations()
    {
        return $this->hasMany(InsuranceInformation::class);
    }
}
