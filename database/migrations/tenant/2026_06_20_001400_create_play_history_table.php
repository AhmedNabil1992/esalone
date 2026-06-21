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
        Schema::create('play_history', function (Blueprint $table) {
            $table->id();
            $table->string('device_name', 255)->nullable();
            $table->string('play_type', 255)->nullable();
            $table->decimal('hour_price', 12, 2)->nullable();
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->dateTime('actual_end_time')->nullable();
            $table->integer('play_time')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('username', 255)->nullable();
            $table->integer('shift_no')->nullable();
            $table->string('invoice_no', 50)->nullable();
            $table->date('trx_date')->nullable();
            $table->timestamps();

            $table->index(['invoice_no', 'trx_date']);
            $table->index(['shift_no', 'trx_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('play_history');
    }
};
