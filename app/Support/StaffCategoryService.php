<?php

namespace App\Support;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Encodes the relationship between staff members and service categories.
 *
 * Staff support multiple service categories via the staff_categories pivot table.
 * PRIMARY matching uses service_category_id.
 * Legacy staff.category string is supported as a fallback.
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
     * Determine whether a staff category matches a service category name or service.
     * Supports Staff instance or string category names.
     */
    public static function categoryMatches($staffOrCategory, $serviceOrCategory = null): bool
    {
        if ($staffOrCategory instanceof Staff) {
            if ($serviceOrCategory instanceof Service) {
                return self::staffCanProvide($staffOrCategory, $serviceOrCategory);
            }

            // Staff with categories assigned in pivot table
            $assignedCategories = $staffOrCategory->relationLoaded('categories')
                ? $staffOrCategory->categories
                : $staffOrCategory->categories()->get();

            if ($assignedCategories->isNotEmpty()) {
                if ($serviceOrCategory === null || $serviceOrCategory === '') {
                    return true;
                }

                if ($serviceOrCategory instanceof ServiceCategory) {
                    return $assignedCategories->pluck('id')->contains($serviceOrCategory->id);
                }

                $normalizedServiceCat = self::normalize((string) $serviceOrCategory);
                return $assignedCategories->contains(function ($cat) use ($normalizedServiceCat) {
                    $normCat = self::normalize($cat->name);
                    return $normCat === $normalizedServiceCat || str_contains($normCat, $normalizedServiceCat) || str_contains($normalizedServiceCat, $normCat);
                });
            }

            // Fallback to legacy string if pivot is empty
            return self::legacyCategoryMatches($staffOrCategory->category, is_object($serviceOrCategory) ? ($serviceOrCategory->name ?? null) : $serviceOrCategory);
        }

        $serviceCatName = is_object($serviceOrCategory) ? ($serviceOrCategory->name ?? null) : $serviceOrCategory;
        return self::legacyCategoryMatches($staffOrCategory, $serviceCatName);
    }

    /**
     * Legacy substring / exact string category matching.
     */
    protected static function legacyCategoryMatches(?string $staffCategory, ?string $serviceCategoryName): bool
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

        return str_contains($staff, $service) || str_contains($service, $staff);
    }

    /**
     * Can the given staff member provide the given service?
     * PRIMARY matching uses service_category_id against staff_categories pivot table.
     */
    public static function staffCanProvide(Staff $staff, Service $service): bool
    {
        if (!$service->is_active) {
            return false;
        }

        // Service without category is available to all staff
        $serviceCategoryId = $service->service_category_id ?? $service->category?->id;
        if (!$serviceCategoryId && !$service->category) {
            return true;
        }

        $assignedCategories = $staff->relationLoaded('categories')
            ? $staff->categories
            : $staff->categories()->get();

        if ($assignedCategories->isNotEmpty()) {
            if (!$serviceCategoryId) {
                return true;
            }
            return $assignedCategories->pluck('id')->contains($serviceCategoryId);
        }

        // Fallback for staff who haven't yet been assigned pivot categories
        if (empty($staff->category) || empty(trim((string) $staff->category))) {
            return true; // Staff without a category are unrestricted
        }

        return self::legacyCategoryMatches($staff->category, $service->category?->name);
    }

    /**
     * Query scope: restrict services to those a given staff member or category may provide.
     * Services without a category are available to all staff.
     */
    public static function scopeByStaffCategory($query, $staffOrCategory = null)
    {
        if ($staffOrCategory === null || $staffOrCategory === '') {
            return $query;
        }

        if ($staffOrCategory instanceof Staff) {
            $assignedCategories = $staffOrCategory->relationLoaded('categories')
                ? $staffOrCategory->categories
                : $staffOrCategory->categories()->get();

            if ($assignedCategories->isNotEmpty()) {
                $categoryIds = $assignedCategories->pluck('id')->all();
                return $query->where(function ($q) use ($categoryIds) {
                    $q->whereNull('service_category_id')
                        ->orWhereIn('service_category_id', $categoryIds);
                });
            }

            // Fallback for legacy staff
            return self::scopeByStaffCategory($query, $staffOrCategory->category);
        }

        if (is_array($staffOrCategory)) {
            $categoryIds = array_filter($staffOrCategory);
            if (empty($categoryIds)) {
                return $query;
            }
            return $query->where(function ($q) use ($categoryIds) {
                $q->whereNull('service_category_id')
                    ->orWhereIn('service_category_id', $categoryIds);
            });
        }

        $staffCategory = (string) $staffOrCategory;
        if (empty(trim($staffCategory))) {
            return $query;
        }

        $normalized = self::normalize($staffCategory);
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();

        return $query->where(function ($q) use ($normalized, $driver) {
            $q->whereDoesntHave('category')
                ->orWhereHas('category', function ($cq) use ($normalized, $driver) {
                    $cq->whereRaw('LOWER(TRIM(name)) = ?', [$normalized])
                        ->orWhereRaw('LOWER(TRIM(name)) LIKE ?', ['%' . $normalized . '%']);
                    if ($driver === 'sqlite') {
                        $cq->orWhereRaw('? LIKE (\'%\' || LOWER(TRIM(name)) || \'%\')', [$normalized]);
                    } else {
                        $cq->orWhereRaw('? LIKE CONCAT("%", LOWER(TRIM(name)), "%")', [$normalized]);
                    }
                });
        });
    }

    /**
     * All active services a given staff member may provide.
     */
    public static function servicesForStaff(Staff $staff): Collection
    {
        return self::scopeByStaffCategory(Service::query()->where('is_active', true), $staff)->get();
    }

    /**
     * Query scope: restrict staff to those whose assigned categories support the given
     * service category. Staff without any category are unrestricted.
     */
    public static function scopeStaffByCategory($query, $category = null)
    {
        if (empty($category)) {
            return $query;
        }

        $catId = null;
        $catName = null;

        if ($category instanceof Service) {
            $catId = $category->service_category_id;
            $catName = $category->category?->name;
        } elseif ($category instanceof ServiceCategory) {
            $catId = $category->id;
            $catName = $category->name;
        } elseif (is_numeric($category)) {
            $catId = (int) $category;
            $catName = ServiceCategory::find($catId)?->name;
        } elseif (is_string($category)) {
            $catName = $category;
            $cat = ServiceCategory::whereRaw('LOWER(TRIM(name)) = ?', [self::normalize($category)])->first();
            $catId = $cat?->id;
        }

        if (!$catId && empty($catName)) {
            return $query;
        }

        return $query->where(function ($q) use ($catId, $catName) {
            // 1. Staff who have this category assigned in staff_categories pivot
            if ($catId) {
                $q->whereHas('categories', function ($cq) use ($catId) {
                    $cq->where('service_categories.id', $catId);
                });
            }

            // 2. Staff who are unrestricted (have NO pivot categories AND no legacy category)
            $q->orWhere(function ($uq) use ($catName) {
                $uq->whereDoesntHave('categories')
                    ->where(function ($legacyQ) use ($catName) {
                        $legacyQ->whereNull('category')
                            ->orWhere('category', '');

                        if (!empty($catName)) {
                            $normalized = self::normalize($catName);
                            $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
                            $legacyQ->orWhereRaw('LOWER(TRIM(category)) = ?', [$normalized])
                                ->orWhereRaw('LOWER(TRIM(category)) LIKE ?', ['%' . $normalized . '%']);
                            if ($driver === 'sqlite') {
                                $legacyQ->orWhereRaw('? LIKE (\'%\' || LOWER(TRIM(category)) || \'%\')', [$normalized]);
                            } else {
                                $legacyQ->orWhereRaw('? LIKE CONCAT("%", LOWER(TRIM(category)), "%")', [$normalized]);
                            }
                        }
                    });
            });
        });
    }

    /**
     * Migrate non-empty legacy staff.category values into staff_categories.
     * Matches trimmed/case-insensitive legacy values against service_categories.name.
     */
    public static function migrateLegacyStaffCategories(): array
    {
        $staffMembers = Staff::whereNotNull('category')
            ->where('category', '!=', '')
            ->get();

        $allCategories = ServiceCategory::all();
        $migrated = 0;
        $unmatched = [];
        $duplicatesSkipped = 0;

        foreach ($staffMembers as $staff) {
            $legacyCat = trim((string) $staff->category);
            if ($legacyCat === '') {
                continue;
            }

            $normalized = self::normalize($legacyCat);

            $matchedCategory = $allCategories->first(function ($cat) use ($normalized) {
                return self::normalize($cat->name) === $normalized;
            });

            if ($matchedCategory) {
                $exists = DB::table('staff_categories')
                    ->where('staff_id', $staff->id)
                    ->where('service_category_id', $matchedCategory->id)
                    ->exists();

                if (!$exists) {
                    DB::table('staff_categories')->insert([
                        'staff_id' => $staff->id,
                        'service_category_id' => $matchedCategory->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $migrated++;
                } else {
                    $duplicatesSkipped++;
                }
            } else {
                $unmatched[] = [
                    'staff_id' => $staff->id,
                    'staff_name' => $staff->name,
                    'legacy_category' => $staff->category,
                ];
                Log::warning("Unmatched legacy staff category: Staff [ID: {$staff->id}, Name: {$staff->name}] has legacy category '{$staff->category}' which could not be matched to any service category.");
            }
        }

        return [
            'migrated' => $migrated,
            'unmatched' => $unmatched,
            'duplicates_skipped' => $duplicatesSkipped,
        ];
    }
}
