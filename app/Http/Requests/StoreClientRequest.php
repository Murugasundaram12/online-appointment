<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clientId = $this->route('client') ? $this->route('client')->id : $this->input('client_id');

        return self::rulesFor($clientId);
    }

    public static function rulesFor(mixed $clientId = null): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'gender' => 'nullable|string|in:male,female,other',
            'dob' => 'nullable|date|before:today',
            'phone' => [
                'required',
                'string',
                'max:30',
                Rule::unique('clients', 'phone')->ignore($clientId),
            ],
            'alternate_phone' => 'nullable|string|max:30',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('clients', 'email')->ignore($clientId),
            ],
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:5000',
            'client_since' => 'nullable|date',
            'is_vip' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'A client with this phone number already exists.',
            'email.required' => 'An email address is required.',
            'email.unique' => 'A client with this email address already exists.',
            'dob.before' => 'Date of birth must be a past date.',
        ];
    }
}
