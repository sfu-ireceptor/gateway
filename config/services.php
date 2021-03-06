<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => 'us-east-1',
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'agave' => [
        'enabled'  => env('AGAVE_ENABLED', false),

        'tenant_url'  => env('AGAVE_TENANT_URL', 'https://irec.tenants.prod.tacc.cloud'),

        'api_key' => env('AGAVE_API_KEY'),
        'api_token' => env('AGAVE_API_TOKEN'),

        'admin_username' => env('AGAVE_ADMIN_USERNAME', 'irec_admin'),
        'admin_password' => env('AGAVE_ADMIN_PASSWORD'),

        'test_user_username' => env('TEST_USER_USERNAME'),
        'test_user_password' => env('TEST_USER_PASSWORD'),

        'system_execution' => [
            'name_prefix'  => env('AGAVE_SYSTEM_EXECUTION_NAME_PREFIX', 'exec-'),
        ],

        'system_staging' => [
            'name_prefix'  => env('AGAVE_SYSTEM_STAGING_NAME_PREFIX', 'staging-'),
            'host'  => env('AGAVE_SYSTEM_STAGING_HOST', 'ireceptorgw.irmacs.sfu.ca'),
            'port'  => env('AGAVE_SYSTEM_STAGING_PORT', 22),
            'auth' => env('AGAVE_SYSTEM_STAGING_AUTH'),
            'rootdir'  => env('AGAVE_SYSTEM_STAGING_ROOTDIR'),
        ],

        'system_deploy' => [
            'name_prefix'  => env('AGAVE_SYSTEM_DEPLOY_NAME_PREFIX', 'deploy-'),
            'host'  => env('AGAVE_SYSTEM_DEPLOY_HOST', 'ireceptorgw.irmacs.sfu.ca'),
            'port'  => env('AGAVE_SYSTEM_DEPLOY_PORT', 22),
            'auth' => env('AGAVE_SYSTEM_DEPLOY_AUTH'),
            'rootdir'  => env('AGAVE_SYSTEM_DEPLOY_ROOTDIR'),
        ],

        'gw_notification_url' => env('AGAVE_GW_NOTIFICATION_URL', 'https://ireceptorgw.irmacs.sfu.ca'),
    ],
];
