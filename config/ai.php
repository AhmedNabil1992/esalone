<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default AI provider that will be used to
    | resolve agent interactions, text generation, image generation, etc.
    |
    */

    'default' => env('AI_PROVIDER', 'gemini'),

    /*
    |--------------------------------------------------------------------------
    | Default Models
    |--------------------------------------------------------------------------
    |
    | Here you may configure the default models for each AI capability.
    | These models will be used unless overridden by your agents.
    |
    */

    'defaults' => [
        'text' => 'gemini-3.1-flash-lite',
        'image' => 'imagen-3.0',
        'embeddings' => 'text-embedding-004',
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Providers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the credentials and drivers for each of the
    | supported AI providers. You can add as many as needed.
    |
    */

    'providers' => [

        'gemini' => [
            'driver' => 'gemini',
            'key' => env('GEMINI_API_KEY'),
        ],

        'openai' => [
            'driver' => 'openai',
            'key' => env('OPENAI_API_KEY'),
        ],

    ],

];
