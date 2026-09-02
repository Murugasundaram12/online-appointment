<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_records', function (Blueprint $table) {
            $table->string('card_brand')->nullable()->after('payment_method');
            $table->string('cardholder_name')->nullable()->after('card_brand');
            $table->string('card_last_four', 4)->nullable()->after('cardholder_name');
            $table->string('transaction_reference')->nullable()->after('card_last_four');
            $table->string('e_transfer_reference')->nullable()->after('transaction_reference');
            $table->string('sender_name')->nullable()->after('e_transfer_reference');
            $table->date('transfer_date')->nullable()->after('sender_name');
            $table->unsignedBigInteger('insurance_company_id')->nullable()->after('transfer_date');
            $table->unsignedBigInteger('insurance_information_id')->nullable()->after('insurance_company_id');
            $table->string('policy_id')->nullable()->after('insurance_information_id');
            $table->string('member_id_or_contract_number')->nullable()->after('policy_id');
            $table->string('claim_reference')->nullable()->after('member_id_or_contract_number');
            $table->decimal('amount_submitted', 10, 2)->nullable()->after('claim_reference');
            $table->text('notes')->nullable()->after('amount_submitted');
        });
    }

    public function down(): void
    {
        Schema::table('payment_records', function (Blueprint $table) {
            $table->dropColumn([
                'card_brand',
                'cardholder_name',
                'card_last_four',
                'transaction_reference',
                'e_transfer_reference',
                'sender_name',
                'transfer_date',
                'insurance_company_id',
                'insurance_information_id',
                'policy_id',
                'member_id_or_contract_number',
                'claim_reference',
                'amount_submitted',
                'notes',
            ]);
        });
    }
};
