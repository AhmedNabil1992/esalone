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
        Schema::create('tenant_webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->ulid('event_id')->unique();
            $table->string('event_type')->index();
            $table->string('tenant_id')->nullable()->index();
            $table->string('external_subscription_id')->index();
            $table->json('payload');
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedSmallInteger('last_response_status')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_webhook_deliveries');
    }
};
