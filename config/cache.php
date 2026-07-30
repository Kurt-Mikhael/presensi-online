<?php

use Illuminate\Support\Str;

return [

    'store' => env('CACHE_STORE', 'file'),

    'stores' => [
        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],
        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/locks'),
        ],
        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CONNECTION', 'pgsql'),
            'table' => 'cache',
            'lock_connection' => env('DB_CONNECTION', 'pgsql'),
            'lock_table' => 'cache_locks',
        ],
    ],

    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'presensi'), '_').'_cache_'),

];