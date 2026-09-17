<?php

declare(strict_types=1);

use App\Models\Tenant\PushSubscription;

return [
    'vapid' => [
        'subject' => env('VAPID_SUBJECT'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'pem_file' => env('VAPID_PEM_FILE'),
    ],

    'model' => PushSubscription::class,

    'table_name' => env('WEBPUSH_DB_TABLE', 'push_subscriptions'),

    /*
     * Kept for the package's central migration only. Runtime subscriptions use
     * the tenant-only model above and can never fall back to this connection.
     */
    'database_connection' => env('WEBPUSH_DB_CONNECTION', env('DB_CONNECTION', 'mysql')),

    'client_options' => [],

    'automatic_padding' => env('WEBPUSH_AUTOMATIC_PADDING', true),
];
