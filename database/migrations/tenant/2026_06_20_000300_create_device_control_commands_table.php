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
        Schema::create('device_control_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->string('ip_address', 45);
            $table->string('device_name', 50);
            $table->unsignedTinyInteger('command');
            $table->string('action', 10);
            $table->string('username', 100)->nullable();
            $table->string('remark', 255)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('processed_by', 100)->nullable();
            $table->unsignedSmallInteger('retry_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('ack_at')->nullable();
            $table->string('error_message', 255)->nullable();
            $table->timestamps();

            $table->index(['status', 'id']);
            $table->index(['device_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_control_commands');
    }
};
