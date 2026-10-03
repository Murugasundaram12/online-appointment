<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Support\StaffCategoryService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('staff_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('service_category_id')->constrained('service_categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['staff_id', 'service_category_id']);
        });

        // Safely migrate existing legacy staff.category values into staff_categories
        if (class_exists(StaffCategoryService::class)) {
            $result = StaffCategoryService::migrateLegacyStaffCategories();
            if (!empty($result['unmatched']) && isset($this->command)) {
                foreach ($result['unmatched'] as $unmatched) {
                    $this->command->warn("Unmatched legacy category '{$unmatched['legacy_category']}' for Staff ID {$unmatched['staff_id']} ({$unmatched['staff_name']}).");
                }
            }
            if (isset($this->command)) {
                $this->command->info("Migrated {$result['migrated']} legacy staff category assignments into staff_categories.");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_categories');
    }
};
