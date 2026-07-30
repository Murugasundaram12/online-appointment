<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            // Removed duplicate definition

            // Actually I should just use constrained() because I created the migration for categories before services (timestamp is earlier/same).
            // But to be safe in migrate execution order I'll rely on Laravel to run them in date order.
            // filenames: service_categories is 071301_create_service.. and services is 071301_create_services...
            // Alphabetically 'service_categories' comes before 'services'. So it should be fine.
            $table->foreignId('service_category_id')->nullable()->constrained('service_categories')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('in_person');
            $table->decimal('price', 10, 2);
            $table->integer('duration_minutes');
            $table->integer('buffer_minutes')->default(0);
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
