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
    'tapis' => [
        'enabled' => env('TAPIS_ENABLED', false),

        'tenant_url' => env('TAPIS_TENANT_URL', 'https://ireceptor.tapis.io'),
        'tapis_url' => env('TAPIS_TENANT_URL', 'https://ireceptor.tapis.io'),

        'api_key' => env('TAPIS_API_KEY'),
        'api_token' => env('TAPIS_API_TOKEN'),

        'admin_username' => env('TAPIS_ADMIN_USERNAME', 'irec_admin'),
        'admin_password' => env('TAPIS_ADMIN_PASSWORD'),

        'analysis_username' => env('TAPIS_ANALYSIS_USERNAME', 'irec_analysis'),
        'analysis_password' => env('TAPIS_ANALYSIS_PASSWORD'),

        'test_user_username' => env('TEST_USER_USERNAME'),
        'test_user_password' => env('TEST_USER_PASSWORD'),
        // The directory where Tapis Apps are found relative to resource_path()
        'app_base_dir' => 'tapis_apps',
        // The base directory in the analysis app where the analysis output is stored.
        // This exists on the compute plaform and is also needed on the Gateway since
        // the gateway downloads the analsyis output.
        'analysis_base_dir' => 'gateway_analysis',
        // App JSON defintion script that is used for every App. Apps need to
        // implement this as it describes the parameters and type of App.
        'app_json_file' => 'app3.json',
        // Directories where the Apps can be found that are currently active.
        'app_directories' => [
            'rearrangement-stats-singularity',
            'rearrangement-histogram-singularity',
            'rearrangement-immunarch-singularity',
            'rearrangement-repcred-singularity',
            'rearrangement-tcrmatch-singularity',
            'rearrangement-cdr3_motif-singularity',
            'rearrangement-base-app-singularity',
            'rearrangement-compairr-singularity',
            'rearrangement-olga-singularity',
            'rearrangement-junction-aa-singularity',
            'clone-stats-singularity',
            'cell-conga-singularity',
            'cell-celltypist-singularity',
        ],
        'system_execution' => [
            // Prefix for execution system
            'name_prefix' => env('TAPIS_SYSTEM_EXECUTION_NAME_PREFIX', 'exec'),
            // Max length of a job
            'max_minutes' => env('TAPIS_JOB_MAX_MINUTES', intval(1439)),
            // Number of cores per node for a job
            'cores_per_node' => env('TAPIS_JOB_CORES_PER_NODE', intval(1)),
            // Memory per node for a job
            'memory_per_node' => env('TAPIS_JOB_MEMORY_PER_NODE', intval(7999)),
            // Memory per core for a job
            'memory_per_core' => env('TAPIS_JOB_MEMORY_PER_CORE', intval(7999)),
            //
            // Directory structure for jobs.  There are three important directories
            // for Tapis jobs. These directories are full paths:
            //   - exec_job_working_dir: is where jobs are run from on the exec host.
            //     This is a path on cedar.
            //   - exec_gateway_mount_dir: This is where all of the utility files that
            //     the gateway needs to run, such as singularity images and App files.
            //     This is a path on cedar.
            //   - container_gateway_mount_dir: This is a directory within the container
            //     where the gateway files reside. This is a path within the CONTAINER and
            //     NOT on cedar. There is essentially a container mount performed as per:
            //          - B $exec_gateway_mount_dir:$container_gateway_mount_dir
            //
            // All of the container specific information is provided to the App in the
            // container through environment variables.
            //
            // NOTE: In the containers it appears that only one -B directive is passed to
            // the container by Tapis. Thus it is easiest and cleanest if all of the
            // above directories are the same so the job, utility, and container directories
            // are all one and the same.
            //
            // Path to where the job input/ouput is on cedar.
            'exec_job_working_dir' => env('TAPIS_SYSTEM_EXEC_JOB_WORKING_DIR', '/scratch/ireceptorgw'),
            // Path to where the gateway utility files are on cedar.
            'exec_gateway_mount_dir' => env('TAPIS_SYSTEM_EXEC_GATEWAY_MOUNT_DIR', '/scratch/ireceptorgw'),
            // Realtive directory to exec_gateway_mount_dir where the singularity images are stored
            'exec_singularity_dir' => 'gateway_base/singularity',
            // Mount point in the singularity container for gateway files.
            // Essentially exec_gateway_mount_dir on cedar is mounted in the container on $container_gateway_mount_dir
            'container_gateway_mount_dir' => env('TAPIS_SYSTEM_EXEC_CONTAINER_MOUNT_DIR', '/scratch/ireceptorgw'),
            // Relative container location of gateway utilities relative container_gateway_mount_dir
            'container_util_dir' => 'gateway_base/gateway_utilities',
            // Relative container location of gateway apps relative to container_gateway_mount_dir
            'container_app_dir' => 'gateway_base/tapis_apps',
            // App script that is run for every App. Apps need to implement this, and the
            // Gateway runs it in the container with the other App Args.
            'container_app_script' => 'app3.sh',
        ],

        'system_staging' => [
            'name_prefix' => env('TAPIS_SYSTEM_STAGING_NAME_PREFIX', 'staging'),
            'host' => env('TAPIS_SYSTEM_STAGING_HOST', 'gateway.ireceptor.org'),
            'port' => intval(env('TAPIS_SYSTEM_STAGING_PORT', 22)),
            'auth' => [
                'username' => env('TAPIS_SYSTEM_STAGING_AUTH_USERNAME', 'ubuntu'),
                'public_key' => @file_get_contents(env('TAPIS_SYSTEM_STAGING_AUTH_PUBLIC_KEY', base_path('storage/config/tapis-system-staging_public-key.txt'))),
                'private_key' => @file_get_contents(env('TAPIS_SYSTEM_STAGING_AUTH_PRIVATE_KEY', base_path('storage/config/tapis-system-staging_private-key.txt'))),
            ],
            'rootdir' => env('TAPIS_SYSTEM_STAGING_ROOTDIR', '/tmp'),
        ],

        'system_deploy' => [
            'name_prefix' => env('TAPIS_SYSTEM_DEPLOY_NAME_PREFIX', 'deploy'),
            'host' => env('TAPIS_SYSTEM_DEPLOY_HOST', 'gateway.ireceptor.org'),
            'port' => intval(env('TAPIS_SYSTEM_DEPLOY_PORT', 22)),
            'auth' => [
                'username' => env('TAPIS_SYSTEM_DEPLOY_AUTH_USERNAME', 'ubuntu'),
                'public_key' => @file_get_contents(env('TAPIS_SYSTEM_DEPLOY_AUTH_PUBLIC_KEY', base_path('storage/config/tapis-system-deploy_public-key.txt'))),
                'private_key' => @file_get_contents(env('TAPIS_SYSTEM_DEPLOY_AUTH_PRIVATE_KEY', base_path('storage/config/tapis-system-deploy_private-key.txt'))),
            ],
            'rootdir' => env('TAPIS_SYSTEM_DEPLOY_ROOTDIR'),
        ],

        'default_execution_system' => [
            'host' => env('TAPIS_DEFAULT_EXECUTION_SYSTEM_HOST', 'robot.cedar.alliancecan.ca'),
            'port' => intval(env('TAPIS_DEFAULT_EXECUTION_SYSTEM_PORT', 22)),
            'auth' => [
                'username' => env('TAPIS_DEFAULT_EXECUTION_SYSTEM_AUTH_USERNAME', 'ireceptorgw'),
            ],
            'rootdir' => env('TAPIS_DEFAULT_EXECUTION_SYSTEM_ROOTDIR', '/tmp'),
        ],

        'gw_notification_url' => env('TAPIS_GW_NOTIFICATION_URL', 'https://ireceptorgw.irmacs.sfu.ca'),
    ],
];
