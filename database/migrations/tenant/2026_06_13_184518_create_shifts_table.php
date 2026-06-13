<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('shift_no')->nullable()->unique();
            $table->date('shift_date')->nullable();
            $table->timestamp('shift_from')->nullable();
            $table->timestamp('shift_to')->nullable();
            $table->foreignId('opened_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('start_amount', 12, 2)->default(0);
            $table->decimal('playstation_amount', 12, 2)->default(0);
            $table->decimal('sales_amount', 12, 2)->default(0);
            $table->decimal('customer_add_amount', 12, 2)->default(0);
            $table->decimal('income_history_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('service_amount', 12, 2)->default(0);
            $table->decimal('purchase_amount', 12, 2)->default(0);
            $table->decimal('expenses_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('customer_minus_amount', 12, 2)->default(0);
            $table->decimal('customer_credit_amount', 12, 2)->default(0);
            $table->decimal('credit_amount', 12, 2)->default(0);
            $table->decimal('actual_amount', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('status')->default('open');
            $table->boolean('notify')->default(false);
            $table->timestamps();

            $table->index(['status', 'shift_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
