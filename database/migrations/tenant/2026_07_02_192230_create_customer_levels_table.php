<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('points_required', 12, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->enum('applies_to', ['play_only', 'all'])->default('play_only');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('customer_level_id')->nullable()->constrained('customer_levels')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['customer_level_id']);
            $table->dropColumn('customer_level_id');
        });

        Schema::dropIfExists('customer_levels');
    }
};
