<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'tenant_provisioning' => [
        'token' => env('TENANT_PROVISIONING_API_TOKEN'),
    ],

    'bridge' => [
        'url' => env('BRIDGE_URL'),
        'token' => env('BRIDGE_TOKEN'),
    ],

    'mqtt' => [
        'host' => env('MQTT_HOST', '127.0.0.1'),
        'port' => (int) env('MQTT_PORT', 1883),
        'username' => env('MQTT_USERNAME'),
        'password' => env('MQTT_PASSWORD'),
        'topic_prefix' => env('MQTT_TOPIC_PREFIX', 'ps-web'),
        'tls' => (bool) env('MQTT_TLS', false),
        'tls_verify_peer' => (bool) env('MQTT_TLS_VERIFY_PEER', true),
        'tls_verify_peer_name' => (bool) env('MQTT_TLS_VERIFY_PEER_NAME', true),
        'connect_timeout' => (int) env('MQTT_CONNECT_TIMEOUT', 5),
        'socket_timeout' => (int) env('MQTT_SOCKET_TIMEOUT', 5),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'enable_self_learning_lookup' => env('AI_ENABLE_SELF_LEARNING_LOOKUP', false),
        'http_verify' => env('GEMINI_HTTP_VERIFY', env('HTTP_VERIFY', true)),
    ],

];
