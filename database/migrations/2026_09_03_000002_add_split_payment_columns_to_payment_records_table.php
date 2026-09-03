<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_records', function (Blueprint $table) {
            $table->string('primary_method')->nullable()->after('payment_method');
            $table->string('secondary_method')->nullable()->after('primary_method');
            $table->decimal('primary_amount', 10, 2)->nullable()->after('secondary_method');
            $table->decimal('secondary_amount', 10, 2)->nullable()->after('primary_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payment_records', function (Blueprint $table) {
            $table->dropColumn(['primary_method', 'secondary_method', 'primary_amount', 'secondary_amount']);
        });
    }
};
