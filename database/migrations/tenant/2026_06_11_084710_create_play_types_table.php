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
        Schema::create('play_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_type_id')->constrained('devices_types')->cascadeOnDelete();
            $table->string('name', 20);
            $table->string('description', 50)->nullable();
            $table->string('picture')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('play_types');
    }
};
