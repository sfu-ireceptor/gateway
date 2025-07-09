<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable authentication
    |--------------------------------------------------------------------------
    |
    | If false, entering an existing user username is enough to log in
    | Ex: false
    |
    */

    'auth' => env('IRECEPTOR_AUTH', true),

    /*
    |--------------------------------------------------------------------------
    | Enable CANARIE monitoring
    |--------------------------------------------------------------------------
    |
    | If false, CANARIE routes will be disabled
    | Ex: false
    |
    */

    'canarie' => env('IRECEPTOR_CANARIE', true),

    /*
    |--------------------------------------------------------------------------
    | Enable home page banner message.
    |--------------------------------------------------------------------------
    |
    | If true, display banner message on home page
    | Ex: false
    |
    */

    'home_banner_display' => env('IRECEPTOR_HOME_BANNER_DISPLAY', false),
    'home_banner_text' => env('IRECEPTOR_HOME_BANNER_TEXT', ''),

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
    'service_request_timeout' => env('IRECEPTOR_SERVICE_REQUEST_TIMEOUT', 3 * 60 + 20),

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
    | Clones download limit
    |--------------------------------------------------------------------------
    |
    | Maximum number of clones that can be downloaded at once
    | Ex: 30000
    |
    */

    'clones_download_limit' => env('IRECEPTOR_CLONES_DOWNLOAD_LIMIT', 500000000),

    /*
    |--------------------------------------------------------------------------
    | Cells download limit
    |--------------------------------------------------------------------------
    |
    | Maximum number of cells that can be downloaded at once
    | Ex: 30000
    |
    */

    'cells_download_limit' => env('IRECEPTOR_CELLS_DOWNLOAD_LIMIT', 500000000),

    /*
    |--------------------------------------------------------------------------
    | Sequence large download limit
    |--------------------------------------------------------------------------
    |
    | Maximum number of sequences that are considered a small download
    | Ex: 2000000
    |
    */

    'sequence_large_download_limit' => env('IRECEPTOR_SEQUENCE_LARGE_DOWNLOAD_LIMIT', 2000000),

    /*
    |--------------------------------------------------------------------------
    | Clone large download limit
    |--------------------------------------------------------------------------
    |
    | Maximum number of clones that are considered a small download
    | Ex: 2000000
    |
    */

    'clone_large_download_limit' => env('IRECEPTOR_CLONE_LARGE_DOWNLOAD_LIMIT', 2000000),

    /*
    |--------------------------------------------------------------------------
    | Cell large download limit
    |--------------------------------------------------------------------------
    |
    | Maximum number of cells that are considered a small download
    | Ex: 20000
    |
    */

    'cell_large_download_limit' => env('IRECEPTOR_CELL_LARGE_DOWNLOAD_LIMIT', 20000),

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
    | Downloads data folder
    |--------------------------------------------------------------------------
    |
    | Where downloads are stored. This should be relative to Laravel's
    | storage_path().
    | Ex: downloads would imply storage_path() . '/downloads'
    |
    */

    'downloads_data_folder' => env('IRECEPTOR_DOWNLOADS_DATA_FOLDER', 'downloads'),

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

    /*
    |--------------------------------------------------------------------------
    | Seeders data folder
    |--------------------------------------------------------------------------
    |
    | To allow override for Docker
    | Ex: database/seeds/data
    |
    */

    'seeders_data_folder' => env('IRECEPTOR_SEEDERS_DATA_FOLDER', base_path() . '/database/seeds/data'),

    /*
    |--------------------------------------------------------------------------
    | Default AIRR API version
    |--------------------------------------------------------------------------
    |
    | Should match a TSV mapping file in
    | database/seeds/data/field_names
    |
    */

    'default_api_version' => env('IRECEPTOR_DEFAULT_API_VERSION', '1.2'),
];
