<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Driver
    |--------------------------------------------------------------------------
    |
    | The cache driver to use for SmartCache. Set to 'auto' to use Laravel's
    | default cache driver.
    |
    | Supported: 'auto', 'redis', 'memcached', 'database', 'array'
    |
    | NOTE: 'file' driver does NOT support cache tags and won't work correctly!
    |
    */
    'driver' => env('SMART_CACHE_DRIVER', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Default TTL (Time To Live)
    |--------------------------------------------------------------------------
    |
    | The default cache duration in minutes. Set to 0 for infinite cache
    | (invalidated only by model events).
    |
    */
    'ttl' => env('SMART_CACHE_TTL', 60),

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | A prefix for all SmartCache keys to avoid collisions with other
    | cached data in your application.
    |
    */
    'prefix' => 'smart_cache',

    /*
    |--------------------------------------------------------------------------
    | Enable SmartCache
    |--------------------------------------------------------------------------
    |
    | Enable or disable SmartCache globally. Useful for disabling in
    | testing environments.
    |
    */
    'enabled' => env('SMART_CACHE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable logging of cache hits and misses for debugging purposes.
    | Logs will appear in Laravel's default log channel.
    |
    */
    'logging' => env('SMART_CACHE_LOGGING', false),
];
