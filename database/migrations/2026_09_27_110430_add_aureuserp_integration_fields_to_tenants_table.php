<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('external_subscription_id')->nullable()->unique()->after('id');
            $table->ulid('provisioning_request_id')->nullable()->index()->after('external_subscription_id');
            $table->string('provisioning_status')->default('ready')->index();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->boolean('retryable')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('deletion_started_at')->nullable();
        });

        DB::table('tenants')->select(['id', 'data'])->orderBy('id')->get()->each(function (object $tenant): void {
            $data = json_decode((string) ($tenant->data ?? '{}'), true);
            if (! is_array($data)) {
                return;
            }

            $keys = [
                'external_subscription_id',
                'provisioning_request_id',
                'provisioning_status',
                'error_code',
                'error_message',
                'retryable',
                'completed_at',
                'deletion_started_at',
            ];
            $attributes = array_intersect_key($data, array_flip($keys));
            foreach (['provisioning_status', 'retryable'] as $requiredKey) {
                if (array_key_exists($requiredKey, $attributes) && $attributes[$requiredKey] === null) {
                    unset($attributes[$requiredKey]);
                }
            }

            if ($attributes !== []) {
                DB::table('tenants')->where('id', $tenant->id)->update($attributes);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique(['external_subscription_id']);
            $table->dropIndex(['provisioning_request_id']);
            $table->dropIndex(['provisioning_status']);
            $table->dropColumn([
                'external_subscription_id',
                'provisioning_request_id',
                'provisioning_status',
                'error_code',
                'error_message',
                'retryable',
                'completed_at',
                'deletion_started_at',
            ]);
        });
    }
};
