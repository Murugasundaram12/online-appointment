<?php

namespace App\Support;

use App\Models\Service;
use App\Models\Staff;
use Illuminate\Support\Collection;

/**
 * Encodes the relationship between a staff member's category and the services
 * they are allowed to provide.
 *
 * Schema mapping:
 *   staff.category            -> free-form category name (string)
 *   service_categories.name   -> canonical category name (string)
 *   services.service_category_id -> service_categories.id
 *
 * A staff member may provide a service when their category name matches the
 * service's category name. Staff without a category are unrestricted.
 */
class StaffCategoryService
{
    /**
     * Normalize a category name for comparison.
     */
    public static function normalize(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    /**
     * Determine whether a staff category matches a service category name.
     * Unrestricted (null/empty) staff categories match anything, and services
     * without a category are available to all staff.
     */
    public static function categoryMatches(?string $staffCategory, ?string $serviceCategoryName): bool
    {
        if (empty($staffCategory) || empty(trim((string) $staffCategory))) {
            return true;
        }

        if (empty($serviceCategoryName) || empty(trim((string) $serviceCategoryName))) {
            return true;
        }

        $staff = self::normalize($staffCategory);
        $service = self::normalize($serviceCategoryName);

        if ($staff === $service) {
            return true;
        }

        // Substring fallback keeps existing data working where naming is not
        // perfectly consistent (e.g. "Massage Therapist" vs "Massage").
        return str_contains($staff, $service) || str_contains($service, $staff);
    }

    /**
     * Can the given staff member provide the given service?
     */
    public static function staffCanProvide(Staff $staff, Service $service): bool
    {
        if (!$service->is_active) {
            return false;
        }

        return self::categoryMatches($staff->category, $service->category?->name);
    }

    /**
     * Query scope: restrict services to those a given staff category may provide.
     * Services without a category are available to all staff.
     */
    public static function scopeByStaffCategory($query, ?string $staffCategory)
    {
        if (empty($staffCategory) || empty(trim((string) $staffCategory))) {
            return $query;
        }

        $normalized = self::normalize($staffCategory);

        return $query->where(function ($q) use ($normalized) {
            $q->whereDoesntHave('category')
                ->orWhereHas('category', function ($cq) use ($normalized) {
                    $cq->whereRaw('LOWER(TRIM(name)) = ?', [$normalized])
                        ->orWhereRaw('LOWER(TRIM(name)) LIKE ?', ['%' . $normalized . '%'])
                        ->orWhereRaw('? LIKE CONCAT("%", LOWER(TRIM(name)), "%")', [$normalized]);
                });
        });
    }

    /**
     * All active services a given staff member may provide.
     */
    public static function servicesForStaff(Staff $staff): Collection
    {
        return self::scopeByStaffCategory(Service::query()->where('is_active', true), $staff->category)->get();
    }

    /**
     * Query scope: restrict staff to those whose category supports the given
     * service category name. Staff without a category are unrestricted.
     */
    public static function scopeStaffByCategory($query, ?string $serviceCategoryName)
    {
        if (empty($serviceCategoryName) || empty(trim((string) $serviceCategoryName))) {
            return $query;
        }

        $normalized = self::normalize($serviceCategoryName);

        return $query->where(function ($q) use ($normalized) {
            $q->whereNull('category')
                ->orWhere('category', '')
                ->orWhereRaw('LOWER(TRIM(category)) = ?', [$normalized])
                ->orWhereRaw('LOWER(TRIM(category)) LIKE ?', ['%' . $normalized . '%'])
                ->orWhereRaw('? LIKE CONCAT("%", LOWER(TRIM(category)), "%")', [$normalized]);
        });
    }
}
