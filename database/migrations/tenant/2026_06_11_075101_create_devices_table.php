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
        Schema::create('devices', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->foreignId('devices_type_id')
                ->constrained('devices_types')
                ->cascadeOnDelete();
            $table->string('name', 20);
            $table->string('ip_address', 15)->nullable();
            $table->string('mac_address', 17)->nullable();
            $table->integer('limit_time')->default(0);
            $table->boolean('is_time')->default(true);
            $table->enum('status', ['active', 'inactive', 'busy'])->default('inactive');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
