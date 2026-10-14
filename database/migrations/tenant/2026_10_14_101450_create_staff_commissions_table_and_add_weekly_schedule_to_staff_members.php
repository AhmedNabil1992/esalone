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
        Schema::table('staff_members', function (Blueprint $table): void {
            $table->json('weekly_schedule')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(0);
        });

        Schema::create('staff_commissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('staff_member_id')->constrained('staff_members')->restrictOnDelete();
            $table->foreignId('appointment_id')->unique()->constrained('appointments')->restrictOnDelete();
            $table->foreignId('invoice_id')->unique()->constrained('invoices')->restrictOnDelete();
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_base', 10, 2);
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->index(['branch_id', 'staff_member_id', 'created_at']);
        });

        Schema::create('staff_commission_payouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('staff_commission_id')->constrained('staff_commissions')->restrictOnDelete();
            $table->foreignId('expense_history_id')->unique()->constrained('expense_histories')->restrictOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->index(['staff_commission_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_commission_payouts');
        Schema::dropIfExists('staff_commissions');

        Schema::table('staff_members', function (Blueprint $table): void {
            $table->dropColumn(['weekly_schedule', 'commission_rate']);
        });
    }
};
