<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'memcache' => [
            'class' => 'yii\caching\MemCache',
            'useMemcached' => true,
            'keyPrefix' => 'bba-dashboard',
        ],
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
