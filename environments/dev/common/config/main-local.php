<?php
return [
    'components' => [
        'clickhouse' => [
            'class' => 'common\components\Connection',
            'address' => '127.0.0.1',
            'port' => '8123',
            'username' => 'default',
            'password' => '',
            'database' => '',
        ],
    ],
];
