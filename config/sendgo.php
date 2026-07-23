<?php

return [
    'url'              => env('SENDGO_URL', 'https://api.sendgo.io'),
    'access_key'       => env('SENDGO_ACCESS_KEY'),
    'secret_key'       => env('SENDGO_SECRET_KEY'),
    'sms_sender_key'   => env('SENDGO_SENDER_KEY'),
    'kakao_sender_key' => env('SENDGO_KAKAO_SENDER_KEY'),
    'api_version'      => env('SENDGO_API_VERSION', 'v1'),
];
