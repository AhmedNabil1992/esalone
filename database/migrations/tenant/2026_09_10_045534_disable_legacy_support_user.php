<?php

declare(strict_types=1);

use App\Models\Tenant\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $supportUser = DB::table('users')->where('email', 'admin@etech-valley.com')->first();

        if (! $supportUser) {
            return;
        }

        DB::table('sessions')->where('user_id', $supportUser->id)->delete();
        DB::table('password_reset_tokens')->where('email', 'admin@etech-valley.com')->delete();
        DB::table('model_has_roles')->where('model_type', User::class)->where('model_id', $supportUser->id)->delete();
        DB::table('model_has_permissions')->where('model_type', User::class)->where('model_id', $supportUser->id)->delete();
        DB::table('users')->where('id', $supportUser->id)->update([
            'name' => 'Disabled legacy support account',
            'email' => 'disabled-support-'.$supportUser->id.'@invalid.local',
            'password' => Hash::make(Str::random(64)),
            'remember_token' => null,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // The insecure shared credential must not be restored.
    }
};
