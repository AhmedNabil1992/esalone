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
        Schema::create('discount_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('invoice_no', 50);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('reason')->nullable();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ledger_entry_id')->nullable()->constrained('cash_box_ledger_entries')->nullOnDelete();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'occurred_at']);
            $table->index(['shift_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_histories');
    }
};
