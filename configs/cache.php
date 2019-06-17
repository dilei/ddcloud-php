<?php
return [
    'default' => 'memcached',
    'prefix' => 'DDCloud',

    'stores' => [
        'memcached' => [
            'driver' => 'memcached',
            'options' => [
                \Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                ['127.0.0.1', 11211, 100], // host port weight
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'host' => '127.0.0.1',
            'port' => 6379,
            'timeout' => 10,
        ],
    ],
];