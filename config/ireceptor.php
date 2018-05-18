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

    'service_request_timeout' => env('IRECEPTOR_SERVICE_REQUEST_TIMEOUT', 120),
    'service_file_request_timeout' => env('IRECEPTOR_SERVICE_FILE_REQUEST_TIMEOUT', 2700),

    /*
    |--------------------------------------------------------------------------
    | Gateway request timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time the gateway will take to handle a user request
    | Ex: loading a sequence page
    |
    */

    'gateway_request_timeout' => env('IRECEPTOR_GATEWAY_REQUEST_TIMEOUT', 180),
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
