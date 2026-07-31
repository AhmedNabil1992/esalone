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
        Schema::create('market_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->foreignId('catalog_item_id')->nullable()->constrained('catalog_items')->nullOnDelete();
            $table->string('subject_name', 255)->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->date('transaction_date')->nullable();
            $table->time('transaction_time')->nullable();
            $table->string('invoice_no', 50)->nullable();
            $table->string('created_by', 255)->nullable();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->foreignId('ledger_entry_id')->nullable()->constrained('cash_box_ledger_entries')->nullOnDelete();
            $table->timestamps();

            $table->index(['transaction_date', 'id']);
            $table->index(['shift_id', 'transaction_date']);
            $table->index(['invoice_no', 'transaction_date']);
            $table->index(['customer_id', 'transaction_date']);
            $table->index(['device_id', 'transaction_date']);
            $table->index(['catalog_item_id', 'transaction_date']);
            $table->index(['ledger_entry_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_history');
    }
};
