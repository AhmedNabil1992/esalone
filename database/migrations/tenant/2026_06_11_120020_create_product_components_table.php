<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_components', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('catalog_items')->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('catalog_items')->cascadeOnDelete();
            $table->decimal('quantity', 12, 3)->default(1);
            $table->string('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'component_id']);
            $table->index(['product_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_components');
    }
};
