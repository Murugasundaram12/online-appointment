<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('staff_schedules', 'working_date')) {
                $table->date('working_date')->nullable()->after('day_of_week');
                $table->index(['staff_id', 'working_date'], 'staff_schedules_staff_working_date_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('staff_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('staff_schedules', 'working_date')) {
                $table->dropIndex('staff_schedules_staff_working_date_idx');
                $table->dropColumn('working_date');
            }
        });
    }
};

