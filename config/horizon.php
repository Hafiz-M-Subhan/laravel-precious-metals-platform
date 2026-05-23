<?php

use Laravel\Horizon\Horizon;

Horizon::night();

return [

    'domain' => env('HORIZON_DOMAIN'),
    'path'   => env('HORIZON_PATH', 'horizon'),

    'driver' => env('QUEUE_CONNECTION', 'redis'),
    'use'    => 'default',

    'prefix' => env('HORIZON_PREFIX', 'horizon:'),

    'middleware' => ['web'],

    'waits' => [
        'redis:default'       => 60,
        'redis:orders'        => 30,
        'redis:savings-plans' => 60,
        'redis:notifications' => 90,
    ],

    'trim' => [
        'recent'        => 60,
        'pending'       => 60,
        'completed'     => 60,
        'recent_failed' => 10080,
        'failed'        => 10080,
        'monitored'     => 10080,
    ],

    'silenced' => [],

    'metrics' => [
        'trim_snapshots' => [
            'job'   => 24,
            'queue' => 24,
        ],
    ],

    'fast_termination' => false,

    'memory_limit' => 64,

    'defaults' => [
        'supervisor-orders' => [
            'connection'     => 'redis',
            'queue'          => ['orders'],
            'balance'        => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses'   => 5,
            'maxTime'        => 0,
            'maxJobs'        => 0,
            'memory'         => 128,
            'tries'          => 3,
            'timeout'        => 60,
            'nice'           => 0,
        ],
        'supervisor-savings' => [
            'connection'     => 'redis',
            'queue'          => ['savings-plans'],
            'balance'        => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses'   => 3,
            'maxTime'        => 0,
            'maxJobs'        => 0,
            'memory'         => 128,
            'tries'          => 3,
            'timeout'        => 90,
            'nice'           => 0,
        ],
        'supervisor-notifications' => [
            'connection'     => 'redis',
            'queue'          => ['notifications'],
            'balance'        => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses'   => 4,
            'maxTime'        => 0,
            'maxJobs'        => 0,
            'memory'         => 128,
            'tries'          => 3,
            'timeout'        => 60,
            'nice'           => 0,
        ],
        'supervisor-default' => [
            'connection'     => 'redis',
            'queue'          => ['default'],
            'balance'        => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses'   => 3,
            'maxTime'        => 0,
            'maxJobs'        => 0,
            'memory'         => 128,
            'tries'          => 1,
            'timeout'        => 60,
            'nice'           => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-orders' => [
                'maxProcesses'  => 10,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'supervisor-savings' => [
                'maxProcesses'  => 5,
            ],
            'supervisor-notifications' => [
                'maxProcesses'  => 8,
            ],
            'supervisor-default' => [
                'maxProcesses'  => 5,
            ],
        ],

        'local' => [
            'supervisor-orders' => [
                'maxProcesses'  => 3,
            ],
            'supervisor-savings' => [
                'maxProcesses'  => 2,
            ],
            'supervisor-notifications' => [
                'maxProcesses'  => 2,
            ],
            'supervisor-default' => [
                'maxProcesses'  => 2,
            ],
        ],
    ],
];
