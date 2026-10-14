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
        Schema::create('appointment_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('salon_service_id')->constrained('salon_services')->restrictOnDelete();
            $table->string('service_name', 120);
            $table->unsignedSmallInteger('duration_minutes');
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();

            $table->unique(['appointment_id', 'salon_service_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_services');
    }
};
