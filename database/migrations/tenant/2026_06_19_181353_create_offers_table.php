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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->text('name')->nullable();
            $table->text('devices')->nullable();
            $table->time('time_from')->nullable();
            $table->time('time_to')->nullable();
            $table->integer('period')->nullable();
            $table->string('play_type', 255)->nullable();
            $table->decimal('price', 11, 2)->nullable();
            $table->string('remark', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('play_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
