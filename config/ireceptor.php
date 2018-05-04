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
    'service_file_request_timeout' => env('IRECEPTOR_SERVICE_FILE_REQUEST_TIMEOUT', 3600),

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
    'gateway_file_request_timeout' => env('IRECEPTOR_GATEWAY_FILE_REQUEST_TIMEOUT', 4800),

];
