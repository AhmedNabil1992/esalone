<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table): void {
            $table->foreignId('shift_id')->nullable()->after('supplier_id')->constrained('shifts')->nullOnDelete();
            $table->index(['shift_id', 'purchased_at']);
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('shift_id');
        });
    }
};
