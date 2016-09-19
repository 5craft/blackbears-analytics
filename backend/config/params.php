<?php
return [
    'yandex_oauth_id' => '',
    'yandex_url' => [
        'appmetrica_app_list' =>'https://beta.api-appmetrika.yandex.ru/management/v1/applications?oauth_token=%oauth_token%',
        'appmetrica_app_add' => 'https://appmetrika.yandex.ru/application/new',
        'account_info' => 'https://login.yandex.ru/info?format=json&oauth_token=%oauth_token%',
        'oauth_login' => 'https://oauth.yandex.ru/authorize?response_type=token&client_id=%oauth_client_id%',
        ],
    'metric_prefix' => ['K','M'] //кратные 1000 по возрастающей
];
