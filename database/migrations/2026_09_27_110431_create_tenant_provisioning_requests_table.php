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
        Schema::create('tenant_provisioning_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('external_subscription_id')->unique();
            $table->string('domain')->unique();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('email');
            $table->json('payload');
            $table->string('status')->default('pending')->index();
            $table->string('tenant_id')->nullable()->index();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->boolean('retryable')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_provisioning_requests');
    }
};
