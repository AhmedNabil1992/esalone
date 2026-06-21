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
        Schema::create('order_session_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('play_session_id')->constrained('play_sessions')->cascadeOnDelete();
            $table->foreignId('device_id')->constrained('devices');
            $table->foreignId('catalog_item_id')->constrained('catalog_items');
            $table->decimal('price', 8, 2);
            $table->integer('quantity')->default(1);
            $table->decimal('total', 8, 2);
            $table->string('status')->default('pending');
            $table->string('order_by')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_session_details');
    }
};
