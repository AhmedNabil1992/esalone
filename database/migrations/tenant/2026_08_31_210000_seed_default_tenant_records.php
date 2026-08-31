<?php

declare(strict_types=1);

use Database\Seeders\Tenant\DefaultSeeder;
use Database\Seeders\Tenant\SettingSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // (new DefaultSeeder)->run();
        // (new SettingSeeder)->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed for default initial configuration
    }
};
