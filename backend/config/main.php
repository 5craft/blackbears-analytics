<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'blackbears-analytics',
    'basePath' => dirname(__DIR__),
    'name' => 'Black Bears Analytics',
    'language' => 'ru',
    'sourceLanguage' => 'en',
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
    'modules' => [],
    'components' => [
        'request' => [
            'cookieValidationKey' => 'sdi8s#cqw9x32rqiw!fjgh0d8f',
            'enableCookieValidation' => true,
            'enableCsrfValidation' => false,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning','info'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'rules' => [
                'index/<application_id:\w+>' => 'site/index',
                '<action:\w+>' => 'site/<action>',
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
            ],
        ],
        'i18n' => [
            'translations' => [
                'dashboard'=>[
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath'=>'@backend/messages',
                ],
                'error'=>[
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath'=>'@backend/messages',
                ],
            ]
        ]
    ],
    'params' => $params,
];
