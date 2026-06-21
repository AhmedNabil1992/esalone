<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoice_payments')) {
            return;
        }

        Schema::create('invoice_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('cash_box_id')->constrained('cash_boxes');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payment_method', 30)->default('cash');
            $table->decimal('amount', 12, 2);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'id']);
            $table->index(['cash_box_id', 'created_at']);
            $table->index(['payment_method', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
    }
};
