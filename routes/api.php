<?php


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| They accept POST requests without CSRF token.
|
*/

// Agave jobs notifications
Route::post('agave/update-status/{id}/{status}', 'UtilController@updateAgaveStatus');

// Deployment trigger for GitHub hook
Route::any('deploy', 'UtilController@deploy');
