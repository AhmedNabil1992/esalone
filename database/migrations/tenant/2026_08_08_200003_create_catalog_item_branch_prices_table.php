<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_item_branch_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained('catalog_items')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->decimal('sale_price', 12, 2);
            $table->timestamps();

            $table->unique(['catalog_item_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_item_branch_prices');
    }
};
