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

    'service_request_timeout' => env('IRECEPTOR_SERVICE_REQUEST_TIMEOUT', 45),

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

];
