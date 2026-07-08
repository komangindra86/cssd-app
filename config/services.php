<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'bali_mandara' => [
        'ruangan_url' => env('BALI_MANDARA_RUANGAN_URL', 'https://app.balimandarahospital.com/service/emr/dropdown/custom/get-ruangan'),
        'pegawai_url' => env('BALI_MANDARA_PEGAWAI_URL',  'https://app.balimandarahospital.com/service/emr/dropdown/pegawai_m?'),
        'rawat_inap_url' => env('BALI_MANDARA_RAWAT_INAP_URL', 'https://app.balimandarahospital.com/service/dashboard/rawat-inap/list?page=1&limit=50&offet=0&ruanganfk=303'),
        'rawat_jalan_url' => env('BALI_MANDARA_RAWAT_JALAN_URL', 'https://app.balimandarahospital.com/service/dashboard/rawat-jalan-pasien?dari=2026-05-11&sampai=2026-05-11&kelompokUser=it&ruanganfk=201&page=1&limit=100'),
        'token' => env('BALI_MANDARA_TOKEN'),
        'cookie' => env('BALI_MANDARA_COOKIE'),
        'timeout' => env('BALI_MANDARA_TIMEOUT', 30),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
