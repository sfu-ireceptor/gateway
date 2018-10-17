<?php

return [

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
    'service_request_timeout' => env('IRECEPTOR_SERVICE_REQUEST_TIMEOUT', 180),

    // allow more time for file queries
    'service_file_request_timeout' => env('IRECEPTOR_SERVICE_FILE_REQUEST_TIMEOUT', 2700),

    // allow less time for samples queries
    'service_request_timeout_samples' => env('IRECEPTOR_SERVICE_REQUEST_TIMEOUT_SAMPLES', 10),

    /*
    |--------------------------------------------------------------------------
    | Gateway request timeout
    |--------------------------------------------------------------------------
    |
    | Maximum execution time for a gateway request
    | Ex: showing a sequence page
    |
    */

    'gateway_request_timeout' => env('IRECEPTOR_GATEWAY_REQUEST_TIMEOUT', 240),

    'gateway_file_request_timeout' => env('IRECEPTOR_GATEWAY_FILE_REQUEST_TIMEOUT', 3600),

    /*
    |--------------------------------------------------------------------------
    | Sequences download limit
    |--------------------------------------------------------------------------
    |
    | Maximum number of sequences that can be downloaded at once
    | Ex: 30000
    |
    */

   'sequences_download_limit' => env('IRECEPTOR_SEQUENCES_DOWNLOAD_LIMIT', 500000),

];
