<?php

namespace App\Services;

class PhoneFormatter
{
    /**
     * Format a phone number into Canadian standard: +1 (XXX) XXX-XXXX.
     * If the input is empty or invalid format, return as-is.
     */
    public static function format(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return $phone;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        // Strip leading 1 if 11 digits starting with 1
        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) === 10) {
            $area = substr($digits, 0, 3);
            $prefix = substr($digits, 3, 3);
            $line = substr($digits, 6, 4);

            return "+1 ({$area}) {$prefix}-{$line}";
        }

        return $phone;
    }

    /**
     * Sanitize phone number to digits only or canonical string for database search.
     */
    public static function sanitize(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return $phone;
        }

        return preg_replace('/\D+/', '', $phone);
    }

    /**
     * Validate if string is a valid 10-digit North American / Canadian phone number.
     */
    public static function isValidCanadian(?string $phone): bool
    {
        if (empty($phone)) {
            return true; // Allow optional/nullable
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            $digits = substr($digits, 1);
        }

        return strlen($digits) === 10;
    }
}
