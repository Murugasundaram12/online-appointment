<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('staff_schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('staff_schedules', 'recurrence_type')) {
                $table->string('recurrence_type')->default('one_time')->after('is_working');
            }
            if (!Schema::hasColumn('staff_schedules', 'recurrence_days')) {
                $table->json('recurrence_days')->nullable()->after('recurrence_type');
            }
            if (!Schema::hasColumn('staff_schedules', 'start_date')) {
                $table->date('start_date')->nullable()->after('recurrence_days');
            }
            if (!Schema::hasColumn('staff_schedules', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }
            if (!Schema::hasColumn('staff_schedules', 'recurrence_group_id')) {
                $table->string('recurrence_group_id')->nullable()->after('end_date');
                $table->index('recurrence_group_id', 'staff_schedules_group_idx');
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'first_name')) {
                $table->string('first_name')->nullable()->after('id');
            }
            if (!Schema::hasColumn('clients', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('clients', 'client_code')) {
                $table->string('client_code')->nullable()->unique()->after('name');
            }
            if (!Schema::hasColumn('clients', 'gender')) {
                $table->string('gender')->nullable()->after('email');
            }
            if (!Schema::hasColumn('clients', 'dob')) {
                $table->date('dob')->nullable()->after('gender');
            }
            if (!Schema::hasColumn('clients', 'alternate_phone')) {
                $table->string('alternate_phone')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('clients', 'address_line1')) {
                $table->string('address_line1')->nullable()->after('city');
            }
            if (!Schema::hasColumn('clients', 'address_line2')) {
                $table->string('address_line2')->nullable()->after('address_line1');
            }
            if (!Schema::hasColumn('clients', 'state')) {
                $table->string('state')->nullable()->after('address_line2');
            }
            if (!Schema::hasColumn('clients', 'country')) {
                $table->string('country')->nullable()->after('state');
            }
            if (!Schema::hasColumn('clients', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('country');
            }
            if (!Schema::hasColumn('clients', 'emergency_contact')) {
                $table->string('emergency_contact')->nullable()->after('postal_code');
            }
            if (!Schema::hasColumn('clients', 'emergency_phone')) {
                $table->string('emergency_phone')->nullable()->after('emergency_contact');
            }
            if (!Schema::hasColumn('clients', 'photo')) {
                $table->string('photo')->nullable()->after('emergency_phone');
            }
            if (!Schema::hasColumn('clients', 'status')) {
                $table->string('status')->default('active')->after('photo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_schedules', function (Blueprint $table) {
            $table->dropIndex('staff_schedules_group_idx');
            $table->dropColumn([
                'recurrence_type',
                'recurrence_days',
                'start_date',
                'end_date',
                'recurrence_group_id',
            ]);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'client_code',
                'gender',
                'dob',
                'alternate_phone',
                'address_line1',
                'address_line2',
                'state',
                'country',
                'postal_code',
                'emergency_contact',
                'emergency_phone',
                'photo',
                'status',
            ]);
        });
    }
};
