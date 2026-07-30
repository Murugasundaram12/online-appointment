<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations - Add salary management columns to payroll table
     */
    public function up(): void
    {
        Schema::table('payroll', function (Blueprint $table) {
            // Add salary-related columns if they don't exist
            if (!Schema::hasColumn('payroll', 'salary_amount')) {
                $table->decimal('salary_amount', 10, 2)->default(0)->after('period_end');
            }
            if (!Schema::hasColumn('payroll', 'commission_amount')) {
                $table->decimal('commission_amount', 10, 2)->default(0)->after('salary_amount');
            }
            if (!Schema::hasColumn('payroll', 'bonus')) {
                $table->decimal('bonus', 10, 2)->default(0)->after('commission_amount');
            }
            if (!Schema::hasColumn('payroll', 'deductions')) {
                $table->decimal('deductions', 10, 2)->default(0)->after('bonus');
            }
            if (!Schema::hasColumn('payroll', 'payment_date')) {
                $table->date('payment_date')->nullable()->after('total_payout');
            }
            if (!Schema::hasColumn('payroll', 'payment_type')) {
                $table->string('payment_type')->default('transfer')->after('payment_date'); // cash, check, transfer, mobile_money
            }
            if (!Schema::hasColumn('payroll', 'notes')) {
                $table->text('notes')->nullable()->after('payment_type');
            }
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::table('payroll', function (Blueprint $table) {
            // Drop the new columns
            if (Schema::hasColumn('payroll', 'salary_amount')) {
                $table->dropColumn('salary_amount');
            }
            if (Schema::hasColumn('payroll', 'commission_amount')) {
                $table->dropColumn('commission_amount');
            }
            if (Schema::hasColumn('payroll', 'bonus')) {
                $table->dropColumn('bonus');
            }
            if (Schema::hasColumn('payroll', 'deductions')) {
                $table->dropColumn('deductions');
            }
            if (Schema::hasColumn('payroll', 'payment_date')) {
                $table->dropColumn('payment_date');
            }
            if (Schema::hasColumn('payroll', 'payment_type')) {
                $table->dropColumn('payment_type');
            }
            if (Schema::hasColumn('payroll', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
