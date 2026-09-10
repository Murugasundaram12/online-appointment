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
        Schema::table('payment_records', function (Blueprint $table) {
            $table->decimal('cash_amount', 10, 2)->nullable()->after('secondary_amount');
            $table->decimal('card_amount', 10, 2)->nullable()->after('cash_amount');
            $table->decimal('e_transfer_amount', 10, 2)->nullable()->after('card_amount');
            $table->decimal('insurance_amount', 10, 2)->nullable()->after('e_transfer_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_records', function (Blueprint $table) {
            $table->dropColumn([
                'cash_amount',
                'card_amount',
                'e_transfer_amount',
                'insurance_amount',
            ]);
        });
    }
};
