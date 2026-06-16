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
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('expenses_id')->nullable()->after('cash_box_id');

            $table->foreign('expenses_id')
                ->references('id')
                ->on('expenses_types')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finanicial_transaction', function (Blueprint $table) {
            $table->dropForeign(['expenses_id']);
            $table->dropColumn('expenses_id');
        });
    }
};
