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
        'app_directories' => [
            'stats', 'histogram', 'clone-stats', 'cell-conga-singularity', 'cell-celltypist-singularity', 'immunarch-singularity',
        ],
        'system_execution' => [
            'name_prefix'  => env('AGAVE_SYSTEM_EXECUTION_NAME_PREFIX', 'exec-'),
            'max_run_time' => intval(96),
            'processors_per_node' => intval(1),
            'memory_per_processor' => intval(8),
        ],

        'system_staging' => [
            'name_prefix'  => env('AGAVE_SYSTEM_STAGING_NAME_PREFIX', 'staging-'),
            'host'  => env('AGAVE_SYSTEM_STAGING_HOST', 'ireceptorgw.irmacs.sfu.ca'),
            'port'  => intval(env('AGAVE_SYSTEM_STAGING_PORT', 22)),
            'auth'  => [
                'username'      => env('AGAVE_SYSTEM_STAGING_AUTH_USERNAME', 'ubuntu'),
                'public_key'    => @file_get_contents(env('AGAVE_SYSTEM_STAGING_AUTH_PUBLIC_KEY', base_path('storage/config/agave-system-staging_public-key.txt'))),
                'private_key'   => @file_get_contents(env('AGAVE_SYSTEM_STAGING_AUTH_PRIVATE_KEY', base_path('storage/config/agave-system-staging_private-key.txt'))),
            ],
            'rootdir'  => env('AGAVE_SYSTEM_STAGING_ROOTDIR', '/tmp'),
        ],

        'system_deploy' => [
            'name_prefix'  => env('AGAVE_SYSTEM_DEPLOY_NAME_PREFIX', 'deploy-'),
            'host'  => env('AGAVE_SYSTEM_DEPLOY_HOST', 'ireceptorgw.irmacs.sfu.ca'),
            'port'  => intval(env('AGAVE_SYSTEM_DEPLOY_PORT', 22)),
            'auth'  => [
                'username'      => env('AGAVE_SYSTEM_DEPLOY_AUTH_USERNAME', 'ubuntu'),
                'public_key'    => @file_get_contents(env('AGAVE_SYSTEM_DEPLOY_AUTH_PUBLIC_KEY', base_path('storage/config/agave-system-deploy_public-key.txt'))),
                'private_key'   => @file_get_contents(env('AGAVE_SYSTEM_DEPLOY_AUTH_PRIVATE_KEY', base_path('storage/config/agave-system-deploy_private-key.txt'))),
            ],
            'rootdir'  => env('AGAVE_SYSTEM_DEPLOY_ROOTDIR'),
        ],

        'default_execution_system' => [
            'host'  => env('AGAVE_DEFAULT_EXECUTION_SYSTEM_HOST', 'cedar.computecanada.ca'),
            'port'  => intval(env('AGAVE_DEFAULT_EXECUTION_SYSTEM_PORT', 22)),
            'auth'  => [
                'username'      => env('AGAVE_DEFAULT_EXECUTION_SYSTEM_AUTH_USERNAME', 'ireceptorgw'),
                'public_key'    => @file_get_contents(env('AGAVE_DEFAULT_EXECUTION_SYSTEM_AUTH_PUBLIC_KEY', base_path('storage/config/agave-default-execution-system_public-key.txt'))),
                'private_key'   => @file_get_contents(env('AGAVE_DEFAULT_EXECUTION_SYSTEM_AUTH_PRIVATE_KEY', base_path('storage/config/agave-default-execution-system_private-key.txt'))),
            ],
            'rootdir'  => env('AGAVE_DEFAULT_EXECUTION_SYSTEM_ROOTDIR', '/tmp'),
        ],

        'gw_notification_url' => env('AGAVE_GW_NOTIFICATION_URL', 'https://ireceptorgw.irmacs.sfu.ca'),
    ],
    'tapis' => [
        'enabled'  => env('TAPIS_ENABLED', false),

        'tenant_url'  => env('TAPIS_TENANT_URL', 'https://ireceptor.tapis.io'),
        'tapis_url'  => env('TAPIS_TENANT_URL', 'https://ireceptor.tapis.io'),
        //'tapis_url'  => env('TAPIS_URL', 'https://tacc.tapis.io'),

        'api_key' => env('TAPIS_API_KEY'),
        'api_token' => env('TAPIS_API_TOKEN'),

        'admin_username' => env('TAPIS_ADMIN_USERNAME', 'irec_admin'),
        'admin_password' => env('TAPIS_ADMIN_PASSWORD'),

        'analysis_username' => env('TAPIS_ANALYSIS_USERNAME', 'irec_analysis'),
        'analysis_password' => env('TAPIS_ANALYSIS_PASSWORD'),

        'test_user_username' => env('TEST_USER_USERNAME'),
        'test_user_password' => env('TEST_USER_PASSWORD'),
        'app_base_dir' => 'tapis_apps',
        'app_directories' => [
            'stats', 'histogram', 'clone-stats', 'cell-conga-singularity', 'cell-celltypist-singularity', 'immunarch-singularity',
        ],
        'system_execution' => [
            'name_prefix'  => env('TAPIS_SYSTEM_EXECUTION_NAME_PREFIX', 'exec'),
            'max_minutes' => intval(2880),
            'cores_per_node' => intval(1),
            'memory_per_node' => intval(8000),
            'memory_per_core' => intval(8000),
            // Base directory for all job output. Uses the Tapis HOST_EVAL function
            // to access a host environment variable.
            //'exec_job_working_dir' => 'HOST_EVAL($HOME)/projects/rpp-breden/ireceptorgw/tapis-jobs',
            //'exec_job_working_dir' => 'HOST_EVAL($SCRATCH)',
            'exec_job_working_dir' => '/scratch/ireceptorgw',
            //'exec_job_working_dir' => '/project/6008066/ireceptorgw/tapis_jobs',
            // Directory on the execution system where the gateway analysis stuff is located
            //'exec_gateway_base_dir' => '/project/6008066',
            'exec_gateway_base_dir' => '/scratch/ireceptorgw',
            // Directory where the singularity images are stored - relative to exec_gateway_base
            'exec_singularity_dir' => '/gateway_base/singularity',
            // Mount point in the singularity container for gateway_base
            //'container_gateway_base_dir' => '/project/6008066',
            'container_gateway_base_dir' => '/scratch/ireceptorgw',
            // Inside container location of gateway utilities relative to container mount point
            'container_util_dir' => '/gateway_base/gateway_utilities',
            // Inside container location of gateway apps relative to container mount point
            'container_app_dir' => '/gateway_base/tapis_apps',
        ],

        'system_staging' => [
            'name_prefix'  => env('TAPIS_SYSTEM_STAGING_NAME_PREFIX', 'staging'),
            'host'  => env('TAPIS_SYSTEM_STAGING_HOST', 'gateway.ireceptor.org'),
            'port'  => intval(env('TAPIS_SYSTEM_STAGING_PORT', 22)),
            'auth'  => [
                'username'      => env('TAPIS_SYSTEM_STAGING_AUTH_USERNAME', 'ubuntu'),
                'public_key'    => @file_get_contents(env('TAPIS_SYSTEM_STAGING_AUTH_PUBLIC_KEY', base_path('storage/config/tapis-system-staging_public-key.txt'))),
                'private_key'   => @file_get_contents(env('TAPIS_SYSTEM_STAGING_AUTH_PRIVATE_KEY', base_path('storage/config/tapis-system-staging_private-key.txt'))),
            ],
            'rootdir'  => env('TAPIS_SYSTEM_STAGING_ROOTDIR', '/tmp'),
        ],

        'system_deploy' => [
            'name_prefix'  => env('TAPIS_SYSTEM_DEPLOY_NAME_PREFIX', 'deploy'),
            'host'  => env('TAPIS_SYSTEM_DEPLOY_HOST', 'gateway.ireceptor.org'),
            'port'  => intval(env('TAPIS_SYSTEM_DEPLOY_PORT', 22)),
            'auth'  => [
                'username'      => env('TAPIS_SYSTEM_DEPLOY_AUTH_USERNAME', 'ubuntu'),
                'public_key'    => @file_get_contents(env('TAPIS_SYSTEM_DEPLOY_AUTH_PUBLIC_KEY', base_path('storage/config/tapis-system-deploy_public-key.txt'))),
                'private_key'   => @file_get_contents(env('TAPIS_SYSTEM_DEPLOY_AUTH_PRIVATE_KEY', base_path('storage/config/tapis-system-deploy_private-key.txt'))),
            ],
            'rootdir'  => env('TAPIS_SYSTEM_DEPLOY_ROOTDIR'),
        ],

        'default_execution_system' => [
            'host'  => env('TAPIS_DEFAULT_EXECUTION_SYSTEM_HOST', 'cedar.computecanada.ca'),
            'port'  => intval(env('TAPIS_DEFAULT_EXECUTION_SYSTEM_PORT', 22)),
            'auth'  => [
                'username'      => env('TAPIS_DEFAULT_EXECUTION_SYSTEM_AUTH_USERNAME', 'ireceptorgw'),
                'sshkeys'    => @file_get_contents(env('TAPIS_DEFAULT_EXECUTION_SYSTEM_AUTH_SSHKEYS', base_path('storage/config/tapis-default-execution-system_cred.json'))),
                'public_key'    => @file_get_contents(env('TAPIS_DEFAULT_EXECUTION_SYSTEM_AUTH_PUBLIC_KEY', base_path('storage/config/tapis-default-execution-system_public-key.txt'))),
                'private_key'   => @file_get_contents(env('TAPIS_DEFAULT_EXECUTION_SYSTEM_AUTH_PRIVATE_KEY', base_path('storage/config/tapis-default-execution-system_private-key.txt'))),
            ],
            'rootdir'  => env('TAPIS_DEFAULT_EXECUTION_SYSTEM_ROOTDIR', '/tmp'),
        ],

        'gw_notification_url' => env('TAPIS_GW_NOTIFICATION_URL', 'https://ireceptorgw.irmacs.sfu.ca'),
    ],
];
