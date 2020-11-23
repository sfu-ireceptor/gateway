<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email addresses
    |--------------------------------------------------------------------------
    */

    'email_support' => env('IRECEPTOR_EMAIL_SUPPORT', 'support@ireceptor.org'),

    /*
    |--------------------------------------------------------------------------
    | Service request timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time the gateway will wait for a request to a service
    | Ex: /sequence_summary query to VDJServer
    |
    */

    // default
    'service_request_timeout' => env('IRECEPTOR_SERVICE_REQUEST_TIMEOUT', 3 * 60),

    // allow more time for file queries
    'service_file_request_timeout' => env('IRECEPTOR_SERVICE_FILE_REQUEST_TIMEOUT', 24 * 60 * 60),

    // chunked file queries need less time
    'service_file_request_chunked_timeout' => env('IRECEPTOR_SERVICE_FILE_REQUEST_CHUNKED_TIMEOUT', 10 * 60),

    // allow less time for samples queries
    'service_request_timeout_samples' => env('IRECEPTOR_SERVICE_REQUEST_TIMEOUT_SAMPLES', 20),

    /*
    |--------------------------------------------------------------------------
    | Gateway request timeout
    |--------------------------------------------------------------------------
    |
    | Maximum execution time for a gateway request
    | Ex: showing a sequence page
    |
    */

    'gateway_request_timeout' => env('IRECEPTOR_GATEWAY_REQUEST_TIMEOUT', 4 * 60),

    'gateway_file_request_timeout' => env('IRECEPTOR_GATEWAY_FILE_REQUEST_TIMEOUT', 3 * 24 * 60 * 60),

    /*
    |--------------------------------------------------------------------------
    | Sequences download limit
    |--------------------------------------------------------------------------
    |
    | Maximum number of sequences that can be downloaded at once
    | Ex: 30000
    |
    */

    'sequences_download_limit' => env('IRECEPTOR_SEQUENCES_DOWNLOAD_LIMIT', 500000000),

    /*
    |--------------------------------------------------------------------------
    | Sequence downloads page refresh interval
    |--------------------------------------------------------------------------
    |
    | The number of seconds after which the sequence downloads page will reload
    | Ex: 30
    |
    */

    'sequences_downloads_refresh_interval' => env('IRECEPTOR_SEQUENCES_REFRESH_INTERVAL', 20),

    /*
    |--------------------------------------------------------------------------
    | Display all ir_ fields
    |--------------------------------------------------------------------------
    |
    | Display all ir-prefixed fields
    | Ex: ir_show_unproductive
    |
    */

    'display_all_ir_fields' => env('IRECEPTOR_IR_FIELDS', false),

    /*
    |--------------------------------------------------------------------------
    | Number of samples displayed by default
    |--------------------------------------------------------------------------
    |
    | Number of samples displayed by default on the samples page
    | Ex: 100
    |
    */

    'nb_samples_per_page' => env('IRECEPTOR_NB_SAMPLES_PER_PAGE', 120),

    /*
    |--------------------------------------------------------------------------
    | Allow repository grouping
    |--------------------------------------------------------------------------
    |
    | If false, the repository group will be removed when running RestServiceSeeder 
    | Ex: false
    |
    */

    'group_repositories' => env('IRECEPTOR_GROUP_REPOSITORIES', true),
];
