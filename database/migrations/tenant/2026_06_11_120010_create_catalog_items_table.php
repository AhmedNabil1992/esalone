<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_group_id')->nullable()->constrained('inventory_groups')->nullOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('picture')->nullable();
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->boolean('is_product')->default(false);
            $table->boolean('is_component')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_product', 'is_component']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_items');
    }
};
