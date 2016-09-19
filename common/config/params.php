<?php
return [
    'billing' => [
        'ios' => [
            'validateSandbox' => false,
            'address' => 'https://buy.itunes.apple.com/verifyReceipt',
            'address_sandbox' => 'https://sandbox.itunes.apple.com/verifyReceipt',
        ],
        'android' => [
            'sig_algorithm'=> OPENSSL_ALGO_SHA1,
            'key_prefix'=> "-----BEGIN PUBLIC KEY-----\n",
            'key_suffix'=> '-----END PUBLIC KEY-----',
        ]
    ],
    'ad_platform' => [
        'applovin' => [
            'ecpm_url' => 'https://r.applovin.com/report?api_key=%api_key%&start=%start_date%&end=%end_date%&columns=day,ecpm,package_name,country&format=json&report_type=publisher',
        ],
        'vungle' => [
            'ecpm_url' => 'https://ssl.vungle.com/api/applications/%app_key%?key=%api_key%&start=%start_date%&end=%end_date%&geo=all',
        ],
        'unity' => [
            'ecpm_url' => 'http://gameads-admin.applifier.com/stats/monetization-api?apikey=%api_key%&splitBy=source,country&sourceIds=%app_key%&start=%start_date%&end=%end_date%',
        ]
    ],
    'user-agent' => 'blackbears-analytics v1.0',
];
