<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_user', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->primary(['user_id', 'branch_id']);
        });

        // Backfill existing users in tenant DB to default Main Branch (ID 1)
        $userIds = DB::table('users')->pluck('id');
        foreach ($userIds as $userId) {
            DB::table('branch_user')->insertOrIgnore([
                'user_id' => $userId,
                'branch_id' => 1,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_user');
    }
};
