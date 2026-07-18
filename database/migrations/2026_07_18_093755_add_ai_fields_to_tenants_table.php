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
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'has_ai_subscription')) {
                $table->boolean('has_ai_subscription')->default(false);
            }
            if (!Schema::hasColumn('tenants', 'ai_requests_limit')) {
                $table->integer('ai_requests_limit')->default(50);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['has_ai_subscription', 'ai_requests_limit']);
        });
    }
};
