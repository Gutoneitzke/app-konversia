<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Name
    |--------------------------------------------------------------------------
    */
    'name' => env('HORIZON_NAME', 'whatsapp-horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    */
    'domain' => env('HORIZON_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    */
    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    */
    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    */
    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_') . '_horizon:'
    ),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    */
    'waits' => [
        'redis:incoming'   => 30,
        'redis:outgoing'   => 60,
        'redis:automation' => 60,
        'redis:webhook'    => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times (minutes)
    |--------------------------------------------------------------------------
    */
    'trim' => [
        'recent'         => 60,
        'pending'        => 60,
        'completed'      => 60,
        'recent_failed'  => 10080, // 7 dias
        'failed'         => 10080,
        'monitored'      => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Silenced Jobs
    |--------------------------------------------------------------------------
    */
    'silenced' => [
        // App\Jobs\HealthCheckJob::class,
    ],

    'silenced_tags' => [
        // 'heartbeat',
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    */
    'metrics' => [
        'trim_snapshots' => [
            'job'   => 24,
            'queue' => 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    */
    'fast_termination' => true,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    */
    'memory_limit' => 128,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    */
    'defaults' => [

        /*
        |-----------------------------------------
        | Mensagens recebidas + Webhooks
        |-----------------------------------------
        */
        'incoming-supervisor' => [
            'connection'           => 'redis',
            'queue'                => ['incoming', 'webhook'],
            'balance'              => 'auto',
            'autoScalingStrategy'  => 'time',
            'maxProcesses'         => 5,
            'maxTime'              => 0,
            'maxJobs'              => 0,
            'memory'               => 256,
            'tries'                => 3,
            'timeout'              => 30,
            'nice'                 => 0,
        ],

        /*
        |-----------------------------------------
        | Envio de mensagens (ANTI-BAN)
        |-----------------------------------------
        */
        'outgoing-supervisor' => [
            'connection'           => 'redis',
            'queue'                => ['outgoing'],
            'balance'              => 'simple',
            'maxProcesses'         => 2, // NÃO aumente muito
            'maxTime'              => 0,
            'maxJobs'              => 0,
            'memory'               => 256,
            'tries'                => 5,
            'timeout'              => 60,
            'nice'                 => 0,
        ],

        /*
        |-----------------------------------------
        | Automações / Bots / Regras
        |-----------------------------------------
        */
        'automation-supervisor' => [
            'connection'           => 'redis',
            'queue'                => ['automation'],
            'balance'              => 'auto',
            'autoScalingStrategy'  => 'time',
            'maxProcesses'         => 2,
            'maxTime'              => 0,
            'maxJobs'              => 0,
            'memory'               => 256,
            'tries'                => 3,
            'timeout'              => 60,
            'nice'                 => 0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Configuration (seconds)
    |--------------------------------------------------------------------------
    |
    | Waits define the maximum acceptable time a job can wait in queue before
    | being processed by a worker. They serve as alerts for identifying
    | congested queues but don't interrupt jobs.
    |
    */
    'waits' => [
        'redis:default' => 60,
        'redis:incoming' => 30,
        'redis:outgoing' => 60,
        'redis:automation' => 60,
        'redis:webhook' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment Specific Configuration
    |--------------------------------------------------------------------------
    */
    'environments' => [

        'production' => [
            'incoming-supervisor' => [
                'maxProcesses' => 10,
            ],
            'outgoing-supervisor' => [
                'maxProcesses' => 3,
            ],
            'automation-supervisor' => [
                'maxProcesses' => 5,
            ],
        ],

        'local' => [
            'incoming-supervisor' => [
                'maxProcesses' => 3,
            ],
            'outgoing-supervisor' => [
                'maxProcesses' => 1,
            ],
            'automation-supervisor' => [
                'maxProcesses' => 2,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Watcher Configuration
    |--------------------------------------------------------------------------
    */
    'watch' => [
        'app',
        'bootstrap',
        'config/**/*.php',
        'database/**/*.php',
        'routes',
        'composer.lock',
        'composer.json',
        '.env',
    ],
];
