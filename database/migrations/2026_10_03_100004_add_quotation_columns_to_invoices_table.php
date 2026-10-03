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
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('quotation_id')->nullable()->after('appointment_id')->constrained('quotations')->nullOnDelete();
            $table->decimal('subtotal', 10, 2)->nullable()->after('invoice_number');
            $table->decimal('discount_amount', 10, 2)->default(0.00)->after('subtotal');
            $table->decimal('tax_amount', 10, 2)->default(0.00)->after('discount_amount');

            $table->index('quotation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['quotation_id']);
            $table->dropIndex(['quotation_id']);
            $table->dropColumn(['quotation_id', 'subtotal', 'discount_amount', 'tax_amount']);
        });
    }
};
